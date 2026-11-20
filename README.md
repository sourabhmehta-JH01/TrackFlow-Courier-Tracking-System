# TrackFlow – Courier Management System

## Project Overview

TrackFlow is a database management system designed to manage courier bookings, shipments, tracking, payments, delivery agents, branches and customer feedback using MySQL.

The project demonstrates relational database design and advanced SQL concepts through practical courier management operations and analytical reports.

## Objectives

- Manage customer and courier booking records.
- Calculate courier charges automatically.
- Maintain shipment status and tracking history.
- Manage delivery agents, employees and branches.
- Maintain payment and customer feedback records.
- Generate analytical reports for business decision-making.

## Technologies Used

- MySQL
- MySQL Workbench
- SQL
- Git and GitHub

## Database Structure

**Database Name:** `trackflow_db`

| Table | Purpose |
|---|---|
| Customer | Stores customer details |
| Booking | Stores courier booking details |
| Courier | Stores parcel details and charges |
| Shipment | Maintains shipment information and status |
| Tracking | Maintains shipment tracking history |
| Branch | Stores branch information |
| Delivery_Agent | Stores delivery agent details |
| Delivery | Maintains delivery assignments and status |
| Payment | Stores payment details |
| Feedback | Stores customer ratings and comments |
| Employee | Stores employee details |

## Entity Relationship Diagram (ERD)

![TrackFlow ER Diagram](./TrackFlow%20%E2%80%93%20Courier%20Tracking%20System/ER2.svg)

## Implemented DBMS Concepts

- DDL and DML commands
- Primary keys and foreign keys
- NOT NULL, UNIQUE, CHECK and DEFAULT constraints
- Aggregate functions and GROUP BY
- JOINs and subqueries
- Stored functions
- Stored procedures
- Triggers
- Views

## Stored Functions

| Function | Purpose |
|---|---|
| Calculate_Charge() | Calculates courier charges |
| Estimated_Delivery_Date() | Estimates expected delivery date |
| Delivery_Days() | Calculates actual delivery duration |
| Calculate_Discount() | Calculates discounts for regular customers |
| Agent_Performance() | Calculates agent delivery completion percentage |

## Stored Procedure

### Book_Courier()

Automates the courier booking process by creating related records in the following tables:

- Courier
- Booking
- Shipment
- Payment
- Tracking

## Trigger

### Update_Tracking

Automatically inserts a tracking history record whenever the shipment status changes.

## Views

The project uses views to generate the following reports:

- Customer booking report
- Shipment status report
- Payment report
- Delivery agent performance report
- Branch performance report
- Customer feedback report
- Complete shipment tracking history

## Analytical Queries

JOINs and subqueries are used to extract meaningful information from existing records and support business decision-making.

| Concept | Analytical Task |
|---|---|
| JOIN | City-wise paid revenue |
| JOIN | Customer-wise total spending |
| Subquery | Agents without assigned deliveries |
| Subquery | Delivered couriers without feedback |

## Project Structure

```text
Repository/
├── TrackFlow-Courier-Tracking-System/
│   ├── trackflow_db.sql
│   ├── data_insertion.sql
│   ├── Stored Function — Calculate Courier Charges.sql
│   ├── Stored Procedure — Book Courier.sql
│   ├── Trigger — Automatic Tracking Update.sql
│   ├── Views — Generate Final Reports.sql
│   ├── JOINS & Subquerry generate meaningful analytical reports.sql
│   └── ER2.svg
└── README.md
```

## How to Run

### Prerequisites

- MySQL Server
- MySQL Workbench

### Execution Steps

1. Clone or download this repository.
2. Open MySQL Workbench.
3. Connect to your local MySQL server.
4. Execute the SQL files in the following order:

| Order | File |
|---|---|
| 1 | trackflow_db.sql |
| 2 | data_insertion.sql |
| 3 | Stored Function — Calculate Courier Charges.sql |
| 4 | Stored Procedure — Book Courier.sql |
| 5 | Trigger — Automatic Tracking Update.sql |
| 6 | Views — Generate Final Reports.sql |
| 7 | JOINS & Subquerry generate meaningful analytical reports.sql |

5. Select the database:

```sql
USE trackflow_db;
```

6. Execute the functions, views and analytical queries.

Run the database creation and sample data scripts once on a fresh database to avoid duplicate records.

## Sample Queries

### Calculate Courier Charges

```sql
SELECT Calculate_Charge('Parcel', 2);
```

### Agent Performance

```sql
SELECT Agent_Performance(1);
```

### Customer Booking Report

```sql
SELECT * FROM Booking_Report;
```

### Shipment Status Report

```sql
SELECT * FROM Shipment_Report;
```

### Complete Tracking History

```sql
SELECT * FROM Tracking_Report;
```

## Project Highlights

- Relational database containing 11 tables.
- Automated courier charge calculation.
- Automated courier booking process.
- Automatic shipment tracking updates.
- Data integrity using database constraints.
- Business reports using views.
- Analytical queries using JOINs and subqueries.

## Limitations

- The project is database-only and does not include a graphical frontend.
- No real-time GPS tracking.
- No online payment gateway integration.
- Delivery estimates and discounts use predefined sample business rules.
- Branch shipment reports associate shipments with branches through the source city.

## Academic Information

| Details | Information |
|---|---|
| Course | Master of Computer Applications (MCA) |
| Semester | First Semester |
| Subject | Database Management Systems (DBMS) |
| Institute | Jaypee Institute of Information Technology, Noida |
| Academic Year | 2026–2027 |

## Conclusion

TrackFlow demonstrates how relational databases and advanced SQL concepts can be used to manage courier operations, maintain data integrity, automate database operations and generate meaningful analytical reports.
