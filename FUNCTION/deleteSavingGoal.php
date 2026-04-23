<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../DB/db_connect.php';
$connect = OpenCon();

if (isset($_POST['saving_id']) && isset($_SESSION['id'])) {
    $saving_id = intval($_POST['saving_id']);
    $user_id = $_SESSION['id'];
    $stmt = mysqli_prepare($connect, "DELETE FROM savinggoals WHERE saving_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $saving_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
header("Location: ../FRONTEND/savingGoals.php");
exit(); 