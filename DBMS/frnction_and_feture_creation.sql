USE TrackFlow;
DELIMITER $$

-- 1. FUNCTION: returns the charge for a type and weight
CREATE FUNCTION Calculate_Charge(p_type VARCHAR(20), p_weight DECIMAL(6,2))
RETURNS DECIMAL(8,2)
DETERMINISTIC
BEGIN
    RETURN CASE p_type
        WHEN 'Document' THEN 40  + 20 * p_weight
        WHEN 'Parcel'   THEN 60  + 45 * p_weight
        WHEN 'Fragile'  THEN 80  + 70 * p_weight
        WHEN 'Express'  THEN 100 + 90 * p_weight
    END;
END$$


-- 2. PROCEDURE: books a courier (all inserts in one transaction)
CREATE PROCEDURE Book_Courier(
    IN p_customer INT,
    IN p_type     VARCHAR(20),
    IN p_weight   DECIMAL(6,2),
    IN p_source   VARCHAR(60),
    IN p_dest     VARCHAR(60),
    IN p_method   VARCHAR(20)
)
BEGIN
    DECLARE v_charge   DECIMAL(8,2);
    DECLARE v_tracking VARCHAR(20);
    DECLARE v_courier  INT;
    DECLARE v_shipment INT;

    -- on any error, undo everything
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET v_charge   = Calculate_Charge(p_type, p_weight);
    SET v_tracking = CONCAT('TF', 1001 + (SELECT MAX(Courier_ID) FROM Courier));

    START TRANSACTION;

    INSERT INTO Courier (Tracking_No, Weight, Type, Charges)
    VALUES (v_tracking, p_weight, p_type, v_charge);
    SET v_courier = LAST_INSERT_ID();

    INSERT INTO Booking (Customer_ID, Courier_ID, Date)
    VALUES (p_customer, v_courier, CURDATE());

    INSERT INTO Shipment (Courier_ID, Source, Destination, Status)
    VALUES (v_courier, p_source, p_dest, 'Booked');
    SET v_shipment = LAST_INSERT_ID();

    INSERT INTO Payment (Courier_ID, Amount, Method, Status)
    VALUES (v_courier, v_charge, p_method, 'Pending');

    INSERT INTO Tracking (Shipment_ID, Location, Status)
    VALUES (v_shipment, p_source, 'Booked');

    COMMIT;

    SELECT v_tracking AS Tracking_No, v_charge AS Charges;
END$$


-- 3. TRIGGER: adds a Tracking row whenever Shipment status changes
CREATE TRIGGER trg_shipment_status_tracking
AFTER UPDATE ON Shipment
FOR EACH ROW
BEGIN
    IF NEW.Status <> OLD.Status THEN
        INSERT INTO Tracking (Shipment_ID, Location, Status)
        VALUES (NEW.Shipment_ID,
                IF(NEW.Status IN ('Out for Delivery', 'Delivered'),
                   NEW.Destination, NEW.Source),
                NEW.Status);
    END IF;
END$$


-- 4. CURSOR: branch-wise report
CREATE PROCEDURE Branch_Report()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE b_name VARCHAR(60);
    DECLARE b_city VARCHAR(40);
    DECLARE total INT;
    DECLARE delivered INT;
    DECLARE cur CURSOR FOR SELECT Branch_Name, City FROM Branch;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS report;
    CREATE TEMPORARY TABLE report (Branch VARCHAR(60), Total INT, Delivered INT);

    OPEN cur;
    loop1: LOOP
        FETCH cur INTO b_name, b_city;
        IF done = 1 THEN LEAVE loop1; END IF;

        SELECT COUNT(*) INTO total FROM Shipment
        WHERE Source = b_city OR Destination = b_city;

        SELECT COUNT(*) INTO delivered FROM Shipment
        WHERE (Source = b_city OR Destination = b_city) AND Status = 'Delivered';

        INSERT INTO report VALUES (b_name, total, delivered);
    END LOOP;
    CLOSE cur;

    SELECT * FROM report;
END$$


-- 5. CURSOR: agent-wise report
CREATE PROCEDURE Agent_Report()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE a_id INT;
    DECLARE a_name VARCHAR(60);
    DECLARE total INT;
    DECLARE delivered INT;
    DECLARE cur CURSOR FOR SELECT Agent_ID, Name FROM Delivery_Agent;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS report2;
    CREATE TEMPORARY TABLE report2 (Agent VARCHAR(60), Total INT, Delivered INT);

    OPEN cur;
    loop1: LOOP
        FETCH cur INTO a_id, a_name;
        IF done = 1 THEN LEAVE loop1; END IF;

        SELECT COUNT(*) INTO total FROM Delivery WHERE Agent_ID = a_id;

        SELECT COUNT(*) INTO delivered FROM Delivery
        WHERE Agent_ID = a_id AND Status = 'Delivered';

        INSERT INTO report2 VALUES (a_name, total, delivered);
    END LOOP;
    CLOSE cur;

    SELECT * FROM report2;
END$$

DELIMITER ;