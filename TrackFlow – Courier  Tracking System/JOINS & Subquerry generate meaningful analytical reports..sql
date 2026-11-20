--  JOIN – City-wise revenue

SELECT s.Source AS City,
       SUM(p.Amount) AS Total_Revenue
FROM Shipment s
JOIN Payment p ON s.Courier_ID = p.Courier_ID
WHERE p.Status = 'Paid'
GROUP BY s.Source;


--  JOIN – Customer-wise spending
SELECT c.Name,
       SUM(p.Amount) AS Total_Spending
FROM Customer c
JOIN Booking b ON c.Customer_ID = b.Customer_ID
JOIN Payment p ON b.Courier_ID = p.Courier_ID
WHERE p.Status = 'Paid'
GROUP BY c.Customer_ID, c.Name
ORDER BY Total_Spending DESC;


-- Subquery – Agents without deliveries
SELECT * FROM Delivery_Agent
WHERE Agent_ID NOT IN
(SELECT Agent_ID FROM Delivery);

-- Subquery – Delivered couriers without feedback
SELECT * FROM Shipment
WHERE Status = 'Delivered'
AND Courier_ID NOT IN
(SELECT Courier_ID FROM Feedback);


