<?php
include '../FUNCTION/mainFunc.inc.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$connect = OpenCon();
$userId = $_SESSION['id'];

// Get active saving goals with their usage
$query = "SELECT s.saving_id as id, s.savingName as name, s.targetAmount as target, s.currentAmount as current
          FROM savinggoals s
          WHERE s.user_id = ? 
          AND s.endDate >= CURDATE()";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$savings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $savings[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'target' => number_format($row['target'], 2),
        'current' => number_format($row['current'], 2)
    ];
}

echo json_encode(['savings' => $savings]);
?> 