use trackflow;


-- Function: Calculate_Charge()

SELECT Calculate_Charge('Parcel', 2.5); 


-- Procedure: Book_Courier()

CALL Book_Courier(1, 'Express', 2.0, 'Delhi', 'Pune', 'Card'); 


-- Trigger: updates tracking when status changes

START TRANSACTION;
UPDATE Shipment SET Status = 'Picked Up' WHERE Shipment_ID = 60;
SELECT Location, Status FROM Tracking WHERE Shipment_ID = 60 ORDER BY Date, Tracking_ID;   
ROLLBACK;


-- Cursor: branch-wise report

CALL Branch_Report();


-- 5. Transactions: COMMIT, ROLLBACK, ACID

-- ROLLBACK (cancels the change):

START TRANSACTION;
UPDATE Payment SET Status = 'Paid' WHERE Courier_ID = 72;
SELECT Status FROM Payment WHERE Courier_ID = 72;    -- Paid
ROLLBACK;
SELECT Status FROM Payment WHERE Courier_ID = 72;    -- Pending again


-- COMMIT (saves the change):

START TRANSACTION;
UPDATE Payment SET Status = 'Paid' WHERE Courier_ID = 72;
COMMIT;
SELECT Status FROM Payment WHERE Courier_ID = 72;    -- stays Paid
UPDATE Payment SET Status = 'Pending' WHERE Courier_ID = 72;   -- put it back to normal

-- Atomicity (all steps or none)
START TRANSACTION;
UPDATE Payment SET Status = 'Paid' WHERE Courier_ID = 72;
INSERT INTO Feedback (Customer_ID, Courier_ID, Rating, Comments) VALUES (1, 72, 9, 'demo');

ROLLBACK;
SELECT Status FROM Payment WHERE Courier_ID = 72;    -- still Pending
