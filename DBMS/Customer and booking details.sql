-- All bookings with customer and courier details
SELECT b.Booking_ID, c.Name AS Customer, c.Phone, b.Date AS Booking_Date,
       co.Tracking_No, co.Type, co.Weight, co.Charges
FROM Booking b
JOIN Customer c  ON c.Customer_ID = b.Customer_ID
JOIN Courier  co ON co.Courier_ID = b.Courier_ID
ORDER BY b.Date;                                          

-- Bookings per customer, including the customer who never booked
SELECT c.Customer_ID, c.Name,
       COUNT(b.Booking_ID)          AS Total_Bookings,
       COALESCE(SUM(co.Charges), 0) AS Total_Spent
FROM Customer c
LEFT JOIN Booking b  ON b.Customer_ID = c.Customer_ID
LEFT JOIN Courier co ON co.Courier_ID = b.Courier_ID
GROUP BY c.Customer_ID, c.Name;                           