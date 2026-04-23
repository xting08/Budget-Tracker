<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit();
    }

    include_once '../DB/db_connect.php';
    $connect = openCon();

    if (!$connect) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    $where_clause = "WHERE t.user_id = " . intval($_SESSION['id']);
    
    // Filter by transaction type
    if (isset($_POST['type']) && $_POST['type'] !== 'all') {
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        switch ($type) {
            case 'shared':
                $where_clause .= " AND t.shared_expense_id IS NOT NULL";
                break;
            case 'budget':
                $where_clause .= " AND t.budget_id IS NOT NULL AND t.saving_id IS NULL";
                break;
            case 'saving':
                $where_clause .= " AND t.saving_id IS NOT NULL AND t.budget_id IS NULL";
                break;
            default:
                $where_clause .= " AND t.transactionType = '" . $type . "'";
        }
    }

    // Filter by category
    if (isset($_POST['category']) && $_POST['category'] !== 'all') {
        $category = mysqli_real_escape_string($connect, $_POST['category']);
        if ($category === 'all_income') {
            $where_clause .= " AND t.transactionType = 'income'";
        } else if ($category === 'all_expense') {
            $where_clause .= " AND t.transactionType = 'expense'";
        } else {
            $where_clause .= " AND t.category_id = '" . $category . "'";
        }
    }
    
    // Filter by date range
    if (isset($_POST['date']) && $_POST['date'] !== 'all') {
        $date_range = mysqli_real_escape_string($connect, $_POST['date']);
        switch ($date_range) {
            case 'today':
                $where_clause .= " AND DATE(t.date) = CURDATE()";
                break;
            case 'this-week':
                $where_clause .= " AND YEARWEEK(t.date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'this-month':
                $where_clause .= " AND YEAR(t.date) = YEAR(CURDATE()) AND MONTH(t.date) = MONTH(CURDATE())";
                break;
            case 'last-month':
                $where_clause .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND t.date < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                break;
            case 'last-3-month':
                $where_clause .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                break;
            case 'this-year':
                $where_clause .= " AND YEAR(t.date) = YEAR(CURDATE())";
                break;
        }
    } else {
        // Default to current month if no date filter is specified
        $where_clause .= " AND YEAR(t.date) = YEAR(CURDATE()) AND MONTH(t.date) = MONTH(CURDATE())";
    }
    
    // Construct the SQL query
    $sql = "SELECT t.transactionID, t.transactionName, t.amount, t.date, t.transactionType, 
            t.shared_expense_id, t.budget_id, t.saving_id, c.categoryName, c.transactionType as categoryType
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.category_id
            $where_clause 
            ORDER BY t.date DESC, t.transactionType ASC";

    // Execute the query
    $result = mysqli_query($connect, $sql);

    if (!$result) {
        echo json_encode([
            'error' => 'Database error',
            'details' => mysqli_error($connect),
            'sql' => $sql,
            'where_clause' => $where_clause
        ]);
        exit();
    }

    // Calculate totals for filtered transactions
    $totalSql = "SELECT 
                    COALESCE(SUM(CASE WHEN t.transactionType = 'income' THEN t.amount ELSE 0 END), 0) as totalIncome,
                    COALESCE(SUM(CASE WHEN t.transactionType = 'expense' THEN t.amount ELSE 0 END), 0) as totalExpense
                 FROM transactions t
                 LEFT JOIN categories c ON t.category_id = c.category_id
                 $where_clause";
    
    $totalResult = mysqli_query($connect, $totalSql);
    if (!$totalResult) {
        echo json_encode([
            'error' => 'Error calculating totals',
            'details' => mysqli_error($connect),
            'sql' => $totalSql
        ]);
        exit();
    }

    $totals = mysqli_fetch_assoc($totalResult);
    $filteredIncome = floatval($totals['totalIncome']);
    $filteredExpense = floatval($totals['totalExpense']);

    $transactions = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = [
                'id' => $row['transactionID'],
                'name' => $row['transactionName'],
                'amount' => number_format($row['amount'], 2),
                'date' => $row['date'],
                'type' => $row['transactionType'],
                'isShared' => !empty($row['shared_expense_id']),
                'isBudget' => !empty($row['budget_id']),
                'isSaving' => !empty($row['saving_id'])
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'transactions' => $transactions,
        'totals' => [
            'income' => number_format($filteredIncome, 2),
            'expense' => number_format($filteredExpense, 2),
            'balance' => number_format($filteredIncome - $filteredExpense, 2)
        ]
    ]);
    exit();
?>