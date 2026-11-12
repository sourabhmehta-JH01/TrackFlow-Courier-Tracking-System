
-- ==========================================
-- TRACKFLOW COURIER TRACKING SYSTEM
-- DATABASE AND TABLE CREATION
-- ==========================================

-- 1. Create Database
CREATE DATABASE IF NOT EXISTS trackflow;

-- Select Database
USE trackflow;


-- 2. CUSTOMER TABLE
CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(50)
);


-- 3. BRANCH TABLE
CREATE TABLE Branch (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(50) NOT NULL,
    phone VARCHAR(15)
);


-- 4. EMPLOYEE TABLE
CREATE TABLE Employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    designation VARCHAR(50),
    branch_id INT,

    FOREIGN KEY (branch_id)
    REFERENCES Branch(branch_id)
);


-- 5. DELIVERY AGENT TABLE
CREATE TABLE Delivery_Agent (
    agent_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNIQUE,
    vehicle_number VARCHAR(20),
    availability_status VARCHAR(20) DEFAULT 'Available',

    FOREIGN KEY (employee_id)
    REFERENCES Employee(employee_id)
);


-- 6. COURIER TABLE
CREATE TABLE Courier (
    courier_id INT AUTO_INCREMENT PRIMARY KEY,
    courier_type VARCHAR(50),
    weight DECIMAL(8,2),
    description VARCHAR(255),
    delivery_charge DECIMAL(10,2)
);


-- 7. BOOKING TABLE
CREATE TABLE Booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    courier_id INT NOT NULL,
    source_branch INT NOT NULL,
    destination_branch INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_status VARCHAR(30) DEFAULT 'Booked',

    FOREIGN KEY (customer_id)
    REFERENCES Customer(customer_id),

    FOREIGN KEY (courier_id)
    REFERENCES Courier(courier_id),

    FOREIGN KEY (source_branch)
    REFERENCES Branch(branch_id),

    FOREIGN KEY (destination_branch)
    REFERENCES Branch(branch_id)
);


-- 8. SHIPMENT TABLE
CREATE TABLE Shipment (
    shipment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNIQUE NOT NULL,
    tracking_number VARCHAR(50) UNIQUE NOT NULL,
    current_status VARCHAR(30) DEFAULT 'Booked',
    expected_delivery DATE,

    FOREIGN KEY (booking_id)
    REFERENCES Booking(booking_id)
);


-- 9. TRACKING TABLE
CREATE TABLE Tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL,
    branch_id INT,
    employee_id INT,
    tracking_status VARCHAR(50) NOT NULL,
    tracking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255),

    FOREIGN KEY (shipment_id)
    REFERENCES Shipment(shipment_id),

    FOREIGN KEY (branch_id)
    REFERENCES Branch(branch_id),

    FOREIGN KEY (employee_id)
    REFERENCES Employee(employee_id)
);


-- 10. PAYMENT TABLE
CREATE TABLE Payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(30),
    payment_status VARCHAR(30) DEFAULT 'Pending',

    FOREIGN KEY (booking_id)
    REFERENCES Booking(booking_id)
);


-- 11. DELIVERY TABLE
CREATE TABLE Delivery (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL UNIQUE,
    agent_id INT,
    delivery_date DATETIME,
    delivery_status VARCHAR(30) DEFAULT 'Pending',
    receiver_name VARCHAR(100),

    FOREIGN KEY (shipment_id)
    REFERENCES Shipment(shipment_id),

    FOREIGN KEY (agent_id)
    REFERENCES Delivery_Agent(agent_id)
);


-- 12. FEEDBACK TABLE
CREATE TABLE Feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    shipment_id INT NOT NULL,
    rating INT,
    comments VARCHAR(255),
    feedback_date DATE,

    FOREIGN KEY (customer_id)
    REFERENCES Customer(customer_id),

    FOREIGN KEY (shipment_id)
    REFERENCES Shipment(shipment_id),

    CHECK (rating BETWEEN 1 AND 5)
);


-- ==========================================
-- DATABASE CREATION COMPLETED
-- ==========================================

-- Display all tables
SHOW TABLES;





USE trackflow;

-- ==========================================
-- 1. CUSTOMER: 50 RECORDS
-- ==========================================

INSERT INTO Customer
(customer_name, email, phone, address, city)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    CONCAT('Customer ', n),
    CONCAT('customer', n, '@gmail.com'),
    CONCAT('98', LPAD(n, 8, '0')),
    CONCAT('House No. ', n, ', Main Road'),
    ELT(MOD(n-1,8)+1,
        'Noida','Delhi','Ghaziabad','Lucknow',
        'Jaipur','Mumbai','Pune','Chandigarh')
FROM seq;


-- ==========================================
-- 2. BRANCH: 10 RECORDS
-- ==========================================

INSERT INTO Branch
(branch_name, address, city, phone)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 10
)
SELECT
    CONCAT('Branch ', n),
    CONCAT('Sector ', n, ', Main Road'),
    ELT(n,'Noida','Delhi','Ghaziabad','Lucknow',
        'Jaipur','Mumbai','Pune','Chandigarh',
        'Agra','Kanpur'),
    CONCAT('91', LPAD(n, 8, '0'))
FROM seq;


-- ==========================================
-- 3. EMPLOYEE: 30 RECORDS
-- ==========================================

