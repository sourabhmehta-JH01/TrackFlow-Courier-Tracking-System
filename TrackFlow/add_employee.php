<?php
require 'config.php';

$roles    = ['Manager', 'Clerk', 'Accountant', 'Sorter', 'Supervisor'];
$branches = q("SELECT Branch_ID, Branch_Name, City FROM Branch ORDER BY Branch_Name");
$bids     = array_column($branches, 'Branch_ID');

$errB = $errE = $errA = "";
$b = ['name' => '', 'city' => '', 'address' => ''];
$e = ['name' => '', 'role' => 'Clerk', 'branch' => 0];
$a = ['name' => '', 'phone' => '', 'branch' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kind = $_POST['kind'] ?? '';

    // ---------- ADD BRANCH ----------
    if ($kind === 'branch') {
        $b = ['name' => trim($_POST['name'] ?? ''), 'city' => trim($_POST['city'] ?? ''), 'address' => trim($_POST['address'] ?? '')];

        if (mb_strlen($b['name']) < 2 || mb_strlen($b['name']) > 60) {
            $errB = "Please enter the branch name (2 to 60 characters).";
        } elseif (!preg_match('/^[A-Za-z][A-Za-z .-]{1,39}$/', $b['city'])) {
            $errB = "Please enter a valid city name (letters only, 2 to 40 characters).";
        } elseif (mb_strlen($b['address']) > 150) {
            $errB = "The address can be at most 150 characters.";
        } elseif (q("SELECT 1 FROM Branch WHERE LOWER(City) = LOWER(?)", "s", $b['city'])) {
            $errB = "We already have a branch in " . $b['city'] . ". Each city can have only one branch.";
        } elseif (q("SELECT 1 FROM Branch WHERE LOWER(Branch_Name) = LOWER(?)", "s", $b['name'])) {
            $errB = "A branch with this name already exists.";
        } else {
            $addr = ($b['address'] === '') ? null : $b['address'];
            $st = $conn->prepare("INSERT INTO Branch (Branch_Name, City, Address) VALUES (?, ?, ?)");
            $st->bind_param("sss", $b['name'], $b['city'], $addr);
            $st->execute();
            header("Location: add_employee.php?what=Branch&ok=" . urlencode($b['name']));
            exit;
        }
    }

    // ---------- ADD EMPLOYEE ----------
    if ($kind === 'employee') {
        $e = ['name' => trim($_POST['name'] ?? ''), 'role' => $_POST['role'] ?? '', 'branch' => (int)($_POST['branch'] ?? 0)];

        if (mb_strlen($e['name']) < 2 || mb_strlen($e['name']) > 60) {
            $errE = "Please enter the employee’s full name (2 to 60 characters).";
        } elseif (!in_array($e['role'], $roles, true)) {
            $errE = "Please choose a role.";
        } elseif (!in_array($e['branch'], $bids)) {
            $errE = "Please choose a branch.";
        } else {
            $st = $conn->prepare("INSERT INTO Employee (Name, Role, Branch_ID) VALUES (?, ?, ?)");
            $st->bind_param("ssi", $e['name'], $e['role'], $e['branch']);
            $st->execute();
            header("Location: add_employee.php?what=Employee&ok=" . urlencode($e['name']));
            exit;
        }
    }

    // ---------- ADD DELIVERY AGENT ----------
    if ($kind === 'agent') {
        $a = ['name' => trim($_POST['name'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''), 'branch' => (int)($_POST['branch'] ?? 0)];

        if (mb_strlen($a['name']) < 2 || mb_strlen($a['name']) > 60) {
            $errA = "Please enter the agent’s full name (2 to 60 characters).";
        } elseif (!preg_match('/^[0-9]{10}$/', $a['phone'])) {
            $errA = "Phone number must be exactly 10 digits.";
        } elseif (!in_array($a['branch'], $bids)) {
            $errA = "Please choose a branch.";
        } elseif (q("SELECT 1 FROM Delivery_Agent WHERE Phone = ?", "s", $a['phone'])) {
            $errA = "A delivery agent with this phone number already exists.";
        } else {
            try {
                $st = $conn->prepare("INSERT INTO Delivery_Agent (Name, Phone, Branch_ID) VALUES (?, ?, ?)");
                $st->bind_param("ssi", $a['name'], $a['phone'], $a['branch']);
                $st->execute();
                header("Location: add_employee.php?what=Delivery+agent&ok=" . urlencode($a['name']));
                exit;
            } catch (mysqli_sql_exception $ex) {
                $errA = "Could not add the delivery agent: " . $ex->getMessage();
            }
        }
    }
}

$brList = q("SELECT br.Branch_ID, br.Branch_Name, br.City, br.Address,
                    (SELECT COUNT(*) FROM Employee e WHERE e.Branch_ID = br.Branch_ID) AS Employees,
                    (SELECT COUNT(*) FROM Delivery_Agent a WHERE a.Branch_ID = br.Branch_ID) AS Agents
             FROM Branch br ORDER BY br.Branch_ID DESC");
$emps = q("SELECT e.Employee_ID, e.Name, e.Role, b.Branch_Name, b.City
           FROM Employee e JOIN Branch b ON b.Branch_ID = e.Branch_ID
           ORDER BY e.Employee_ID DESC");
$ags  = q("SELECT a.Agent_ID, a.Name, a.Phone, b.Branch_Name, b.City
           FROM Delivery_Agent a JOIN Branch b ON b.Branch_ID = a.Branch_ID
           ORDER BY a.Agent_ID DESC");

top("Add Staff");
?>
<h1>Add Staff</h1>
<p class="muted">Add a branch, an employee or a delivery agent. A new branch city appears automatically in the shipment forms.</p>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert ok"><?= h($_GET['what'] ?? 'Record') ?> <b><?= h($_GET['ok']) ?></b> was added.
    <?php if (($_GET['what'] ?? '') === 'Branch'): ?>Now add an employee and a delivery agent for this branch.<?php endif; ?></div>
<?php endif; ?>

<div class="two">
  <form method="post" class="panel">
    <h2>📍 Add branch</h2>
    <?php if ($errB): ?><div class="alert err"><?= h($errB) ?></div><?php endif; ?>
    <input type="hidden" name="kind" value="branch">
    <div class="formgrid">
      <div><label>Branch name</label><input type="text" name="name" value="<?= h($b['name']) ?>" maxlength="60" placeholder="e.g. Jaipur Malviya Nagar" required></div>
      <div><label>City (new delivery location)</label><input type="text" name="city" value="<?= h($b['city']) ?>" maxlength="40" placeholder="e.g. Jaipur" required></div>
      <div><label>Address (optional)</label><input type="text" name="address" value="<?= h($b['address']) ?>" maxlength="150"></div>
    </div>
    <p><button class="btn" type="submit">Add branch</button></p>
  </form>

  <form method="post" class="panel">
    <h2>🏢 Add employee</h2>
    <?php if ($errE): ?><div class="alert err"><?= h($errE) ?></div><?php endif; ?>
    <input type="hidden" name="kind" value="employee">
    <div class="formgrid">
      <div><label>Full name</label><input type="text" name="name" value="<?= h($e['name']) ?>" maxlength="60" required></div>
      <div><label>Role</label><select name="role"><?php opts($roles, $e['role']); ?></select></div>
      <div><label>Branch</label>
        <select name="branch" required><option value="">Select branch...</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= (int)$br['Branch_ID'] ?>"<?= $e['branch'] === (int)$br['Branch_ID'] ? ' selected' : '' ?>><?= h($br['Branch_Name']) ?> (<?= h($br['City']) ?>)</option>
          <?php endforeach; ?>
        </select></div>
    </div>
    <p><button class="btn" type="submit">Add employee</button></p>
  </form>

  <form method="post" class="panel">
    <h2>🚚 Add delivery agent</h2>
    <?php if ($errA): ?><div class="alert err"><?= h($errA) ?></div><?php endif; ?>
    <input type="hidden" name="kind" value="agent">
    <div class="formgrid">
      <div><label>Full name</label><input type="text" name="name" value="<?= h($a['name']) ?>" maxlength="60" required></div>
      <div><label>Phone number (10 digits)</label><input type="text" name="phone" value="<?= h($a['phone']) ?>" maxlength="10" pattern="[0-9]{10}" required></div>
      <div><label>Branch</label>
        <select name="branch" required><option value="">Select branch...</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= (int)$br['Branch_ID'] ?>"<?= $a['branch'] === (int)$br['Branch_ID'] ? ' selected' : '' ?>><?= h($br['Branch_Name']) ?> (<?= h($br['City']) ?>)</option>
          <?php endforeach; ?>
        </select></div>
    </div>
    <p><button class="btn" type="submit">Add delivery agent</button></p>
  </form>
</div>

<h2>Branches (<?= count($brList) ?>)</h2>
<?php table(['Branch_ID' => 'ID', 'Branch_Name' => 'Branch', 'City' => 'City', 'Address' => 'Address', 'Employees' => 'Employees', 'Agents' => 'Agents'], $brList); ?>

<h2>Delivery agents (<?= count($ags) ?>)</h2>
<?php table(['Agent_ID' => 'ID', 'Name' => 'Name', 'Phone' => 'Phone', 'Branch_Name' => 'Branch', 'City' => 'City'], $ags); ?>

<h2>Employees (<?= count($emps) ?>)</h2>
<?php table(['Employee_ID' => 'ID', 'Name' => 'Name', 'Role' => 'Role', 'Branch_Name' => 'Branch', 'City' => 'City'], $emps); ?>
<?php bottom(); ?>