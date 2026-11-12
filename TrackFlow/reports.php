<?php
require 'config.php';

function auto_table($rows) {
    if (!$rows) { echo '<p class="muted">No rows.</p>'; return; }
    $cols = [];
    foreach (array_keys($rows[0]) as $k) { $cols[$k] = str_replace('_', ' ', $k); }
    table($cols, $rows);
}

function call_proc($name) {
    global $conn;
    $res  = $conn->query("CALL $name()");
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    while ($conn->more_results()) { $conn->next_result(); }
    return $rows;
}

$reports = [
 'Shipment, revenue and customer reports' => [
  ['Shipments: delivered, pending and in transit', 'CASE · GROUP BY · COUNT',
   "SELECT CASE WHEN Status = 'Delivered' THEN 'Delivered'
               WHEN Status IN ('In Transit', 'Out for Delivery') THEN 'In transit'
               ELSE 'Pending' END AS Report,
           COUNT(*) AS Shipments
    FROM Shipment
    GROUP BY Report
    ORDER BY Shipments DESC"],
  ['Revenue by month', 'JOIN · SUM · CASE · GROUP BY',
   "SELECT DATE_FORMAT(b.Date, '%Y-%m') AS Booking_Month,
           COUNT(*) AS Bookings,
           SUM(p.Amount) AS Billed,
           SUM(CASE WHEN p.Status = 'Paid' THEN p.Amount ELSE 0 END) AS Received
    FROM Booking b
    JOIN Payment p ON p.Courier_ID = b.Courier_ID
    GROUP BY DATE_FORMAT(b.Date, '%Y-%m')
    ORDER BY Booking_Month"],
  ['Revenue received by payment method', 'WHERE · SUM · GROUP BY',
   "SELECT Method, COUNT(*) AS Payments, SUM(Amount) AS Received
    FROM Payment
    WHERE Status = 'Paid'
    GROUP BY Method
    ORDER BY Received DESC"],
  ['Customers with 2 or more bookings', 'JOIN · GROUP BY · HAVING',
   "SELECT cu.Name, COUNT(*) AS Bookings, SUM(c.Charges) AS Spent
    FROM Customer cu
    JOIN Booking b ON b.Customer_ID = cu.Customer_ID
    JOIN Courier c ON c.Courier_ID  = b.Courier_ID
    GROUP BY cu.Customer_ID, cu.Name
    HAVING COUNT(*) >= 2
    ORDER BY Bookings DESC, Spent DESC"],
 ],
 'Subqueries and set operators' => [
  ['Couriers above the average charge', 'SUBQUERY · AVG',
   "SELECT Tracking_No, Type, Weight, Charges
    FROM Courier
    WHERE Charges > (SELECT AVG(Charges) FROM Courier)
    ORDER BY Charges DESC
    LIMIT 10"],
  ['Customers who have given feedback', 'EXISTS',
   "SELECT cu.Customer_ID, cu.Name
    FROM Customer cu
    WHERE EXISTS (SELECT 1 FROM Feedback f WHERE f.Customer_ID = cu.Customer_ID)
    ORDER BY cu.Name
    LIMIT 10"],
  ['Customers who never booked a courier', 'NOT EXISTS',
   "SELECT cu.Customer_ID, cu.Name, cu.Phone
    FROM Customer cu
    WHERE NOT EXISTS (SELECT 1 FROM Booking b WHERE b.Customer_ID = cu.Customer_ID)
    ORDER BY cu.Name"],
  ['Parcels costlier than ANY express courier', 'ANY',
   "SELECT Tracking_No, Weight, Charges
    FROM Courier
    WHERE Type = 'Parcel'
      AND Charges > ANY (SELECT Charges FROM Courier WHERE Type = 'Express')
    ORDER BY Charges DESC
    LIMIT 10"],
  ['Parcels heavier than ALL express couriers', 'ALL',
   "SELECT Tracking_No, Weight, Charges
    FROM Courier
    WHERE Type = 'Parcel'
      AND Weight > ALL (SELECT Weight FROM Courier WHERE Type = 'Express')
    ORDER BY Weight DESC
    LIMIT 10"],
  ['Everything in the system, counted', 'UNION',
   "SELECT 'Customers' AS Category, COUNT(*) AS Total FROM Customer
    UNION
    SELECT 'Employees', COUNT(*) FROM Employee
    UNION
    SELECT 'Delivery agents', COUNT(*) FROM Delivery_Agent
    UNION
    SELECT 'Branches', COUNT(*) FROM Branch
    UNION
    SELECT 'Shipments', COUNT(*) FROM Shipment"],
 ],
 'Feedback and rating reports' => [
  ['Average rating by courier type', 'JOIN · AVG · GROUP BY',
   "SELECT c.Type, COUNT(*) AS Reviews, ROUND(AVG(f.Rating), 2) AS Avg_Rating
    FROM Feedback f
    JOIN Courier c ON c.Courier_ID = f.Courier_ID
    GROUP BY c.Type
    ORDER BY Avg_Rating DESC"],
  ['Rating distribution', 'COUNT · GROUP BY',
   "SELECT Rating, COUNT(*) AS Reviews
    FROM Feedback
    GROUP BY Rating
    ORDER BY Rating DESC"],
  ['Delivery agents ranked by customer rating', '4-table JOIN · AVG · GROUP BY',
   "SELECT a.Name AS Agent, COUNT(f.Feedback_ID) AS Reviews, ROUND(AVG(f.Rating), 2) AS Avg_Rating
    FROM Delivery_Agent a
    JOIN Delivery d ON d.Agent_ID    = a.Agent_ID
    JOIN Shipment s ON s.Shipment_ID = d.Shipment_ID
    JOIN Feedback f ON f.Courier_ID  = s.Courier_ID
    GROUP BY a.Agent_ID, a.Name
    ORDER BY Avg_Rating DESC, Reviews DESC
    LIMIT 10"],
  ['Complaints: rating 2 or below', 'JOIN · WHERE',
   "SELECT cu.Name AS Customer, c.Tracking_No, f.Rating, f.Comments
    FROM Feedback f
    JOIN Customer cu ON cu.Customer_ID = f.Customer_ID
    JOIN Courier c   ON c.Courier_ID   = f.Courier_ID
    WHERE f.Rating <= 2
    ORDER BY f.Rating, cu.Name"],
 ],
];

top("Reports");
?>
<h1>Reports</h1>
<p class="muted">Every report runs a live query on the TrackFlow database. Open “Show the SQL” to see the query behind it.</p>

<h2>Cursor reports</h2>
<?php foreach ([['Branch_Report', 'Branch-wise report (cursor)'], ['Agent_Report', 'Agent-wise report (cursor)']] as [$proc, $title]): ?>
  <div class="sec">
    <h3><?= h($title) ?></h3>
    <span class="tag">CURSOR</span><span class="tag">OPEN · FETCH · CLOSE</span><span class="tag">Stored procedure</span>
    <?php
      try { auto_table(call_proc($proc)); }
      catch (mysqli_sql_exception $e) { echo '<div class="alert err">' . h($e->getMessage()) . '</div>'; }
    ?>
    <details><summary>Show the SQL</summary><pre>CALL <?= h($proc) ?>();

-- inside the procedure:
DECLARE cur CURSOR FOR SELECT ... ;
OPEN cur;
loop1: LOOP
  FETCH cur INTO ...;          -- one row at a time
  IF done = 1 THEN LEAVE loop1; END IF;
  ...                          -- count shipments or deliveries for this row
END LOOP;
CLOSE cur;</pre></details>
  </div>
<?php endforeach; ?>

<?php foreach ($reports as $group => $items): ?>
  <h2><?= h($group) ?></h2>
  <?php foreach ($items as [$title, $tags, $sql]): ?>
    <div class="sec">
      <h3><?= h($title) ?></h3>
      <?php foreach (explode(' · ', $tags) as $t) { echo '<span class="tag">' . h($t) . '</span>'; } ?>
      <?php
        try { auto_table(q($sql)); }
        catch (mysqli_sql_exception $e) { echo '<div class="alert err">' . h($e->getMessage()) . '</div>'; }
      ?>
      <details><summary>Show the SQL</summary><pre><?= h($sql) ?></pre></details>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>
<?php bottom(); ?>