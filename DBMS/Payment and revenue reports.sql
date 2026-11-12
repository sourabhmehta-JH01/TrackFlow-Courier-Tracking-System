-- Revenue collected vs pending
SELECT Status, COUNT(*) AS Payments, SUM(Amount) AS Total_Amount
FROM Payment
GROUP BY Status;                                         

-- Revenue by payment method
SELECT Method, COUNT(*) AS Payments, SUM(Amount) AS Total_Amount
FROM Payment
GROUP BY Method
ORDER BY Total_Amount DESC;

-- Revenue by courier type (paid only)
SELECT co.Type, COUNT(*) AS Couriers, SUM(p.Amount) AS Revenue
FROM Payment p
JOIN Courier co ON co.Courier_ID = p.Courier_ID
WHERE p.Status = 'Paid'
GROUP BY co.Type;

-- Pending payments with customer details
SELECT c.Name, c.Phone, co.Tracking_No, p.Amount, p.Method
FROM Payment p
JOIN Courier  co ON co.Courier_ID  = p.Courier_ID
JOIN Booking  b  ON b.Courier_ID   = co.Courier_ID
JOIN Customer c  ON c.Customer_ID  = b.Customer_ID
WHERE p.Status = 'Pending';                               