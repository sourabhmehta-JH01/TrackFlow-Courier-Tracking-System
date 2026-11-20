# TrackFlow – Courier Management System

## Project Overview

TrackFlow is a database management system designed to manage courier bookings, shipments, tracking, payments, delivery agents, branches and customer feedback using MySQL.

The project demonstrates relational database design and advanced SQL concepts through practical courier management operations and analytical reports. It can be executed directly in MySQL Workbench without a frontend.

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

Database name: `trackflow_db`

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

![TrackFlow Entity Relationship Diagram](./ER2.svg)

## Implemented DBMS Concepts

- DDL and DML commands
- Primary keys and foreign keys
- NOT NULL, UNIQUE, CHECK and DEFAULT constraints
- Aggregate functions and GROUP BY
- JOINs and subqueries
- Stored functions and stored procedures
- Triggers and views

### Stored Functions

| Function | Purpose |
|---|---|
| `Calculate_Charge()` | Calculates courier charges |
| `Estimated_Delivery_Date()` | Estimates delivery dates using sample service rules |
| `Delivery_Days()` | Calculates actual delivery duration |
| `Calculate_Discount()` | Calculates a discount for eligible regular customers |
| `Agent_Performance()` | Calculates the percentage of completed assigned deliveries |

### Stored Procedure

`Book_Courier()` automates courier booking by creating the related courier, booking, shipment, payment and initial tracking records.

### Trigger

`Update_Tracking` automatically adds a tracking history record when the shipment status changes.

### Views

- Customer booking report
- Shipment status report
- Payment report
- Delivery agent performance report
- Branch performance report
- Customer feedback report
- Shipment tracking history report

### Analytical Queries

| Concept | Analysis |
|---|---|
| JOIN | City-wise paid revenue |
| JOIN | Customer-wise total spending |
| Subquery | Agents without assigned deliveries |
| Subquery | Delivered couriers without feedback |

## Project Structure

```text
TrackFlow-DBMS/
├── 01_Create_Database.sql
├── 02_Create_Tables.sql
├── 03_Insert_Data.sql
├── 04_Stored_Functions.sql
├── 05_Stored_Procedures.sql
├── 06_Triggers.sql
├── 07_Views.sql
├── 08_Joins.sql
├── 09_Subqueries.sql
├── ER.svg
└── README.md
```

## How to Run

1. Install MySQL Server and MySQL Workbench.
2. Clone or download this repository from GitHub.
3. Open MySQL Workbench and connect to the local MySQL server.
4. Open and execute the SQL files in numerical order, starting with `01_Create_Database.sql`.
5. Select the database:

   ```sql
   USE trackflow_db;
   ```

6. Execute the functions, reports and analytical queries to explore the project.

Run the database initialization and sample data scripts once on a fresh database to avoid duplicate records.

## Sample Queries

```sql
SELECT Calculate_Charge('Parcel', 2);
SELECT Agent_Performance(1);
SELECT * FROM Booking_Report;
SELECT * FROM Shipment_Report;
SELECT * FROM Tracking_Report;
```

## Limitations

- The system is database-only and does not include a graphical frontend.
- Tracking locations are recorded or inferred from shipment data; there is no live GPS tracking.
- Delivery estimates and discounts use sample business rules.
- Payment records do not represent integration with an online payment gateway.
- Branch shipment reports associate shipments with branches through the source city.

## Academic Information

- **Course:** Master of Computer Applications (MCA)
- **Semester:** First Semester
- **Subject:** Database Management Systems (DBMS)
- **Institute:** Jaypee Institute of Information Technology, Noida

## Conclusion

TrackFlow demonstrates how relational databases and advanced SQL concepts can be used to manage courier operations, maintain consistent records and generate useful business insights.
