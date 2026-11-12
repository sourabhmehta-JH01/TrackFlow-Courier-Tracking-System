-- All feedback with customer and courier
SELECT c.Name AS Customer, co.Tracking_No, f.Rating, f.Comments
FROM Feedback f
JOIN Customer c  ON c.Customer_ID = f.Customer_ID
JOIN Courier  co ON co.Courier_ID = f.Courier_ID;          

-- Rating summary
SELECT COUNT(*) AS Total_Feedback, ROUND(AVG(Rating), 2) AS Average_Rating,
       MAX(Rating) AS Best, MIN(Rating) AS Worst
FROM Feedback;                                           

-- Rating distribution
SELECT Rating, COUNT(*) AS Total
FROM Feedback
GROUP BY Rating
ORDER BY Rating DESC;

-- Delivered couriers that have no feedback yet (expect none with this data)
SELECT co.Tracking_No
FROM Shipment s
JOIN Courier co ON co.Courier_ID = s.Courier_ID
LEFT JOIN Feedback f ON f.Courier_ID = co.Courier_ID
WHERE s.Status = 'Delivered' AND f.Feedback_ID IS NULL;

-- Average rating by agent (delivered shipments only)
SELECT a.Name AS Agent, ROUND(AVG(f.Rating), 2) AS Avg_Rating
FROM Delivery d
JOIN Delivery_Agent a ON a.Agent_ID  = d.Agent_ID
JOIN Shipment s       ON s.Shipment_ID = d.Shipment_ID
JOIN Feedback f       ON f.Courier_ID  = s.Courier_ID
GROUP BY a.Agent_ID, a.Name;