<?php
require 'config.php';

$s = q("SELECT
          (SELECT COUNT(*) FROM Shipment) AS shipments,
          (SELECT COALESCE(SUM(Amount),0) FROM Payment WHERE Status = 'Paid') AS revenue,
          (SELECT COALESCE(SUM(Amount),0) FROM Payment WHERE Status = 'Pending') AS pending_amt,
          (SELECT COUNT(*) FROM Payment WHERE Status = 'Pending') AS pending_cnt,
          (SELECT COUNT(*) FROM Shipment WHERE Status = 'Delivered') AS delivered,
          (SELECT COUNT(*) FROM Shipment WHERE Status IN ('In Transit','Out for Delivery')) AS transit,
          (SELECT COUNT(*) FROM Customer) AS customers,
          (SELECT COUNT(*) FROM Employee) AS employees")[0];

$by  = q("SELECT Status, COUNT(*) AS total FROM Shipment GROUP BY Status ORDER BY total DESC");
$pay = q("SELECT Method, COUNT(*) AS payments, SUM(Amount) AS revenue
          FROM Payment WHERE Status = 'Paid' GROUP BY Method ORDER BY revenue DESC");
$recent = q("SELECT c.Tracking_No, cu.Name, s.Source, s.Destination, s.Status, c.Charges, p.Status AS Pay
             FROM Shipment s
             JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
             JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
             JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
             JOIN Payment p   ON p.Courier_ID   = c.Courier_ID
             ORDER BY s.Shipment_ID DESC LIMIT 8");
$max = max(array_merge([1], array_column($by, 'total')));

top("Dashboard");
?>
<h1>Dashboard</h1>
<p class="muted">Live analysis of shipments, revenue and payments from the TrackFlow database.</p>

<div class="stats">
  <?php
  stat_card("Total shipments", $s['shipments']);
  stat_card("Total revenue (₹)", number_format($s['revenue'], 2));
  stat_card("Pending payments (₹)", number_format($s['pending_amt'], 2));
  stat_card("Unpaid bookings", $s['pending_cnt']);
  stat_card("Delivered", $s['delivered']);
  stat_card("In transit", $s['transit']);
  stat_card("Customers", $s['customers']);
  stat_card("Employees", $s['employees']);
  ?>
</div>

<div class="actions">
    <a class="action" href="reports.php"><span class="icon">📊</span><b>Reports</b><small>Queries, cursor reports and ratings</small></a>
  <a class="action" href="feedback.php"><span class="icon">⭐</span><b>Feedback</b><small>Rate delivered shipments</small></a>
  <a class="action" href="create_shipment.php"><span class="icon">📦</span><b>Create shipment</b><small>Book a new courier</small></a>
  <a class="action" href="add_employee.php"><span class="icon">👤</span><b>Add staff</b><small>Branch, employee or delivery agent</small></a>  <a class="action" href="employee.php?leave=1"><span class="icon">🏢</span><b>Staff portal</b><small>Employees and agents manage shipments</small></a>  <form class="action" action="track.php" method="get">
    <span class="icon">🔎</span><b>Track shipment</b>
    <div class="inline"><input name="tn" placeholder="Tracking no, e.g. TF1001" required><button class="btn small" type="submit">Track</button></div>
  </form>
</div>

<div class="two">
  <div class="panel">
    <h2>Shipments by status</h2>
    <?php foreach ($by as $r): ?>
      <div class="bar-row">
        <span><?= badge($r['Status']) ?></span>
        <div class="bar"><i style="width:<?= round($r['total'] * 100 / $max) ?>%"></i></div>
        <b><?= (int)$r['total'] ?></b>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="panel">
    <h2>Revenue received by payment method</h2>
    <?php table(['Method' => 'Method', 'payments' => 'Payments', 'revenue' => 'Revenue (₹)'], $pay); ?>
  </div>
</div>

<h2>Recent shipments</h2>
<div class="scroll"><table>
  <tr><th>Tracking no</th><th>Customer</th><th>Route</th><th>Charge (₹)</th><th>Shipment</th><th>Payment</th></tr>
  <?php foreach ($recent as $r): ?>
  <tr>
    <td><a href="track.php?tn=<?= urlencode($r['Tracking_No']) ?>"><?= h($r['Tracking_No']) ?></a></td>
    <td><?= h($r['Name']) ?></td>
    <td><?= h($r['Source']) ?> → <?= h($r['Destination']) ?></td>
    <td><?= h($r['Charges']) ?></td>
    <td><?= badge($r['Status']) ?></td>
    <td><?= badge($r['Pay']) ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php bottom(); ?>