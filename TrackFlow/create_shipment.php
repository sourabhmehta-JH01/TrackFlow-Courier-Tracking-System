<?php
require 'config.php';

$types     = ['Document', 'Parcel', 'Fragile', 'Express'];
$methods   = ['Cash', 'Card', 'UPI', 'Net Banking'];
$cities    = array_column(q("SELECT City FROM Branch ORDER BY City"), 'City');
$customers = q("SELECT Customer_ID, Name, Phone FROM Customer ORDER BY Name");
$ids       = array_column($customers, 'Customer_ID');

$f = ['customer' => (int)($_GET['customer'] ?? 0), 'type' => 'Parcel', 'weight' => '2.5',
      'src' => 'Delhi', 'dst' => 'Mumbai', 'method' => 'UPI'];
$msg = $err = "";
$done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = ['customer' => (int)($_POST['customer'] ?? 0), 'type' => $_POST['type'] ?? '',
          'weight' => trim($_POST['weight'] ?? ''), 'src' => $_POST['src'] ?? '',
          'dst' => $_POST['dst'] ?? '', 'method' => $_POST['method'] ?? ''];
    $w = (float)$f['weight'];

    if (!in_array($f['customer'], $ids)) {
        $err = "Please choose a customer.";
    } elseif (!is_numeric($f['weight']) || $w <= 0 || $w > 50) {
        $err = "Weight must be between 0.1 and 50 kg.";
    } elseif (!in_array($f['type'], $types, true) || !in_array($f['method'], $methods, true)
              || !in_array($f['src'], $cities, true) || !in_array($f['dst'], $cities, true)) {
        $err = "Please choose valid options from the lists.";
    } elseif ($f['src'] === $f['dst']) {
        $err = "Source and destination must be different.";
    } elseif (isset($_POST['estimate'])) {
        // FUNCTION: Calculate_Charge()
        $c = q("SELECT Calculate_Charge(?, ?) AS c", "sd", $f['type'], $w)[0]['c'];
        $msg = "Estimated charge: ₹" . number_format((float)$c, 2);
    } else {
        try {
            // PROCEDURE: Book_Courier() (one transaction, 5 tables)
            $res = $conn->query(sprintf("CALL Book_Courier(%d, '%s', %s, '%s', '%s', '%s')",
                   $f['customer'], $conn->real_escape_string($f['type']), number_format($w, 2, '.', ''),
                   $conn->real_escape_string($f['src']), $conn->real_escape_string($f['dst']),
                   $conn->real_escape_string($f['method'])));
            $done = $res->fetch_assoc();
            $res->free();
            while ($conn->more_results()) { $conn->next_result(); }
        } catch (mysqli_sql_exception $e) {
            $err = "Booking failed and was rolled back: " . $e->getMessage();
        }
    }
}

top("Create Shipment");
?>
<h1>Create shipment</h1>
<p class="muted">Books a courier with the Book_Courier() procedure. The charge comes from Calculate_Charge().</p>

<?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($done): ?>
  <div class="alert ok">Shipment created! Tracking number <b><?= h($done['Tracking_No']) ?></b>, charge ₹<?= h($done['Charges']) ?>.
    <a href="track.php?tn=<?= urlencode($done['Tracking_No']) ?>">Track it →</a></div>
<?php endif; ?>

<form method="post" class="formbox">
  <div class="formgrid">
    <div><label>Customer</label>
      <select name="customer" required><option value="">Select...</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['Customer_ID'] ?>"<?= $f['customer'] == $c['Customer_ID'] ? ' selected' : '' ?>><?= h($c['Name']) ?> (<?= h($c['Phone']) ?>)</option>
        <?php endforeach; ?>
      </select></div>
    <div><label>Courier type</label><select name="type"><?php opts($types, $f['type']); ?></select></div>
    <div><label>Weight (kg)</label><input type="number" name="weight" step="0.1" min="0.1" max="50" value="<?= h($f['weight']) ?>" required></div>
    <div><label>From</label><select name="src"><?php opts($cities, $f['src']); ?></select></div>
    <div><label>To</label><select name="dst"><?php opts($cities, $f['dst']); ?></select></div>
    <div><label>Payment method</label><select name="method"><?php opts($methods, $f['method']); ?></select></div>
  </div>
  <p>
    <button class="btn" type="submit" name="book" value="1">Create shipment</button>
    <button class="btn alt" type="submit" name="estimate" value="1">Estimate charge</button>
  </p>
  <p class="muted">New customer? <a href="register.php">Register here</a></p>
</form>
<?php bottom(); ?>