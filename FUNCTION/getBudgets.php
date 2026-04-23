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

    // Get active budgets with their usage
    $query = "SELECT b.budget_id as id, b.budgetName as name, b.amountLimit as `limit`, 
              COALESCE(SUM(t.amount), 0) as used
              FROM budgets b
              LEFT JOIN transactions t ON b.budget_id = t.budget_id
              WHERE b.user_id = ? 
              AND b.endDate >= CURDATE()
              GROUP BY b.budget_id";

    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $budgets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $budgets[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'limit' => number_format($row['limit'], 2),
            'used' => number_format($row['used'], 2)
        ];
    }

    echo json_encode(['budgets' => $budgets]);
?> 