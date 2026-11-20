USE trackflow_db;

-- 1. Insert Customer Data

INSERT INTO Customer (Customer_ID, Name, Phone, Email)
VALUES
(1, 'Rahul Kumar', '9000000001', 'rahul@example.com'),
(2, 'Priya Singh', '9000000002', 'priya@example.com'),
(3, 'Amit Sharma', '9000000003', 'amit@example.com'),
(4, 'Neha Verma', '9000000004', 'neha@example.com'),
(5, 'Rohit Gupta', '9000000005', 'rohit@example.com'),
(6, 'Anjali Kumari', '9000000006', 'anjali@example.com'),
(7, 'Vikash Kumar', '9000000007', 'vikash@example.com'),
(8, 'Pooja Singh', '9000000008', 'pooja@example.com'),
(9, 'Karan Mehta', '9000000009', 'karan@example.com'),
(10, 'Sneha Sharma', '9000000010', 'sneha@example.com');


-- 2. Insert Branch Data

INSERT INTO Branch (Branch_ID, Branch_Name, City, Address)
VALUES
(1, 'Ranchi Branch', 'Ranchi', 'Main Road'),
(2, 'Noida Branch', 'Noida', 'Sector 62'),
(3, 'Delhi Branch', 'Delhi', 'Connaught Place'),
(4, 'Mumbai Branch', 'Mumbai', 'Andheri'),
(5, 'Kolkata Branch', 'Kolkata', 'Salt Lake');


-- 3. Insert Employee Data

INSERT INTO Employee (Employee_ID, Name, Role, Branch_ID)
VALUES
(1, 'Rajesh Kumar', 'Manager', 1),
(2, 'Suman Singh', 'Staff', 1),
(3, 'Manish Gupta', 'Manager', 2),
(4, 'Riya Sharma', 'Staff', 2),
(5, 'Deepak Kumar', 'Manager', 3),
(6, 'Nisha Verma', 'Staff', 3),
(7, 'Arjun Singh', 'Manager', 4),
(8, 'Kavita Gupta', 'Staff', 4),
(9, 'Ankit Das', 'Manager', 5),
(10, 'Simran Das', 'Staff', 5);


-- 4. Insert Delivery Agent Data

INSERT INTO Delivery_Agent (Agent_ID, Name, Phone, Branch_ID)
VALUES
(1, 'Ajay Kumar', '9100000001', 1),
(2, 'Vijay Kumar', '9100000002', 1),
(3, 'Ramesh Singh', '9100000003', 2),
(4, 'Suresh Singh', '9100000004', 2),
(5, 'Mohit Sharma', '9100000005', 3),
(6, 'Sumit Sharma', '9100000006', 3),
(7, 'Akash Gupta', '9100000007', 4),
(8, 'Vishal Gupta', '9100000008', 4),
(9, 'Rahul Das', '9100000009', 5),
(10, 'Rohan Das', '9100000010', 5);


-- 5. Insert Courier Data

INSERT INTO Courier (Courier_ID, Tracking_No, Weight, Type, Charges)
VALUES
(1, 'TF1001', 1.00, 'Document', 60),
(2, 'TF1002', 2.00, 'Parcel', 150),
(3, 'TF1003', 3.00, 'Express', 370),
(4, 'TF1004', 2.00, 'Fragile', 220),
(5, 'TF1005', 4.00, 'Parcel', 240),
(6, 'TF1006', 1.00, 'Express', 190),
(7, 'TF1007', 2.00, 'Document', 80),
(8, 'TF1008', 5.00, 'Parcel', 285),
(9, 'TF1009', 3.00, 'Fragile', 290),
(10, 'TF1010', 2.00, 'Express', 280);


-- 6. Insert Booking Data

INSERT INTO Booking (Booking_ID, Customer_ID, Courier_ID, Date)
VALUES
(1, 1, 1, '2026-11-01'),
(2, 2, 2, '2026-11-02'),
(3, 3, 3, '2026-11-03'),
(4, 4, 4, '2026-11-04'),
(5, 5, 5, '2026-11-05'),
(6, 6, 6, '2026-11-06'),
(7, 7, 7, '2026-11-07'),
(8, 8, 8, '2026-11-08'),
(9, 9, 9, '2026-11-09'),
(10, 10, 10, '2026-11-10');


