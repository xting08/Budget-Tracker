<?php
require_once '../DB/db_connect.php';
session_start();

function show_alert_and_redirect($icon, $title, $text, $redirect) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
Swal.fire({
    icon: "' . $icon . '",
    title: "' . $title . '",
    text: "' . $text . '",
    confirmButtonColor: "#294c4b"
}).then(function(){
    window.location.href = "' . $redirect . '";
});
</script>
</body>
</html>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id']) || !isset($_POST['budget_id'])) {
    show_alert_and_redirect('info', 'Invalid Access', 'Please use the budget page to delete a budget.', '../FRONTEND/budget.php');
}

$budget_id = intval($_POST['budget_id']);
$user_id = $_SESSION['id'];
$connect = OpenCon();

// Check ownership
$query = "SELECT * FROM budgets WHERE budget_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ii", $budget_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!mysqli_fetch_assoc($result)) {
    show_alert_and_redirect('error', 'Not authorized', 'You do not own this budget.', '../FRONTEND/budget.php');
}

// Delete related transactions
$delTrans = mysqli_prepare($connect, "DELETE FROM transactions WHERE budget_id = ? AND user_id = ?");
mysqli_stmt_bind_param($delTrans, "ii", $budget_id, $user_id);
mysqli_stmt_execute($delTrans);

// Delete the budget
$delBudget = mysqli_prepare($connect, "DELETE FROM budgets WHERE budget_id = ? AND user_id = ?");
mysqli_stmt_bind_param($delBudget, "ii", $budget_id, $user_id);
mysqli_stmt_execute($delBudget);

// Success message
show_alert_and_redirect('success', 'Budget Deleted', 'The budget has been deleted.', '../FRONTEND/budget.php'); 