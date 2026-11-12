<?php
require 'config.php';

function stars($n) { return str_repeat('★', $n) . str_repeat('☆', 5 - $n); }

$cid = (int)($_GET['c'] ?? $_POST['c'] ?? 0);
$msg = $err = "";

// customers who have at least one delivered shipment (EXISTS subquery)
$custs = q("SELECT cu.Customer_ID, cu.Name, cu.Phone FROM Customer cu
            WHERE EXISTS (SELECT 1 FROM Booking b JOIN Shipment s ON s.Courier_ID = b.Courier_ID
                          WHERE b.Customer_ID = cu.Customer_ID AND s.Status = 'Delivered')
            ORDER BY cu.Name");
$cust = null;
foreach ($custs as $c) { if ((int)$c['Customer_ID'] === $cid) { $cust = $c; } }

if ($cust && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $courier = (int)($_POST['courier'] ?? 0);
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comments'] ?? '');
    $own = q("SELECT c.Tracking_No FROM Booking b
              JOIN Courier c  ON c.Courier_ID = b.Courier_ID
              JOIN Shipment s ON s.Courier_ID = c.Courier_ID
              WHERE b.Customer_ID = ? AND c.Courier_ID = ? AND s.Status = 'Delivered'", "ii", $cid, $courier)[0] ?? null;

    if (!$own) {
        $err = "You can only rate your own delivered shipments.";
    } elseif ($rating < 1 || $rating > 5) {
        $err = "Rating must be from 1 to 5.";
    } elseif (mb_strlen($comment) > 255) {
        $err = "The comment can be at most 255 characters.";
    } elseif (q("SELECT 1 FROM Feedback WHERE Customer_ID = ? AND Courier_ID = ?", "ii", $cid, $courier)) {
        $err = "You have already rated this shipment.";
    } else {
        try {
            $cm = ($comment === '') ? null : $comment;
            $st = $conn->prepare("INSERT INTO Feedback (Customer_ID, Courier_ID, Rating, Comments) VALUES (?, ?, ?, ?)");
            $st->bind_param("iiis", $cid, $courier, $rating, $cm);
            $st->execute();
            $msg = "Thank you! Your rating for " . $own['Tracking_No'] . " was saved.";
        } catch (mysqli_sql_exception $e) {
            $err = "The database rejected the feedback: " . $e->getMessage();
        }
    }
}

$mine = [];
if ($cust) {
    $mine = q("SELECT c.Courier_ID, c.Tracking_No, s.Source, s.Destination, f.Rating, f.Comments
               FROM Booking b
               JOIN Courier c  ON c.Courier_ID = b.Courier_ID
               JOIN Shipment s ON s.Courier_ID = c.Courier_ID
               LEFT JOIN Feedback f ON f.Courier_ID = c.Courier_ID AND f.Customer_ID = b.Customer_ID
               WHERE b.Customer_ID = ? AND s.Status = 'Delivered'
               ORDER BY (f.Feedback_ID IS NOT NULL), c.Courier_ID DESC", "i", $cid);
}

$sum  = q("SELECT COUNT(*) AS n, COALESCE(ROUND(AVG(Rating), 2), 0) AS avg_rating FROM Feedback")[0];
$dist = array_column(q("SELECT Rating, COUNT(*) AS total FROM Feedback GROUP BY Rating"), 'total', 'Rating');
$maxD = max(array_merge([1], array_values($dist)));
$recent = q("SELECT cu.Name, c.Tracking_No, f.Rating, f.Comments
             FROM Feedback f
             JOIN Customer cu ON cu.Customer_ID = f.Customer_ID
             JOIN Courier c   ON c.Courier_ID   = f.Courier_ID
             ORDER BY f.Feedback_ID DESC LIMIT 8");

top("Feedback");
?>
<h1>Feedback and ratings</h1>
<p class="muted">Customers rate delivered shipments from 1 to 5. The database rule CHECK (Rating BETWEEN 1 AND 5) rejects anything else.</p>

<?php if ($msg): ?><div class="alert ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>

<form method="get" class="formbox">
  <div class="formgrid">
    <div><label>Customer</label>
      <select name="c" required><option value="">Select your name...</option>
        <?php foreach ($custs as $c): ?>
          <option value="<?= (int)$c['Customer_ID'] ?>"<?= $cid === (int)$c['Customer_ID'] ? ' selected' : '' ?>><?= h($c['Name']) ?> (<?= h($c['Phone']) ?>)</option>
        <?php endforeach; ?>
      </select></div>
    <div><button class="btn" type="submit">Show my deliveries</button></div>
  </div>
</form>

<?php if ($cust): ?>
  <h2>Delivered shipments of <?= h($cust['Name']) ?></h2>
  <div class="scroll"><table>
    <tr><th>Tracking no</th><th>Route</th><th>Your rating</th></tr>
    <?php foreach ($mine as $m): ?>
    <tr>
      <td><?= h($m['Tracking_No']) ?></td>
      <td><?= h($m['Source']) ?> → <?= h($m['Destination']) ?></td>
      <td>
        <?php if ($m['Rating'] !== null): ?>
          <span class="stars"><?= stars((int)$m['Rating']) ?></span> <?= h($m['Comments']) ?>
        <?php else: ?>
          <form method="post" class="inline">
            <input type="hidden" name="c" value="<?= $cid ?>">
            <input type="hidden" name="courier" value="<?= (int)$m['Courier_ID'] ?>">
            <select name="rating"><?php for ($i = 5; $i >= 1; $i--) { echo '<option value="' . $i . '">' . $i . ' ★</option>'; } ?></select>
            <input name="comments" maxlength="255" placeholder="Comment (optional)">
            <button class="btn small" type="submit">Submit</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$mine): ?><tr><td colspan="3" class="muted">No delivered shipments yet.</td></tr><?php endif; ?>
  </table></div>
<?php endif; ?>

<div class="two">
  <div class="panel">
    <h2>Overall rating</h2>
    <div class="stats">
      <?php stat_card("Average rating", $sum['avg_rating'] . " / 5"); stat_card("Total reviews", $sum['n']); ?>
    </div>
    <?php for ($i = 5; $i >= 1; $i--): $n = $dist[$i] ?? 0; ?>
      <div class="bar-row">
        <span class="stars"><?= $i ?> ★</span>
        <div class="bar"><i style="width:<?= round($n * 100 / $maxD) ?>%"></i></div>
        <b><?= (int)$n ?></b>
      </div>
    <?php endfor; ?>
  </div>
  <div class="panel">
    <h2>Recent reviews</h2>
    <?php foreach ($recent as $r): ?>
      <p><span class="stars"><?= stars((int)$r['Rating']) ?></span> <b><?= h($r['Name']) ?></b>
         <span class="muted">· <?= h($r['Tracking_No']) ?></span><br><?= h($r['Comments'] ?: '') ?></p>
    <?php endforeach; ?>
    <?php if (!$recent): ?><p class="muted">No reviews yet.</p><?php endif; ?>
  </div>
</div>
<?php bottom(); ?>