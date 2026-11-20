DELIMITER //

CREATE PROCEDURE Book_Courier(
    IN customer INT,
    IN courier_type VARCHAR(30),
    IN weight DECIMAL(6,2),
    IN source_city VARCHAR(50),
    IN destination_city VARCHAR(50),
    IN payment_method VARCHAR(30)
)
BEGIN
    DECLARE cid INT;
    DECLARE sid INT;
    DECLARE charge DECIMAL(10,2);
    DECLARE tracking VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Calculate charges
    SET charge = Calculate_Charge(courier_type, weight);

    -- Generate tracking number
    SET tracking = CONCAT('TF', UUID_SHORT());

    -- Insert courier details
    INSERT INTO Courier (Tracking_No, Weight, Type, Charges)
    VALUES (tracking, weight, courier_type, charge);

    SET cid = LAST_INSERT_ID();

    -- Insert booking details
    INSERT INTO Booking (Customer_ID, Courier_ID, Date)
    VALUES (customer, cid, CURDATE());

    -- Insert shipment details
    INSERT INTO Shipment (Courier_ID, Source, Destination, Status)
    VALUES (cid, source_city, destination_city, 'Booked');

    SET sid = LAST_INSERT_ID();

    -- Insert payment details
    INSERT INTO Payment (Courier_ID, Amount, Method, Status)
    VALUES (cid, charge, payment_method, 'Pending');

    -- Insert tracking details
    INSERT INTO Tracking (Shipment_ID, Location, Status)
    VALUES (sid, source_city, 'Booked');

    COMMIT;

    SELECT tracking AS Tracking_Number,
           charge AS Charges;

END //

DELIMITER ;


-- Testing
ALTER TABLE Courier
MODIFY Tracking_No VARCHAR(30) NOT NULL;


CALL Book_Courier(
    1,
    'Parcel',
    2,
    'Ranchi',
    'Delhi',
    'Cash'
);



SELECT * FROM Courier;
SELECT * FROM Booking;
SELECT * FROM Shipment;
SELECT * FROM Payment;
SELECT * FROM Tracking;