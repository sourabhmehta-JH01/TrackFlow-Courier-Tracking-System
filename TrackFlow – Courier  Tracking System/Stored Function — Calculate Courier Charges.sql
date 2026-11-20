				DELIMITER //

CREATE FUNCTION Calculate_Charge(
    courier_type VARCHAR(30),
    weight DECIMAL(6,2)
)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE charge DECIMAL(10,2);

    IF courier_type = 'Document' THEN
        SET charge = 40 + weight * 20;

    ELSEIF courier_type = 'Parcel' THEN
        SET charge = 60 + weight * 45;

    ELSEIF courier_type = 'Fragile' THEN
        SET charge = 80 + weight * 70;

    ELSEIF courier_type = 'Express' THEN
        SET charge = 100 + weight * 90;

    ELSE
        SET charge = NULL;
    END IF;

    RETURN charge;
END //

DELIMITER ;


-- Estamiting Charge
SELECT Calculate_Charge('Express', 5) AS Charges;
