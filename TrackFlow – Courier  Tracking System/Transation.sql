USE trackflow_db;

START TRANSACTION;

UPDATE Payment
SET Status = 'Pending'
WHERE Payment_ID = 9;

SAVEPOINT sp1;

UPDATE Payment
SET Status = 'Paid'
WHERE Payment_ID = 9;

SELECT * FROM Payment WHERE Payment_ID = 9;

ROLLBACK TO sp1;

SELECT * FROM Payment WHERE Payment_ID = 9;

ROLLBACK;