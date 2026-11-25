"""TrackFlow courier management web application.

Database design: MySQL stores the business data and implements the stored
functions, booking procedure, status-change trigger, and reporting views.
Flask handles customer/employee/agent requests and sends SQL to MySQL.
"""



import os, secrets, hmac, re
from decimal import Decimal, InvalidOperation
from flask import Flask, render_template, request, session, redirect, url_for, flash, g, abort
# MySQL CONNECTOR: Imports the driver used by Flask to communicate with MySQL.
import mysql.connector
from dotenv import load_dotenv

load_dotenv()
app = Flask(__name__)
app.secret_key = os.environ.get('TRACKFLOW_SECRET') or secrets.token_hex(32)
STEPS = ['Assigned','Picked Up','In Transit','Out for Delivery','Delivered']
TYPES = ['Document','Parcel','Fragile','Express']
METHODS = ['Cash','UPI','Card']



# DATABASE CONNECTION (MySQL)
# db() connects to the existing trackflow_db schema on localhost, port 3306.
# Credentials can be supplied through TRACKFLOW_USER and TRACKFLOW_PASSWORD.
# Flask's `g` keeps one connection per request, so helper functions reuse it.
# This function does not create the database or any of its tables.
def db():
    if 'db' not in g:
        g.db = mysql.connector.connect(host='127.0.0.1', port=3306, user=os.getenv('TRACKFLOW_USER','trackflow_user'), password=os.getenv('TRACKFLOW_PASSWORD',''), database='trackflow_db')
    return g.db
# DATABASE CLEANUP: Close the request connection to release MySQL resources.
@app.teardown_appcontext
def close_db(error):
    if 'db' in g: g.db.close()




# SELECT HELPER: %s placeholders pass values safely; rows are returned as dictionaries.
def query(sql, args=()):
    with db().cursor(dictionary=True, buffered=True) as c:
        c.execute(sql,args)
        return c.fetchall()
# SINGLE-ROW HELPER: Return one dictionary or None if no matching record exists.
def one(sql,args=()):
    rows=query(sql,args)
    return rows[0] if rows else None
# WRITE HELPER: Execute a modifying SQL statement; the caller must commit or roll back.
def write(sql,args=()):
    with db().cursor() as c:
        c.execute(sql,args)
        return c.rowcount,c.lastrowid
@app.before_request
def csrf():
    session.setdefault('csrf',secrets.token_hex(32))
    if request.method=='POST' and not hmac.compare_digest(session['csrf'],request.form.get('csrf','')): abort(400)
# TRANSACTION FAILURE: Roll back changes that have not yet been committed.
# Note: a COMMIT performed inside a stored procedure cannot be undone here.
def action_error(e):
    db().rollback()
    flash(str(e) if isinstance(e,ValueError) else 'Database operation failed. Check the terminal and SQL setup.','error')
    if not isinstance(e,ValueError): app.logger.exception('Database operation failed')
def f(key): return request.form.get(key,'').strip()
def num(key): return int(f(key) or 0)
@app.route('/')






# DATABASE VIEWS AND REPORTS
# These seven *_Report names are MySQL views, defined in the database, not Python.
# SELECT * reads their prepared report results for the homepage.
# The four queries below are direct SQL reports, NOT stored views.
def home():
    reports={v.replace('_',' '):query('SELECT * FROM '+v) for v in ['Booking_Report','Shipment_Report','Tracking_Report','Payment_Report','Agent_Report','Branch_Report','Feedback_Report']}
    reports['City revenue']=query("SELECT s.Source AS City,SUM(p.Amount) AS Revenue FROM Shipment s JOIN Payment p ON p.Courier_ID=s.Courier_ID WHERE p.Status='Paid' GROUP BY s.Source")
    reports['Customer spending']=query("SELECT c.Name,SUM(p.Amount) AS Spending FROM Customer c JOIN Booking b ON b.Customer_ID=c.Customer_ID JOIN Payment p ON p.Courier_ID=b.Courier_ID WHERE p.Status='Paid' GROUP BY c.Customer_ID,c.Name")
    reports['Unassigned agents']=query('SELECT * FROM Delivery_Agent WHERE Agent_ID NOT IN (SELECT Agent_ID FROM Delivery)')
    reports['Delivered without feedback']=query("SELECT * FROM Shipment WHERE Status='Delivered' AND Courier_ID NOT IN (SELECT Courier_ID FROM Feedback)")
    # Public demo homepage excludes individual customer reports; reports available via explicit demo mode.
    return render_template('page.html',mode='home',reports=reports)
@app.route('/employee',methods=['GET','POST'])





