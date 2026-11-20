-- Purpose: Whenever a shipment status changes, automatically insert a new tracking record.

DELIMITER //

CREATE TRIGGER Update_Tracking
AFTER UPDATE ON Shipment
FOR EACH ROW
BEGIN
    IF NEW.Status <> OLD.Status THEN

        INSERT INTO Tracking (Shipment_ID, Location, Status)
        VALUES (
            NEW.Shipment_ID,
            CASE
                WHEN NEW.Status IN ('Delivered', 'Out for Delivery')
                THEN NEW.Destination
                ELSE NEW.Source
            END,
            NEW.Status
        );

    END IF;
END //

DELIMITER ;

select * from shipment;

UPDATE Shipment
SET Status = 'delivered'
WHERE Shipment_ID = 9;

SELECT * FROM Tracking
WHERE Shipment_ID = 9;