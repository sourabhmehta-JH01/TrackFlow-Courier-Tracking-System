CREATE DATABASE trackflow_db;

USE trackflow_db;

-- 1. Customer Table
CREATE TABLE Customer (
    Customer_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Phone VARCHAR(10) NOT NULL UNIQUE,
    Email VARCHAR(100) UNIQUE
);

-- 2. Branch Table
CREATE TABLE Branch (
    Branch_ID INT PRIMARY KEY AUTO_INCREMENT,
    Branch_Name VARCHAR(50) NOT NULL,
    City VARCHAR(50) NOT NULL,
    Address VARCHAR(100)
);

-- 3. Employee Table
CREATE TABLE Employee (
    Employee_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Role VARCHAR(50),
    Branch_ID INT NOT NULL,
    FOREIGN KEY (Branch_ID) REFERENCES Branch(Branch_ID)
);

-- 4. Delivery Agent Table
CREATE TABLE Delivery_Agent (
    Agent_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Phone VARCHAR(10) NOT NULL UNIQUE,
    Branch_ID INT NOT NULL,
    FOREIGN KEY (Branch_ID) REFERENCES Branch(Branch_ID)
);

-- 5. Courier Table
CREATE TABLE Courier (
    Courier_ID INT PRIMARY KEY AUTO_INCREMENT,
    Tracking_No VARCHAR(20) NOT NULL UNIQUE,
    Weight DECIMAL(6,2) NOT NULL CHECK (Weight > 0),
    Type VARCHAR(30) NOT NULL,
    Charges DECIMAL(10,2) NOT NULL
);

-- 6. Booking Table
CREATE TABLE Booking (
    Booking_ID INT PRIMARY KEY AUTO_INCREMENT,
    Customer_ID INT NOT NULL,
    Courier_ID INT NOT NULL UNIQUE,
    Date DATE NOT NULL,
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
    FOREIGN KEY (Courier_ID) REFERENCES Courier(Courier_ID)
);

-- 7. Shipment Table
CREATE TABLE Shipment (
    Shipment_ID INT PRIMARY KEY AUTO_INCREMENT,
    Courier_ID INT NOT NULL UNIQUE,
    Source VARCHAR(50) NOT NULL,
    Destination VARCHAR(50) NOT NULL,
    Status VARCHAR(30) DEFAULT 'Booked',
    FOREIGN KEY (Courier_ID) REFERENCES Courier(Courier_ID)
);

-- 8. Tracking Table
CREATE TABLE Tracking (
    Tracking_ID INT PRIMARY KEY AUTO_INCREMENT,
    Shipment_ID INT NOT NULL,
    Location VARCHAR(50),
    Status VARCHAR(30) NOT NULL,
    Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Shipment_ID) REFERENCES Shipment(Shipment_ID)
);

-- 9. Delivery Table
CREATE TABLE Delivery (
    Delivery_ID INT PRIMARY KEY AUTO_INCREMENT,
    Shipment_ID INT NOT NULL UNIQUE,
    Agent_ID INT NOT NULL,
    Date DATE,
    Status VARCHAR(30) DEFAULT 'Assigned',
    FOREIGN KEY (Shipment_ID) REFERENCES Shipment(Shipment_ID),
    FOREIGN KEY (Agent_ID) REFERENCES Delivery_Agent(Agent_ID)
);

-- 10. Payment Table
CREATE TABLE Payment (
    Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
    Courier_ID INT NOT NULL UNIQUE,
    Amount DECIMAL(10,2) NOT NULL,
    Method VARCHAR(30),
    Status VARCHAR(20) DEFAULT 'Pending',
    FOREIGN KEY (Courier_ID) REFERENCES Courier(Courier_ID)
);

-- 11. Feedback Table
CREATE TABLE Feedback (
    Feedback_ID INT PRIMARY KEY AUTO_INCREMENT,
    Customer_ID INT NOT NULL,
    Courier_ID INT NOT NULL,
    Rating INT NOT NULL CHECK (Rating BETWEEN 1 AND 5),
    Comments VARCHAR(200),
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
    FOREIGN KEY (Courier_ID) REFERENCES Courier(Courier_ID)
);

