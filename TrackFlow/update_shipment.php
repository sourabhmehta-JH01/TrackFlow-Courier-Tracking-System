<?php
require 'config.php';

// only a chosen EMPLOYEE can open this page
$staff = $_SESSION['staff'] ?? null;
if (!$staff || $staff['type'] !== 'employee') { header("Location: employee.php"); exit; }
$sid  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$city = $staff['city'];

// an employee can only open shipments that start or end in their branch city
$ship = q("SELECT s.Shipment_ID, s.Source, s.Destination, s.Status, c.Tracking_No, c.Type, c.Weight,
                  cu.Name AS Customer, d.Agent_ID
           FROM Shipment s
           JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
           JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
           JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
           LEFT JOIN Delivery d ON d.Shipment_ID = s.Shipment_ID
           WHERE s.Shipment_ID = ? AND (s.Source = ? OR s.Destination = ?)",
          "iss", $sid, $city, $city)[0] ?? null;
if (!$ship) { header("Location: employee.php"); exit; }

$cities   = array_column(q("SELECT City FROM Branch ORDER BY City"), 'City');
$agents   = q("SELECT a.Agent_ID, a.Name, b.City FROM Delivery_Agent a
               JOIN Branch b ON b.Branch_ID = a.Branch_ID ORDER BY b.City, a.Name");
$agentIds = array_column($agents, 'Agent_ID');
$locked      = ($ship['Status'] === 'Delivered');
$canDest     = in_array($ship['Status'], ['Booked', 'Picked Up', 'In Transit'], true);
$hasDelivery = ($ship['Agent_ID'] !== null);
$curAgent    = (int)$ship['Agent_ID'];
$err = "";

// NOTE: no status field here. Only the assigned delivery agent can update the status.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $dst   = $canDest ? ($_POST['destination'] ?? '') : $ship['Destination'];
    $agent = (int)($_POST['agent'] ?? 0);

    if (!in_array($dst, $cities, true) || $dst === $ship['Source']) {
        $err = "Choose a destination city that is different from the source.";
    } elseif ($agent === 0 || !in_array($agent, $agentIds)) {
        $err = "Please choose a delivery agent.";
    } elseif ($dst === $ship['Destination'] && $agent === $curAgent) {
        $err = "Nothing was changed.";
    } else {
        try {
            $conn->begin_transaction();      // saved together, or not at all
            if ($dst !== $ship['Destination']) {
                $st = $conn->prepare("UPDATE Shipment SET Destination = ? WHERE Shipment_ID = ?");
                $st->bind_param("si", $dst, $sid);
                $st->execute();
            }
            if ($agent !== $curAgent) {
                if ($hasDelivery) {
                    $st = $conn->prepare("UPDATE Delivery SET Agent_ID = ? WHERE Shipment_ID = ?");
                    $st->bind_param("ii", $agent, $sid);
                } else {
                    $st = $conn->prepare("INSERT INTO Delivery (Shipment_ID, Agent_ID, Date, Status) VALUES (?, ?, NULL, 'Assigned')");
                    $st->bind_param("ii", $sid, $agent);
                }
                $st->execute();
            }
            $conn->commit();
            header("Location: employee.php?ok=" . urlencode($ship['Tracking_No']));
            exit;
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $err = "Update failed and was rolled back: " . $e->getMessage();
        }
    }
}

$timeline = q("SELECT Location, Status, Date FROM Tracking WHERE Shipment_ID = ? ORDER BY Date, Tracking_ID", "i", $sid);

top("Manage Shipment");
?>
<p><a href="employee.php">← Back to my shipments</a></p>
<h1>Manage shipment <?= h($ship['Tracking_No']) ?> <?= badge($ship['Status']) ?></h1>
<p class="muted">Signed in as <?= h($staff['name']) ?> · <?= h($staff['branch']) ?>, <?= h($city) ?></p>

<?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>

<div class="panel detail">
  <div><span>Customer</span><b><?= h($ship['Customer']) ?></b></div>
  <div><span>Type and weight</span><b><?= h($ship['Type']) ?>, <?= h($ship['Weight']) ?> kg</b></div>
  <div><span>Source</span><b><?= h($ship['Source']) ?></b></div>
  <div><span>Destination</span><b><?= h($ship['Destination']) ?></b></div>
  <div><span>Status</span><b><?= h($ship['Status']) ?> (set by the delivery agent)</b></div>
</div>

<div class="two">
  <div class="panel">
    <h2>Assign delivery agent</h2>
    <?php if ($locked): ?>
      <p class="muted">This shipment is delivered, so its details are final.</p>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="id" value="<?= $sid ?>">
        <div class="formgrid">
          <div><label>Delivery agent</label>
            <select name="agent" required><option value="">Select...</option>
              <?php foreach ($agents as $a): ?>
                <option value="<?= (int)$a['Agent_ID'] ?>"<?= $curAgent === (int)$a['Agent_ID'] ? ' selected' : '' ?>><?= h($a['Name']) ?> (<?= h($a['City']) ?>)</option>
              <?php endforeach; ?>
            </select></div>
          <?php if ($canDest): ?>
          <div><label>Destination</label><select name="destination"><?php opts($cities, $ship['Destination']); ?></select></div>
          <?php endif; ?>
        </div>
        <p><button class="btn" type="submit">Save</button></p>
      </form>
      <p class="muted">The assigned agent updates the status from the Agent section. Each status change adds a tracking row automatically (database trigger).</p>
    <?php endif; ?>
  </div>
  <div class="panel">
    <h2>Tracking history</h2>
    <ul class="timeline">
      <?php foreach ($timeline as $t): ?>
        <li><?= badge($t['Status']) ?> <b><?= h($t['Location']) ?></b><small><?= h($t['Date']) ?></small></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php bottom(); ?>