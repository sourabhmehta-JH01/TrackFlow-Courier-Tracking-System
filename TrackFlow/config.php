<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("127.0.0.1", "trackflow", "track123", "TrackFlow", 3306);
$conn->set_charset("utf8mb4");

// make text safe to print in HTML
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// run a prepared SELECT and return all rows
function q($sql, $types = "", ...$params) {
    global $conn;
    $st = $conn->prepare($sql);
    if ($types !== "") { $st->bind_param($types, ...$params); }
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

// coloured status label
function badge($s) {
    $s = (string)$s;
    return '<span class="badge st-' . strtolower(str_replace(' ', '-', $s)) . '">' . h($s) . '</span>';
}

function stat_card($label, $value) {
    echo '<div class="stat"><div class="num">' . h($value) . '</div><div class="lbl">' . h($label) . '</div></div>';
}

// <option> list for a dropdown
function opts($list, $sel) {
    foreach ($list as $o) { echo '<option' . ($o === $sel ? ' selected' : '') . '>' . h($o) . '</option>'; }
}

// $cols = ['sql_column' => 'Heading'], $badges = columns shown as status badges
function table($cols, $rows, $badges = []) {
    echo '<div class="scroll"><table><tr>';
    foreach ($cols as $label) { echo '<th>' . h($label) . '</th>'; }
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $k => $label) {
            echo '<td>' . (in_array($k, $badges) ? badge($r[$k]) : h($r[$k])) . '</td>';
        }
        echo '</tr>';
    }
    if (!$rows) { echo '<tr><td colspan="' . count($cols) . '" class="muted">No records found.</td></tr>'; }
    echo '</table></div>';
}


function top($title) {
    $page  = basename($_SERVER['PHP_SELF']);
    if ($page === 'update_shipment.php') { $page = 'employee.php'; }
       $links = ['index.php' => 'Dashboard', 'create_shipment.php' => 'Create Shipment',
              'track.php' => 'Track Shipment', 'add_employee.php' => 'Add Staff',
              'employee.php?leave=1' => 'Staff Portal'];
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'  
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . h($title) . ' | TrackFlow</title><link rel="stylesheet" href="style.css"></head><body>'
       . '<header class="top"><a class="brand" href="index.php">Track<span>Flow</span></a><nav>';
    foreach ($links as $file => $label) {
        echo '<a href="' . $file . '"' . (strtok($file, '?') === $page ? ' class="on"' : '') . '>' . h($label) . '</a>';
    }
    echo '</nav></header><main>';
}

function bottom() {
    echo '</main><footer>TrackFlow · Courier Tracking System · DBMS Project</footer></body></html>';
}