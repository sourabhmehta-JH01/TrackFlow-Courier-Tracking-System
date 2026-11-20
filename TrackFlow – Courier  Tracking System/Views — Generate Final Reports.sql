
-- View 1: Customer and booking details
CREATE VIEW Booking_Report AS
SELECT
    b.Booking_ID,
    c.Name AS Customer_Name,
    co.Tracking_No,
    co.Type,
    co.Weight,
    co.Charges,
    b.Date
FROM Booking b
JOIN Customer c
ON b.Customer_ID = c.Customer_ID
JOIN Courier co
ON b.Courier_ID = co.Courier_ID;

SELECT * FROM Booking_Report;




-- View 2: Shipment status report

CREATE VIEW Shipment_Report AS
SELECT
    c.Tracking_No,
    s.Source,
    s.Destination,
    s.Status
FROM Shipment s
JOIN Courier c
ON s.Courier_ID = c.Courier_ID;

SELECT * FROM Shipment_Report;

-- Delivered shipments:

SELECT * FROM Shipment_Report
WHERE Status = 'Delivered';


-- Pending shipments:
SELECT * FROM Shipment_Report
WHERE Status NOT IN ('Delivered', 'Cancelled');


-- In-transit shipments:
SELECT * FROM Shipment_Report
WHERE Status = 'In Transit';


-- View 3: Payment and revenue report

CREATE VIEW Payment_Report AS
SELECT
    c.Tracking_No,
    p.Amount,
    p.Method,
    p.Status
FROM Payment p
JOIN Courier c
ON p.Courier_ID = c.Courier_ID;

SELECT * FROM Payment_Report;

-- Total revenue:

SELECT SUM(Amount) AS Total_Revenue
FROM Payment_Report
WHERE Status = 'Paid';


-- View 4: Agent performance report
CREATE VIEW Agent_Report AS
SELECT
    a.Agent_ID,
    a.Name AS Agent_Name,
    COUNT(d.Delivery_ID) AS Total_Deliveries,
    COUNT(
        CASE WHEN d.Status = 'Delivered' THEN 1 END
    ) AS Completed_Deliveries
FROM Delivery_Agent a
LEFT JOIN Delivery d
ON a.Agent_ID = d.Agent_ID
GROUP BY a.Agent_ID, a.Name;

SELECT * FROM Agent_Report;


-- View 5: Branch performance report

CREATE VIEW Branch_Report AS
SELECT
    b.Branch_ID,
    b.Branch_Name,
    COUNT(s.Shipment_ID) AS Total_Shipments,
    COUNT(
        CASE WHEN s.Status = 'Delivered' THEN 1 END
    ) AS Delivered_Shipments
FROM Branch b
LEFT JOIN Shipment s
ON b.City = s.Source
GROUP BY b.Branch_ID, b.Branch_Name;


SELECT * FROM Branch_Report;

-- View 6: Feedback and rating report
CREATE VIEW Feedback_Report AS
SELECT
    c.Name AS Customer_Name,
    co.Tracking_No,
    f.Rating,
    f.Comments
FROM Feedback f
JOIN Customer c
ON f.Customer_ID = c.Customer_ID
JOIN Courier co
ON f.Courier_ID = co.Courier_ID;

SELECT * FROM Feedback_Report;



-- to generate complete tracking history 

CREATE VIEW Tracking_Report AS
SELECT
    c.Tracking_No,
    t.Location,
    t.Status,
    t.Date
FROM Tracking t
JOIN Shipment s
ON t.Shipment_ID = s.Shipment_ID
JOIN Courier c
ON s.Courier_ID = c.Courier_ID;


SELECT * FROM Tracking_Report
WHERE Tracking_No = 'TF1003'
ORDER BY Date;