# MYSQL SELECT: Loads employee and branch information for the employee dashboard.
def employee():
    employees=query('SELECT e.*,b.Branch_Name,b.City FROM Employee e JOIN Branch b ON b.Branch_ID=e.Branch_ID ORDER BY b.Branch_Name,e.Name')
    if request.method=='POST':
        try:
            if f('action')=='enter': session['employee']=num('employee')
            elif f('action')=='logout': session.pop('employee',None)
            else:
                eid=session.get('employee',0)
                # MYSQL INSERT: Assigns an agent to an eligible shipment.
                count,_=write("INSERT INTO Delivery (Shipment_ID,Agent_ID,Status) SELECT s.Shipment_ID,a.Agent_ID,'Assigned' FROM Shipment s JOIN Branch b ON b.City=s.Source JOIN Employee e ON e.Branch_ID=b.Branch_ID JOIN Delivery_Agent a ON a.Branch_ID=b.Branch_ID WHERE e.Employee_ID=%s AND a.Agent_ID=%s AND s.Shipment_ID=%s AND s.Status='Booked' AND NOT EXISTS (SELECT 1 FROM Delivery d WHERE d.Shipment_ID=s.Shipment_ID)",(eid,num('agent'),num('shipment')))
                if not count: raise ValueError('Invalid assignment or courier already assigned.')
                # MYSQL COMMIT: Permanently saves the successful agent assignment.
                db().commit(); flash('Courier assigned.')
        except (ValueError,mysql.connector.Error) as e: action_error(e)
        return redirect(url_for('employee'))
    emp=next((e for e in employees if e['Employee_ID']==session.get('employee')),None)
    # MYSQL SELECT: Retrieves unassigned shipments for the employee branch.
    shipments=query("SELECT s.*,c.Tracking_No FROM Shipment s JOIN Courier c ON c.Courier_ID=s.Courier_ID WHERE s.Source=%s AND s.Status='Booked' AND NOT EXISTS (SELECT 1 FROM Delivery d WHERE d.Shipment_ID=s.Shipment_ID)",(emp['City'],)) if emp else []
    # MYSQL SELECT: Retrieves available delivery agents.
    agents=query('SELECT * FROM Delivery_Agent WHERE Branch_ID=%s',(emp['Branch_ID'],)) if emp else []
    return render_template('page.html',mode='employee',employees=employees,emp=emp,shipments=shipments,agents=agents)
@app.route('/agent',methods=['GET','POST'])




# MYSQL SELECT: Loads delivery agents and their branch information.
def agent():
    # MYSQL SELECT: Retrieves available delivery agents.
    agents=query('SELECT a.*,b.Branch_Name FROM Delivery_Agent a JOIN Branch b ON b.Branch_ID=a.Branch_ID ORDER BY b.Branch_Name,a.Name')
    aid=session.get('agent',0)
    if request.method=='POST':
        try:
            if f('action')=='enter': session['agent']=num('agent')
            elif f('action')=='logout': session.pop('agent',None)
            # PAYMENT WORKFLOW: The agent endpoint changes Payment.Pending to Paid.
            # The customer dashboard only reads payment status; it cannot confirm it.
            elif f('action')=='pay':
                # ACID PAYMENT TRANSACTION
                conn = db()

                # Lock payment until COMMIT or ROLLBACK
                payment = one(
                    """SELECT p.Payment_ID, p.Status, p.Amount
                       FROM Payment p
                       JOIN Shipment s ON s.Courier_ID=p.Courier_ID
                       JOIN Delivery d ON d.Shipment_ID=s.Shipment_ID
                       WHERE d.Agent_ID=%s AND d.Delivery_ID=%s
                       FOR UPDATE""",
                    (aid, num('delivery'))
                )

                # CONSISTENCY: Validate payment
                if not payment:
                    raise ValueError('Payment not found.')

                if payment['Status'] != 'Pending':
                    raise ValueError('Payment already processed.')

                if payment['Amount'] is None or payment['Amount'] <= 0:
                    raise ValueError('Invalid payment amount.')

                # ATOMICITY: Update within transaction
                count, _ = write(
                    """UPDATE Payment
                       SET Status='Paid'
                       WHERE Payment_ID=%s AND Status='Pending'""",
                    (payment['Payment_ID'],)
                )

                if count != 1:
                    raise ValueError('Payment update failed.')

                # DURABILITY: Permanently save
                conn.commit()
                flash('Payment successful! COMMIT completed.')
                app.logger.info(
                    'PAYMENT COMMIT: Payment_ID=%s',
                    payment['Payment_ID']
                )
            elif f('action')=='status':
                # TRANSACTION SAFETY: SELECT ... FOR UPDATE locks this delivery record
                # until commit/rollback, reducing conflicting status updates.
                d=one('SELECT d.*,s.Status AS Shipment_Status FROM Delivery d JOIN Shipment s ON s.Shipment_ID=d.Shipment_ID WHERE d.Agent_ID=%s AND d.Delivery_ID=%s FOR UPDATE',(aid,num('delivery')))
                if not d or d['Status'] not in STEPS or d['Status']=='Delivered' or d['Shipment_Status'] in ('Delivered','Cancelled'): raise ValueError('Invalid delivery.')
                nxt=STEPS[STEPS.index(d['Status'])+1]
                if d['Shipment_Status'] in STEPS and STEPS.index(nxt)<STEPS.index(d['Shipment_Status']): raise ValueError('Shipment already progressed.')
                # MYSQL UPDATE: Advances the delivery status and records its completion date.
                write("UPDATE Delivery SET Status=%s,Date=IF(%s='Delivered',CURDATE(),Date) WHERE Delivery_ID=%s",(nxt,nxt,d['Delivery_ID']))
                # TRIGGER: Updating Shipment.Status fires MySQL's Update_Tracking trigger
                # when the status actually changes. The trigger adds a Tracking history row.
                # Python does NOT call this trigger or manually insert that history row.
                write('UPDATE Shipment SET Status=%s WHERE Shipment_ID=%s',(nxt,d['Shipment_ID']))
                # MYSQL COMMIT: Saves both delivery and shipment status changes.
                db().commit(); flash('Status updated.')
        except (ValueError,mysql.connector.Error) as e: action_error(e)
        return redirect(url_for('agent'))
    selected=next((a for a in agents if a['Agent_ID']==aid),None)
    # MYSQL SELECT/JOIN: Loads agent deliveries and their payment details.
    deliveries=query('SELECT d.*,c.Tracking_No,s.Source,s.Destination,s.Status AS Shipment_Status,p.Amount,p.Method,p.Status AS Payment_Status FROM Delivery d JOIN Shipment s ON s.Shipment_ID=d.Shipment_ID JOIN Courier c ON c.Courier_ID=s.Courier_ID LEFT JOIN Payment p ON p.Courier_ID=c.Courier_ID WHERE d.Agent_ID=%s ORDER BY d.Delivery_ID DESC',(aid,)) if selected else []
    return render_template('page.html',mode='agent',agents=agents,selected=selected,deliveries=deliveries,steps=STEPS)
