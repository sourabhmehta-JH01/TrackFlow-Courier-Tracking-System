<?php
require 'config.php';

$statuses  = ['Booked', 'Picked Up', 'In Transit', 'Out for Delivery', 'Delivered'];
$methods   = ['Cash', 'Card', 'UPI', 'Net Banking'];
$nextLabel = ['Picked Up' => 'Mark picked up', 'In Transit' => 'Mark in transit',
              'Out for Delivery' => 'Start delivery', 'Delivered' => 'Mark delivered'];

// leave the staff area
if (isset($_GET['leave'])) { unset($_SESSION['staff']); header("Location: employee.php"); exit; }

// choose who you are (employee or delivery agent)
if (isset($_GET['type'], $_GET['who'])) {
    $id = (int)$_GET['who'];
    if ($_GET['type'] === 'employee') {
        $type = 'employee';
        $r = q("SELECT e.Employee_ID AS id, e.Name, e.Role, b.Branch_ID, b.Branch_Name, b.City
                FROM Employee e JOIN Branch b ON b.Branch_ID = e.Branch_ID WHERE e.Employee_ID = ?", "i", $id);
    } else {
        $type = 'agent';
        $r = q("SELECT a.Agent_ID AS id, a.Name, 'Delivery agent' AS Role, b.Branch_ID, b.Branch_Name, b.City
                FROM Delivery_Agent a JOIN Branch b ON b.Branch_ID = a.Branch_ID WHERE a.Agent_ID = ?", "i", $id);
    }
    if ($r) {
        $_SESSION['staff'] = ['type' => $type, 'id' => (int)$r[0]['id'], 'name' => $r[0]['Name'], 'role' => $r[0]['Role'],
                              'branch_id' => (int)$r[0]['Branch_ID'], 'branch' => $r[0]['Branch_Name'], 'city' => $r[0]['City']];
    }
    header("Location: employee.php");
    exit;
}

$staff = $_SESSION['staff'] ?? null;
$msg = $err = "";

// ===== AGENT ACTIONS: only a delivery agent can do these =====
if ($staff && $staff['type'] === 'agent' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'status';

    if ($action === 'pay') {
        // payment of a customer whose shipment is assigned to THIS agent
        $pid   = (int)($_POST['payment'] ?? 0);
        $state = $_POST['state'] ?? '';
        $m     = $_POST['method'] ?? '';
        $p = q("SELECT p.Payment_ID, p.Status, c.Tracking_No
                FROM Payment p
                JOIN Courier c  ON c.Courier_ID  = p.Courier_ID
                JOIN Shipment s ON s.Courier_ID  = c.Courier_ID
                JOIN Delivery d ON d.Shipment_ID = s.Shipment_ID
                WHERE p.Payment_ID = ? AND d.Agent_ID = ?", "ii", $pid, $staff['id'])[0] ?? null;

        if (!$p) {
            $err = "This payment does not belong to one of your shipments.";
        } elseif ($state === 'paid') {
            if ($p['Status'] !== 'Pending') {
                $err = "This payment is already marked as paid.";
            } elseif (!in_array($m, $methods, true)) {
                $err = "Please choose how the payment was received.";
            } else {
                $st = $conn->prepare("UPDATE Payment SET Status = 'Paid', Method = ? WHERE Payment_ID = ? AND Status = 'Pending'");
                $st->bind_param("si", $m, $pid);
                $st->execute();
                $msg = "Payment for " . $p['Tracking_No'] . " marked as PAID (" . $m . ").";
            }
        } elseif ($state === 'unpaid') {
            if ($p['Status'] !== 'Paid') {
                $err = "This payment is already marked as not paid.";
            } else {
                $st = $conn->prepare("UPDATE Payment SET Status = 'Pending' WHERE Payment_ID = ? AND Status = 'Paid'");
                $st->bind_param("i", $pid);
                $st->execute();
                $msg = "Payment for " . $p['Tracking_No'] . " marked as NOT PAID.";
            }
        } else {
            $err = "Unknown payment action.";
        }
    } else {
        // delivery status: one step at a time
        $sid = (int)($_POST['shipment'] ?? 0);
        $new = $_POST['status'] ?? '';
        $d = q("SELECT d.Delivery_ID, s.Status, c.Tracking_No
                FROM Delivery d
                JOIN Shipment s ON s.Shipment_ID = d.Shipment_ID
                JOIN Courier c  ON c.Courier_ID  = s.Courier_ID
                WHERE d.Shipment_ID = ? AND d.Agent_ID = ?", "ii", $sid, $staff['id'])[0] ?? null;

        $idx      = $d ? array_search($d['Status'], $statuses, true) : false;
        $expected = ($idx !== false) ? ($statuses[$idx + 1] ?? null) : null;

        if (!$d) {
            $err = "This shipment is not assigned to you.";
        } elseif ($expected === null || $new !== $expected) {
            $err = "Only the next step is allowed from the current status.";
        } else {
            try {
                $conn->begin_transaction();
                // the TRIGGER adds the tracking row when this UPDATE runs
                $st = $conn->prepare("UPDATE Shipment SET Status = ? WHERE Shipment_ID = ?");
                $st->bind_param("si", $new, $sid);
                $st->execute();
                if ($new === 'Delivered') {
                    $st = $conn->prepare("UPDATE Delivery SET Status = 'Delivered', Date = CURDATE() WHERE Delivery_ID = ?");
                    $st->bind_param("i", $d['Delivery_ID']);
                    $st->execute();
                } elseif ($new === 'Out for Delivery') {
                    $st = $conn->prepare("UPDATE Delivery SET Status = 'Out for Delivery' WHERE Delivery_ID = ?");
                    $st->bind_param("i", $d['Delivery_ID']);
                    $st->execute();
                }
                $conn->commit();
                $msg = $d['Tracking_No'] . " is now " . $new . ".";
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $err = "Update failed and was rolled back: " . $e->getMessage();
            }
        }
    }
}

if (!empty($_GET['ok'])) { $msg = "Shipment " . $_GET['ok'] . " was updated."; }

top("Staff Portal");
?>
<h1>Staff portal</h1>

<?php if (!$staff): ?>
  <p class="muted">Employees assign delivery agents. Only delivery agents update the delivery status and the payments. Choose who you are to continue.</p>
  <?php
    $emps = q("SELECT e.Employee_ID, e.Name, e.Role, b.City FROM Employee e JOIN Branch b ON b.Branch_ID = e.Branch_ID ORDER BY e.Name");
    $ags  = q("SELECT a.Agent_ID, a.Name, b.City FROM Delivery_Agent a JOIN Branch b ON b.Branch_ID = a.Branch_ID ORDER BY a.Name");
  ?>
  <div class="two">
    <form method="get" class="panel">
      <h2>🏢 Employee</h2>
      <p class="muted">See your branch dashboard and assign delivery agents.</p>
      <input type="hidden" name="type" value="employee">
      <select name="who" required><option value="">Select your name...</option>
        <?php foreach ($emps as $e): ?><option value="<?= (int)$e['Employee_ID'] ?>"><?= h($e['Name']) ?> (<?= h($e['Role']) ?>, <?= h($e['City']) ?>)</option><?php endforeach; ?>
      </select>
      <p><button class="btn" type="submit">Open employee dashboard</button></p>
    </form>
    <form method="get" class="panel">
      <h2>🚚 Delivery agent</h2>
      <p class="muted">See your assigned shipments, update delivery status and mark payments.</p>
      <input type="hidden" name="type" value="agent">
      <select name="who" required><option value="">Select your name...</option>
        <?php foreach ($ags as $a): ?><option value="<?= (int)$a['Agent_ID'] ?>"><?= h($a['Name']) ?> (<?= h($a['City']) ?>)</option><?php endforeach; ?>
      </select>
      <p><button class="btn" type="submit">Open agent dashboard</button></p>
    </form>
  </div>

<?php else: ?>
  <p class="muted">Signed in as <b><?= h($staff['name']) ?></b> · <?= h($staff['role']) ?> · <?= h($staff['branch']) ?>, <?= h($staff['city']) ?></p>
  <p><a class="btn small alt" href="employee.php?leave=1">Switch to delivery agent or another person</a></p>
  <?php if ($msg): ?><div class="alert ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>

  <?php if ($staff['type'] === 'employee'):
      // ================= EMPLOYEE DASHBOARD (branch) =================
      $city   = $staff['city'];
      $filter = $_GET['status'] ?? '';

      $stat = q("SELECT COUNT(*) AS total,
                        COALESCE(SUM(d.Delivery_ID IS NOT NULL), 0) AS assigned,
                        COALESCE(SUM(d.Delivery_ID IS NULL AND s.Status <> 'Delivered'), 0) AS waiting,
                        COALESCE(SUM(s.Status IN ('In Transit', 'Out for Delivery')), 0) AS moving,
                        COALESCE(SUM(s.Status = 'Delivered'), 0) AS delivered
                 FROM Shipment s
                 LEFT JOIN Delivery d ON d.Shipment_ID = s.Shipment_ID
                 WHERE s.Source = ? OR s.Destination = ?", "ss", $city, $city)[0];

      // workload of every delivery agent of this branch
      $agentRows = q("SELECT a.Agent_ID, a.Name, a.Phone,
                             COUNT(d.Delivery_ID) AS assigned,
                             COALESCE(SUM(s.Status <> 'Delivered'), 0) AS open_cnt,
                             COALESCE(SUM(s.Status = 'Delivered'), 0) AS delivered,
                             COALESCE(SUM(CASE WHEN p.Status = 'Pending' THEN p.Amount END), 0) AS to_collect
                      FROM Delivery_Agent a
                      LEFT JOIN Delivery d ON d.Agent_ID    = a.Agent_ID
                      LEFT JOIN Shipment s ON s.Shipment_ID = d.Shipment_ID
                      LEFT JOIN Payment p  ON p.Courier_ID  = s.Courier_ID
                      WHERE a.Branch_ID = ?
                      GROUP BY a.Agent_ID, a.Name, a.Phone
                      ORDER BY a.Name", "i", $staff['branch_id']);

      // shipments of this city that still have no delivery agent
      $waiting = q("SELECT s.Shipment_ID, c.Tracking_No, cu.Name AS Customer, s.Source, s.Destination, s.Status
                    FROM Shipment s
                    JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
                    JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
                    JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
                    LEFT JOIN Delivery d ON d.Shipment_ID = s.Shipment_ID
                    WHERE (s.Source = ? OR s.Destination = ?) AND d.Delivery_ID IS NULL AND s.Status <> 'Delivered'
                    ORDER BY s.Shipment_ID DESC", "ss", $city, $city);

      // all shipments of this city with their delivery agent
      $sql = "SELECT s.Shipment_ID, c.Tracking_No, cu.Name AS Customer, s.Source, s.Destination, s.Status,
                     a.Name AS Agent, a.Phone AS AgentPhone, p.Status AS Pay
              FROM Shipment s
              JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
              JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
              JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
              JOIN Payment p   ON p.Courier_ID   = c.Courier_ID
              LEFT JOIN Delivery d       ON d.Shipment_ID = s.Shipment_ID
              LEFT JOIN Delivery_Agent a ON a.Agent_ID    = d.Agent_ID
              WHERE (s.Source = ? OR s.Destination = ?)";
      $types = "ss"; $params = [$city, $city];
      if (in_array($filter, $statuses, true)) { $sql .= " AND s.Status = ?"; $types .= "s"; $params[] = $filter; }
      $sql .= " ORDER BY (s.Status = 'Delivered'), s.Shipment_ID DESC";
      $rows = q($sql, $types, ...$params);
  ?>
    <h2>Employee dashboard</h2>
    <p class="muted">Assigned shipments and delivery agents of your branch. Employees assign agents and edit the destination. The delivery status and the payments are handled only by the assigned delivery agent.</p>
    <div class="stats">
      <?php stat_card("Shipments at this city", $stat['total']);
            stat_card("Assigned to an agent", $stat['assigned']);
            stat_card("Waiting for an agent", $stat['waiting']);
            stat_card("On the way", $stat['moving']);
            stat_card("Delivered", $stat['delivered']);
            stat_card("Delivery agents", count($agentRows)); ?>
    </div>

    <h2>Delivery agents at <?= h($staff['branch']) ?></h2>
    <?php table(['Name' => 'Delivery agent', 'Phone' => 'Phone', 'assigned' => 'Shipments assigned',
                 'open_cnt' => 'In progress', 'delivered' => 'Delivered', 'to_collect' => 'Payment to collect (₹)'], $agentRows); ?>

    <h2>Waiting for a delivery agent (<?= count($waiting) ?>)</h2>
    <div class="scroll"><table>
      <tr><th>Tracking no</th><th>Customer</th><th>Route</th><th>Status</th><th></th></tr>
      <?php foreach ($waiting as $w): ?>
      <tr>
        <td><?= h($w['Tracking_No']) ?></td>
        <td><?= h($w['Customer']) ?></td>
        <td><?= h($w['Source']) ?> → <?= h($w['Destination']) ?></td>
        <td><?= badge($w['Status']) ?></td>
        <td><a class="btn small" href="update_shipment.php?id=<?= (int)$w['Shipment_ID'] ?>">Assign agent</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$waiting): ?><tr><td colspan="5" class="muted">Every shipment at this city has a delivery agent. 🎉</td></tr><?php endif; ?>
    </table></div>

    <h2>Shipments and their delivery agents</h2>
    <div class="pills">
      <a href="employee.php"<?= $filter === '' ? ' class="on"' : '' ?>>All</a>
      <?php foreach ($statuses as $s): ?>
        <a href="employee.php?status=<?= urlencode($s) ?>"<?= $filter === $s ? ' class="on"' : '' ?>><?= h($s) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="scroll"><table>
      <tr><th>Tracking no</th><th>Customer</th><th>Route</th><th>Status</th><th>Payment</th><th>Delivery agent</th><th>Agent phone</th><th></th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['Tracking_No']) ?></td>
        <td><?= h($r['Customer']) ?></td>
        <td><?= h($r['Source']) ?> → <?= h($r['Destination']) ?></td>
        <td><?= badge($r['Status']) ?></td>
        <td><?= badge($r['Pay']) ?></td>
        <td><?= h($r['Agent'] ?: 'Not assigned') ?></td>
        <td><?= h($r['AgentPhone'] ?: '-') ?></td>
        <td><a class="btn small" href="update_shipment.php?id=<?= (int)$r['Shipment_ID'] ?>"><?= $r['Status'] === 'Delivered' ? 'View' : 'Manage' ?></a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="muted">No shipments found.</td></tr><?php endif; ?>
    </table></div>

  <?php else:
      // ================= AGENT DASHBOARD =================
      $me = q("SELECT Agent_ID, Name, Phone FROM Delivery_Agent WHERE Agent_ID = ?", "i", $staff['id'])[0] ?? null;
      $mine = q("SELECT s.Shipment_ID, c.Tracking_No, cu.Name AS Customer, cu.Phone, s.Source, s.Destination,
                        s.Status, d.Date
                 FROM Delivery d
                 JOIN Shipment s  ON s.Shipment_ID  = d.Shipment_ID
                 JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
                 JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
                 JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
                 WHERE d.Agent_ID = ?
                 ORDER BY (s.Status = 'Delivered'), s.Shipment_ID DESC", "i", $staff['id']);
      $pays = q("SELECT p.Payment_ID, c.Tracking_No, cu.Name AS Customer, cu.Phone, p.Amount, p.Method, p.Status
                 FROM Delivery d
                 JOIN Shipment s  ON s.Shipment_ID  = d.Shipment_ID
                 JOIN Courier c   ON c.Courier_ID   = s.Courier_ID
                 JOIN Booking b   ON b.Courier_ID   = c.Courier_ID
                 JOIN Customer cu ON cu.Customer_ID = b.Customer_ID
                 JOIN Payment p   ON p.Courier_ID   = c.Courier_ID
                 WHERE d.Agent_ID = ?
                 ORDER BY (p.Status = 'Paid'), s.Shipment_ID DESC", "i", $staff['id']);
      $done      = count(array_filter($mine, fn($r) => $r['Status'] === 'Delivered'));
      $unpaid    = array_filter($pays, fn($r) => $r['Status'] === 'Pending');
      $toCollect = array_sum(array_column($unpaid, 'Amount'));
      $received  = array_sum(array_column(array_filter($pays, fn($r) => $r['Status'] === 'Paid'), 'Amount'));
      $cnt       = array_count_values(array_column($mine, 'Status'));
      $maxBar    = max(array_merge([1], array_values($cnt)));
  ?>
    <h2>Agent dashboard</h2>
    <div class="panel detail">
      <div><span>Delivery agent</span><b><?= h($me['Name'] ?? $staff['name']) ?></b></div>
      <div><span>Agent ID</span><b><?= (int)$staff['id'] ?></b></div>
      <div><span>Phone</span><b><?= h($me['Phone'] ?? '-') ?></b></div>
      <div><span>Branch</span><b><?= h($staff['branch']) ?>, <?= h($staff['city']) ?></b></div>
    </div>

    <div class="stats">
      <?php stat_card("Assigned to me", count($mine)); stat_card("To deliver", count($mine) - $done);
            stat_card("Delivered", $done); stat_card("Payments not paid", count($unpaid));
            stat_card("Amount to collect (₹)", number_format($toCollect, 2));
            stat_card("Amount received (₹)", number_format($received, 2)); ?>
    </div>

    <div class="panel" style="margin:20px 0">
      <h2>My shipments by status</h2>
      <?php foreach ($statuses as $s): $n = $cnt[$s] ?? 0; ?>
        <div class="bar-row">
          <span><?= badge($s) ?></span>
          <div class="bar"><i style="width:<?= round($n * 100 / $maxBar) ?>%"></i></div>
          <b><?= (int)$n ?></b>
        </div>
      <?php endforeach; ?>
    </div>

    <h2>My assigned shipments</h2>
    <div class="scroll"><table>
      <tr><th>Tracking no</th><th>Customer</th><th>Phone</th><th>Route</th><th>Status</th><th>Delivery date</th><th>Update status</th></tr>
      <?php foreach ($mine as $r):
            $i = array_search($r['Status'], $statuses, true);
            $next = ($i !== false) ? ($statuses[$i + 1] ?? null) : null; ?>
      <tr>
        <td><?= h($r['Tracking_No']) ?></td>
        <td><?= h($r['Customer']) ?></td>
        <td><?= h($r['Phone']) ?></td>
        <td><?= h($r['Source']) ?> → <?= h($r['Destination']) ?></td>
        <td><?= badge($r['Status']) ?></td>
        <td><?= h($r['Date'] ?: '-') ?></td>
        <td>
          <?php if ($next): ?>
            <form method="post" class="inline">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="shipment" value="<?= (int)$r['Shipment_ID'] ?>">
              <input type="hidden" name="status" value="<?= h($next) ?>">
              <button class="btn small" type="submit"><?= h($nextLabel[$next]) ?></button>
            </form>
          <?php else: ?>Completed<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$mine): ?><tr><td colspan="7" class="muted">No shipments are assigned to you yet. An employee assigns them.</td></tr><?php endif; ?>
    </table></div>

    <h2>Customer payments</h2>
    <p class="muted">Mark whether each of your customers has paid. Only you can change the payment of your own shipments.</p>
    <div class="scroll"><table>
      <tr><th>Tracking no</th><th>Customer</th><th>Phone</th><th>Amount (₹)</th><th>Method</th><th>Payment</th><th>Action</th></tr>
      <?php foreach ($pays as $p): ?>
      <tr>
        <td><?= h($p['Tracking_No']) ?></td>
        <td><?= h($p['Customer']) ?></td>
        <td><?= h($p['Phone']) ?></td>
        <td><?= h($p['Amount']) ?></td>
        <td><?= h($p['Method']) ?></td>
        <td><?= badge($p['Status']) ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="payment" value="<?= (int)$p['Payment_ID'] ?>">
            <?php if ($p['Status'] === 'Pending'): ?>
              <input type="hidden" name="state" value="paid">
              <select name="method"><?php opts($methods, $p['Method']); ?></select>
              <button class="btn small" type="submit">Mark as paid</button>
            <?php else: ?>
              <input type="hidden" name="state" value="unpaid">
              <button class="btn small alt" type="submit">Mark as not paid</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$pays): ?><tr><td colspan="7" class="muted">No customer payments yet. They appear once shipments are assigned to you.</td></tr><?php endif; ?>
    </table></div>
    <p class="muted">Each delivery status change adds a tracking row automatically (database trigger). The main dashboard's revenue and pending amounts update as you mark payments.</p>
  <?php endif; ?>
<?php endif; ?>
<?php bottom(); ?>