INSERT INTO Employee
(employee_name, email, phone, designation, branch_id)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 30
)
SELECT
    CONCAT('Employee ', n),
    CONCAT('employee', n, '@trackflow.com'),
    CONCAT('97', LPAD(n, 8, '0')),
    ELT(MOD(n-1,4)+1,
        'Manager','Clerk','Supervisor','Operator'),
    MOD(n-1,10)+1
FROM seq;


-- ==========================================
-- 4. DELIVERY AGENT: 20 RECORDS
-- ==========================================

INSERT INTO Delivery_Agent
(employee_id, vehicle_number, availability_status)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 20
)
SELECT
    n,
    CONCAT('UP', LPAD(MOD(n-1,99)+1,2,'0'),
           'AB', LPAD(n,4,'0')),
    ELT(MOD(n-1,3)+1,
        'Available','Busy','Available')
FROM seq;


-- ==========================================
-- 5. COURIER: 50 RECORDS
-- ==========================================

INSERT INTO Courier
(courier_type, weight, description, delivery_charge)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    ELT(MOD(n-1,5)+1,
        'Documents','Electronics','Clothing',
        'Fragile','Express'),
    ROUND(0.5 + MOD(n,20)*0.5,2),
    CONCAT('Courier package number ', n),
    ROUND(50 + MOD(n,20)*25,2)
FROM seq;


-- ==========================================
-- 6. BOOKING: 50 RECORDS
-- ==========================================

INSERT INTO Booking
(customer_id, courier_id, source_branch,
 destination_branch, booking_date, booking_status)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    n,
    n,
    MOD(n-1,10)+1,
    MOD(n+2,10)+1,
    DATE_SUB(CURDATE(), INTERVAL n DAY),
    ELT(MOD(n-1,4)+1,
        'Booked','Shipped','Delivered','Cancelled')
FROM seq;


-- ==========================================
-- 7. SHIPMENT: 50 RECORDS
-- ==========================================

INSERT INTO Shipment
(booking_id, tracking_number, current_status,
 expected_delivery)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    n,
    CONCAT('TRK2026', LPAD(n,5,'0')),
    ELT(MOD(n-1,5)+1,
        'Booked','Shipped','In Transit',
        'Delivered','Cancelled'),
    DATE_ADD(
        DATE_SUB(CURDATE(), INTERVAL n DAY),
        INTERVAL 5 DAY
    )
FROM seq;


-- ==========================================
-- 8. TRACKING: 100 RECORDS
-- ==========================================

INSERT INTO Tracking
(shipment_id, branch_id, employee_id,
 tracking_status, tracking_date, remarks)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    CEIL(n/2),
    MOD(n-1,10)+1,
    MOD(n-1,30)+1,
    ELT(MOD(n-1,5)+1,
        'Booked','Picked Up','In Transit',
        'Arrived at Branch','Out for Delivery'),
    DATE_SUB(NOW(), INTERVAL n HOUR),
    CONCAT('Tracking update number ', n)
FROM seq;


-- ==========================================
-- 9. PAYMENT: 50 RECORDS
-- ==========================================

INSERT INTO Payment
(booking_id, amount, payment_date,
 payment_method, payment_status)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    n,
    100 + MOD(n,20)*50,
    DATE_SUB(NOW(), INTERVAL n DAY),
    ELT(MOD(n-1,4)+1,
        'Cash','UPI','Card','Net Banking'),
    ELT(MOD(n-1,3)+1,
        'Paid','Pending','Paid')
FROM seq;


-- ==========================================
-- 10. DELIVERY: 50 RECORDS
-- ==========================================

INSERT INTO Delivery
(shipment_id, agent_id, delivery_date,
 delivery_status, receiver_name)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 50
)
SELECT
    n,
    MOD(n-1,20)+1,
    DATE_SUB(NOW(), INTERVAL MOD(n,10) DAY),
    ELT(MOD(n-1,3)+1,
        'Delivered','Pending','Delivered'),
    CONCAT('Receiver ', n)
FROM seq;


-- ==========================================
-- 11. FEEDBACK: 40 RECORDS
-- ==========================================

INSERT INTO Feedback
(customer_id, shipment_id, rating,
 comments, feedback_date)

WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 40
)
SELECT
    n,
    n,
    MOD(n-1,5)+1,
    ELT(MOD(n-1,5)+1,
        'Excellent service',
        'Good delivery',
        'Average experience',
        'Late delivery',
        'Very good experience'),
    DATE_SUB(CURDATE(), INTERVAL n DAY)
FROM seq;


-- ==========================================
-- VERIFY ALL TABLES
-- ==========================================

SELECT 'Customer' AS Table_Name, COUNT(*) AS Total
FROM Customer
UNION ALL
SELECT 'Branch', COUNT(*) FROM Branch
UNION ALL
SELECT 'Employee', COUNT(*) FROM Employee
UNION ALL
SELECT 'Delivery_Agent', COUNT(*) FROM Delivery_Agent
UNION ALL
SELECT 'Courier', COUNT(*) FROM Courier
UNION ALL
SELECT 'Booking', COUNT(*) FROM Booking
UNION ALL
SELECT 'Shipment', COUNT(*) FROM Shipment
UNION ALL
SELECT 'Tracking', COUNT(*) FROM Tracking
UNION ALL
SELECT 'Payment', COUNT(*) FROM Payment
UNION ALL
SELECT 'Delivery', COUNT(*) FROM Delivery
UNION ALL
SELECT 'Feedback', COUNT(*) FROM Feedback;