@app.route('/customer',methods=['GET','POST'])





# MYSQL SELECT: Retrieves the logged-in customer and available branch cities.
def customer():
    cid=session.get('customer',0)
    # MYSQL SELECT: Finds the current customer record.
    person=one('SELECT * FROM Customer WHERE Customer_ID=%s',(cid,)) if cid else None
    # MYSQL SELECT: Loads branch cities for courier booking.
    cities=[r['City'] for r in query('SELECT DISTINCT City FROM Branch ORDER BY City')]
    estimate=None
    if request.method=='POST':
        try:
            action=f('action')
            if action in ('login','register'):
                if not f('name') or len(f('name'))>50 or not re.fullmatch(r'[0-9]{10}',f('phone')): raise ValueError('Enter a valid name and 10-digit phone.')
                if action=='login':
                    # MYSQL SELECT: Checks customer login details.
                    p=one('SELECT Customer_ID FROM Customer WHERE Name=%s AND Phone=%s',(f('name'),f('phone')))
                    if not p: raise ValueError('Customer not found.')
                    session['customer']=p['Customer_ID']
                else:
                    if not re.fullmatch(r'[^@\s]+@[^@\s]+\.[^@\s]+',f('email')) or len(f('email'))>100: raise ValueError('Enter a valid email.')
                    # MYSQL INSERT + COMMIT: Registers a new customer in MySQL.
                    _,cid=write('INSERT INTO Customer(Name,Phone,Email) VALUES(%s,%s,%s)',(f('name'),f('phone'),f('email'))); db().commit(); session['customer']=cid
                return redirect(url_for('customer'))
            if action=='logout': session.pop('customer',None); return redirect(url_for('customer'))
            if not person: raise ValueError('Login first.')
            if action in ('calculate','book'):
                weight=Decimal(f('weight'))
                if not weight.is_finite() or not Decimal('0.01')<=weight<=Decimal('9999.99') or weight!=weight.quantize(Decimal('.01')) or f('type') not in TYPES: raise ValueError('Invalid weight or type.')
                # STORED FUNCTION: Calculate_Charge(type, weight) runs inside MySQL.
                # Flask only displays the returned charge; it does not calculate the rate.
                if action=='calculate': estimate=one('SELECT Calculate_Charge(%s,%s) AS charge',(f('type'),weight))['charge']
                else:
                    if f('source') not in cities or f('destination') not in cities or f('source')==f('destination') or f('method') not in METHODS: raise ValueError('Choose different valid cities and payment method.')
                    # STORED PROCEDURE: Book_Courier() handles a complete new booking in MySQL.
                    # Inputs: customer ID, courier type, weight, source, destination, payment method.
                    # The SQL procedure inserts Courier, Booking, Shipment, Payment and Tracking.
                    # Its own transaction/exception handler manages those related inserts.
                    with db().cursor(dictionary=True) as c:
                        # callproc executes the procedure; stored_results() reads its tracking number/charge output.
                        c.callproc('Book_Courier',(cid,f('type'),weight,f('source'),f('destination'),f('method')))
                        results=[row for result in c.stored_results() for row in result.fetchall()]
                    # Commit the connection after the call; the supplied procedure already COMMITs internally.
                    db().commit(); flash('Booking successful: '+str(results[0]['Tracking_Number']) if results else 'Booking successful.')
                    return redirect(url_for('customer'))
            elif action=='feedback':
                rating=num('rating'); courier=num('courier')
                if not 1<=rating<=5 or len(f('comments'))>200: raise ValueError('Invalid feedback.')
                # MYSQL SELECT ... FOR UPDATE: Locks the customer booking row during feedback validation.
                booking=one('SELECT Booking_ID FROM Booking WHERE Customer_ID=%s AND Courier_ID=%s FOR UPDATE',(cid,courier))
                if not booking: raise ValueError('Invalid courier.')
                # MYSQL INSERT: Adds feedback only for an eligible delivered courier.
                count,_=write("INSERT INTO Feedback(Customer_ID,Courier_ID,Rating,Comments) SELECT b.Customer_ID,b.Courier_ID,%s,%s FROM Booking b JOIN Shipment s ON s.Courier_ID=b.Courier_ID WHERE b.Customer_ID=%s AND b.Courier_ID=%s AND s.Status='Delivered' AND NOT EXISTS (SELECT 1 FROM Feedback f WHERE f.Customer_ID=b.Customer_ID AND f.Courier_ID=b.Courier_ID)",(rating,f('comments'),cid,courier))
                if not count: raise ValueError('Feedback already submitted or courier not delivered.')
                # MYSQL COMMIT: Saves customer feedback.
                db().commit(); flash('Feedback saved.'); return redirect(url_for('customer'))
        except (ValueError,InvalidOperation,mysql.connector.Error) as e: action_error(e)







        
    # STORED FUNCTION: Estimated_Delivery_Date(booking_date, courier_type)
    # is evaluated by MySQL for each booking returned by this JOIN.
    # Current SQL rules: Express +2 days, Parcel +5, Document +3, others +7.
    # The date is computed on demand; no extra date column or table is needed.
    # Source and destination are shown separately and do not affect this function.
    bookings=query('SELECT c.*,s.Source,s.Destination,s.Status,s.Shipment_ID,b.Date,p.Amount,p.Method,p.Status AS Payment_Status, Estimated_Delivery_Date(b.Date, c.Type) AS Estimated_Delivery_Date FROM Booking b JOIN Courier c ON c.Courier_ID=b.Courier_ID JOIN Shipment s ON s.Courier_ID=c.Courier_ID LEFT JOIN Payment p ON p.Courier_ID=c.Courier_ID WHERE b.Customer_ID=%s ORDER BY b.Booking_ID DESC',(cid,)) if person else []
    selected=request.args.get('tracking',bookings[0]['Tracking_No'] if bookings else '')
    shipment=next((b for b in bookings if b['Tracking_No']==selected),None)
    # TRACKING HISTORY: Read the records inserted at booking and by Update_Tracking.
    history=query('SELECT Location,Status,Date FROM Tracking WHERE Shipment_ID=%s ORDER BY Date DESC,Tracking_ID DESC',(shipment['Shipment_ID'],)) if shipment else []
    # MYSQL SELECT/JOIN: Loads the customer's previous feedback.
    feedback=query('SELECT c.Tracking_No,f.Courier_ID,f.Rating,f.Comments FROM Feedback f JOIN Courier c ON c.Courier_ID=f.Courier_ID WHERE f.Customer_ID=%s',(cid,)) if person else []
    steps=['Booked']+STEPS[1:]; progress=-1
    if shipment:
        status=shipment['Status'].lower(); progress=next((i for i,s in enumerate(steps) if s.lower()==status),0 if status=='assigned' else -1)
    return render_template('page.html',mode='customer',person=person,cities=cities,types=TYPES,methods=METHODS,estimate=estimate,bookings=bookings,shipment=shipment,history=history,steps=steps,progress=progress,feedback=feedback,eligible=[b for b in bookings if b['Status'].lower()=='delivered' and b['Courier_ID'] not in {f['Courier_ID'] for f in feedback}],active=sum(b['Status'].lower()!='delivered' for b in bookings),pending=sum((b['Amount'] or 0) for b in bookings if b['Payment_Status']=='Pending'))
# LOCAL DEVELOPMENT SERVER: Run Flask at http://127.0.0.1:5000.
if __name__=='__main__': app.run(host='127.0.0.1',port=5000,debug=False)

