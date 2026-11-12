# TrackFlow – Courier Tracking System

## 1. Project Overview

TrackFlow is a DBMS-based Courier Tracking System developed using PHP and MySQL. It manages courier bookings, shipments, customers, employees, delivery agents, payments, tracking history, and customer feedback.

The system provides a centralized platform to manage courier operations and monitor shipment progress.

## 2. Technologies Used

- Frontend: HTML, CSS
- Backend: PHP
- Database: MySQL
- Database Management Tool: MySQL Workbench
- Local Server: XAMPP

## 3. Main Features

- Customer Registration
- Courier Booking
- Shipment Creation
- Shipment Tracking
- Employee Management
- Delivery Agent Management
- Shipment Status Updates
- Payment Management
- Customer Feedback and Ratings
- Reports and Analytics
- Dashboard

## 4. Database Tables

The project uses the following 11 tables:

1. Customer
2. Courier
3. Branch
4. Booking
5. Shipment
6. Payment
7. Feedback
8. Delivery_Agent
9. Employee
10. Tracking
11. Delivery

## 5. Advanced SQL Concepts

The project demonstrates:

- SQL Joins
- Aggregate Functions
- Subqueries
- Stored Procedures
- User-defined Functions
- Triggers
- Cursors
- Transactions (COMMIT and ROLLBACK)

## 6. Project Structure

```text
TrackFlow-Courier-Tracking-System/
│
├── DBMS/
│   ├── Database Creation
│   ├── Sample Data
│   ├── SQL Reports
│   ├── Functions and Procedures
│   └── MySQL Workbench File
│
├── TrackFlow/
│   ├── PHP Files
│   └── style.css
│
└── README.md
```

## 7. Installation and Setup

1. Install XAMPP and MySQL.
2. Start Apache and MySQL.
3. Create the TrackFlow database by executing the database creation SQL file.
4. Execute the SQL file containing stored procedures, functions, triggers, and cursors.
5. Configure your local database connection in `TrackFlow/config.php`.
6. Place the project folder inside the XAMPP `htdocs` directory.
7. Open the application in your browser:

```text
http://localhost/TrackFlow/
```

## 8. Team Project

TrackFlow is developed as an academic DBMS project to demonstrate database design, SQL concepts, and PHP-MySQL integration.

---

**Project Name:** TrackFlow – Courier Tracking System  
**Database:** MySQL  
**Backend:** PHP
