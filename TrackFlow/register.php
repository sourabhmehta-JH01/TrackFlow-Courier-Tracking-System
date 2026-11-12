<?php
require 'config.php';

$err = "";
$name = $phone = $email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $email = trim($_POST['email'] ?? "");

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
        $err = "Please enter your full name (2 to 60 characters).";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $err = "Phone number must be exactly 10 digits.";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address, or leave it blank.";
    } elseif (q("SELECT 1 FROM Customer WHERE Phone = ?", "s", $phone)) {
        $err = "This phone number is already registered.";
    } elseif ($email !== "" && q("SELECT 1 FROM Customer WHERE Email = ?", "s", $email)) {
        $err = "This email is already registered.";
    } else {
        $emailVal = ($email === "") ? null : $email;
        $st = $conn->prepare("INSERT INTO Customer (Name, Phone, Email) VALUES (?, ?, ?)");
        $st->bind_param("sss", $name, $phone, $emailVal);
        $st->execute();
        header("Location: create_shipment.php?customer=" . $conn->insert_id);
        exit;
    }
}

top("Register Customer");
?>
<div class="box">
  <h1>Register customer</h1>
  <?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Full name</label>
    <input type="text" name="name" value="<?= h($name) ?>" maxlength="60" required>
    <label>Phone number (10 digits)</label>
    <input type="text" name="phone" value="<?= h($phone) ?>" maxlength="10" pattern="[0-9]{10}" required>
    <label>Email (optional)</label>
    <input type="email" name="email" value="<?= h($email) ?>" maxlength="80">
    <button class="btn full" type="submit">Register</button>
  </form>
  <p><a href="create_shipment.php">← Back to create shipment</a></p>
</div>
<?php bottom(); ?>