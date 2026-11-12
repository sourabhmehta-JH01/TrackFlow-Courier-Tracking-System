<?php
require 'config.php';

$tn = strtoupper(trim($_GET['tn'] ?? ''));
$ship = null;
$timeline = [];

if ($tn !== '') {
    $r = q("SELECT s.Shipment_ID, c.Tracking_No, c.Type, c.Weight, c.Charges, s.Source, s.Destination,
                   s.Status, cu.Name AS Customer, cu.Phone, p.Method, p.Status AS Pay, a.Name AS Agent
            FROM Courier c
            JOIN Shipment s  ON s.Courier_ID   = c.Courier_ID
            JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
            JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
            JOIN Payment p   ON p.Courier_ID   = c.Courier_ID
            LEFT JOIN Delivery d       ON d.Shipment_ID = s.Shipment_ID
            LEFT JOIN Delivery_Agent a ON a.Agent_ID    = d.Agent_ID
            WHERE c.Tracking_No = ?", "s", $tn);
    $ship = $r[0] ?? null;
    if ($ship) {
        $timeline = q("SELECT Location, Status, Date FROM Tracking WHERE Shipment_ID = ? ORDER BY Date, Tracking_ID",
                      "i", $ship['Shipment_ID']);
    }
}

top("Track Shipment");
?>
<h1>Track shipment</h1>
<form method="get" class="formbox">
  <div class="inline"><input name="tn" value="<?= h($tn) ?>" placeholder="Enter tracking number, e.g. TF1001" required>
  <button class="btn" type="submit">Track</button></div>
</form>

<?php if ($tn !== '' && !$ship): ?><div class="alert err">No shipment found for “<?= h($tn) ?>”.</div><?php endif; ?>

<?php if ($ship): ?>
  <h2><?= h($ship['Tracking_No']) ?> <?= badge($ship['Status']) ?></h2>
  <div class="panel detail">
    <div><span>Customer</span><b><?= h($ship['Customer']) ?></b></div>
    <div><span>Phone</span><b><?= h($ship['Phone']) ?></b></div>
    <div><span>Route</span><b><?= h($ship['Source']) ?> → <?= h($ship['Destination']) ?></b></div>
    <div><span>Type and weight</span><b><?= h($ship['Type']) ?>, <?= h($ship['Weight']) ?> kg</b></div>
    <div><span>Charge</span><b>₹<?= h($ship['Charges']) ?></b></div>
    <div><span>Payment</span><b><?= h($ship['Method']) ?> <?= badge($ship['Pay']) ?></b></div>
    <div><span>Delivery agent</span><b><?= h($ship['Agent'] ?: 'Not assigned yet') ?></b></div>
  </div>

  <div class="panel" style="margin-top:20px">
    <h2>Tracking history</h2>
    <ul class="timeline">
      <?php foreach ($timeline as $t): ?>
        <li><?= badge($t['Status']) ?> <b><?= h($t['Location']) ?></b><small><?= h($t['Date']) ?></small></li>
      <?php endforeach; ?>
    </ul>
    <p class="muted">The delivery status is updated only by the assigned delivery agent, from the <a href="employee.php">Staff portal</a>.</p>  </div>
<?php endif; ?>
<?php bottom(); ?>