-- 7. Insert Shipment Data

INSERT INTO Shipment (Shipment_ID, Courier_ID, Source, Destination, Status)
VALUES
(1, 1, 'Ranchi', 'Delhi', 'Delivered'),
(2, 2, 'Noida', 'Mumbai', 'Delivered'),
(3, 3, 'Delhi', 'Kolkata', 'In Transit'),
(4, 4, 'Mumbai', 'Ranchi', 'Delivered'),
(5, 5, 'Kolkata', 'Noida', 'Booked'),
(6, 6, 'Ranchi', 'Mumbai', 'In Transit'),
(7, 7, 'Noida', 'Delhi', 'Delivered'),
(8, 8, 'Delhi', 'Ranchi', 'Out for Delivery'),
(9, 9, 'Mumbai', 'Kolkata', 'Picked Up'),
(10, 10, 'Kolkata', 'Delhi', 'Booked');


-- 8. Insert Tracking Data

INSERT INTO Tracking (Shipment_ID, Location, Status, Date)
VALUES
(1, 'Ranchi', 'Booked', '2026-11-01'),
(1, 'Ranchi', 'Picked Up', '2026-11-02'),
(1, 'Delhi', 'In Transit', '2026-11-03'),
(1, 'Delhi', 'Out for Delivery', '2026-11-04 09:00:00'),
(1, 'Delhi', 'Delivered', '2026-11-04 15:00:00'),

(2, 'Noida', 'Booked', '2026-11-02'),
(2, 'Mumbai', 'Delivered', '2026-11-06'),

(3, 'Delhi', 'Booked', '2026-11-03'),
(3, 'Delhi', 'In Transit', '2026-11-07'),

(4, 'Mumbai', 'Booked', '2026-11-04'),
(4, 'Ranchi', 'Delivered', '2026-11-08'),

(5, 'Kolkata', 'Booked', '2026-11-05'),

(6, 'Ranchi', 'Booked', '2026-11-06'),
(6, 'Ranchi', 'In Transit', '2026-11-09'),

(7, 'Noida', 'Booked', '2026-11-07'),
(7, 'Delhi', 'Delivered', '2026-11-09'),

(8, 'Delhi', 'Booked', '2026-11-08'),
(8, 'Ranchi', 'Out for Delivery', '2026-11-11'),

(9, 'Mumbai', 'Booked', '2026-11-09'),
(9, 'Mumbai', 'Picked Up', '2026-11-10'),

(10, 'Kolkata', 'Booked', '2026-11-10');


-- 9. Insert Delivery Data

INSERT INTO Delivery (Delivery_ID, Shipment_ID, Agent_ID, Date, Status)
VALUES
(1, 1, 1, '2026-11-04', 'Delivered'),
(2, 2, 3, '2026-11-06', 'Delivered'),
(3, 3, 5, NULL, 'In Transit'),
(4, 4, 7, '2026-11-08', 'Delivered'),
(5, 6, 2, NULL, 'In Transit'),
(6, 7, 4, '2026-11-09', 'Delivered'),
(7, 8, 6, NULL, 'Out for Delivery'),
(8, 9, 8, NULL, 'Picked Up');


-- 10. Insert Payment Data

INSERT INTO Payment (Payment_ID, Courier_ID, Amount, Method, Status)
VALUES
(1, 1, 60, 'Cash', 'Paid'),
(2, 2, 150, 'UPI', 'Paid'),
(3, 3, 370, 'Card', 'Pending'),
(4, 4, 220, 'UPI', 'Paid'),
(5, 5, 240, 'Cash', 'Pending'),
(6, 6, 190, 'UPI', 'Paid'),
(7, 7, 80, 'Cash', 'Paid'),
(8, 8, 285, 'Card', 'Pending'),
(9, 9, 290, 'Cash', 'Pending'),
(10, 10, 280, 'UPI', 'Pending');


-- 11. Insert Feedback Data

INSERT INTO Feedback (Feedback_ID, Customer_ID, Courier_ID, Rating, Comments)
VALUES
(1, 1, 1, 5, 'Excellent service'),
(2, 2, 2, 4, 'Good delivery'),
(3, 4, 4, 2, 'Late delivery'),
(4, 7, 7, 5, 'Very fast delivery');