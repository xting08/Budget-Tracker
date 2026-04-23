<?php
require_once '../DB/db_connect.php';
session_start();

if (!isset($_SESSION['id']) || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing ID']);
    exit();
}

$shared_expense_id = intval($_POST['id']);
$user_id = $_SESSION['id'];
$connect = OpenCon();

// Only allow the creator to delete
$query = "SELECT * FROM shareexpenses WHERE shareExpense_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ii", $shared_expense_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Delete related records
mysqli_query($connect, "DELETE FROM shared_expense_recipients WHERE shared_expense_id = $shared_expense_id");
mysqli_query($connect, "DELETE FROM shareexpenses WHERE shareExpense_id = $shared_expense_id");

echo json_encode(['success' => true]);
exit(); 