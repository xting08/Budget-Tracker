<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
include_once '../DB/db_connect.php';
include_once '../FUNCTION/mainFunc.inc.php';

header('Content-Type: application/json');
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    if (!isset($_SESSION['id'])) {
        throw new Exception('User not logged in');
    }

    if (!isset($connect)) {
        throw new Exception('Database connection failed.');
    }

    $userId = intval($_SESSION['id']);
    $selectedMonth = $_GET['month'] ?? date('Y-m'); // Default to current month
    $selectedYear = $_GET['year'] ?? date('Y'); // Default to current year

    // Validate month and year
    if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
        throw new Exception('Invalid month format. Use YYYY-MM format.');
    }

    list($year, $month) = explode('-', $selectedMonth);
    
    // Validate year range (current year - 1 to current year)
    $currentYear = date('Y');
    if ($year < ($currentYear - 1) || $year > $currentYear) {
        throw new Exception('Year must be within the last 2 years.');
    }

    // Validate month
    if ($month < 1 || $month > 12) {
        throw new Exception('Invalid month.');
    }

    // Check if this is the current month
    $isCurrentMonth = ($year == date('Y') && $month == date('m'));
    $currentDay = date('d');

    // Get expense by category for the selected month
    $expenseQuery = "
        SELECT c.categoryName, SUM(t.amount) as total
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = ?
        AND t.transactionType = 'expense'
        AND MONTH(t.date) = ?
        AND YEAR(t.date) = ?
        " . ($isCurrentMonth ? "AND DAY(t.date) <= ?" : "") . "
        AND (c.user_id = 0 OR c.user_id = ?)
        GROUP BY t.category_id, c.categoryName
        ORDER BY total DESC
    ";
    
    $stmt = $connect->prepare($expenseQuery);
    if (!$stmt) {
        throw new Exception('SQL statement preparation failed: ' . $connect->error);
    }
    
    if ($isCurrentMonth) {
        $stmt->bind_param("iiiii", $userId, $month, $year, $currentDay, $userId);
    } else {
        $stmt->bind_param("iiii", $userId, $month, $year, $userId);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('SQL statement execution failed: ' . $stmt->error);
    }
    
    $expenseResult = $stmt->get_result();
    
    $expenseLabels = [];
    $expenseData = [];
    $expenseColors = ['#ffbcbc', '#ffdba8', '#fff8b7', '#e3ffa3', '#b0ffb1', '#c1fffe', '#9edbec', '#9ea3ec', '#de9eec'];
    
    while ($row = $expenseResult->fetch_assoc()) {
        $expenseLabels[] = $row['categoryName'] ? $row['categoryName'] : 'Uncategorized';
        $expenseData[] = floatval($row['total']);
    }
    
    $hasExpenseData = array_sum($expenseData) > 0;

    // Get income by category for the selected month
    $incomeQuery = "
        SELECT c.categoryName, SUM(t.amount) as total
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = ?
        AND t.transactionType = 'income'
        AND MONTH(t.date) = ?
        AND YEAR(t.date) = ?
        " . ($isCurrentMonth ? "AND DAY(t.date) <= ?" : "") . "
        AND (c.user_id = 0 OR c.user_id = ?)
        GROUP BY t.category_id, c.categoryName
        ORDER BY total DESC
    ";
    
    $stmt = $connect->prepare($incomeQuery);
    if (!$stmt) {
        throw new Exception('SQL statement preparation failed: ' . $connect->error);
    }
    
    if ($isCurrentMonth) {
        $stmt->bind_param("iiiii", $userId, $month, $year, $currentDay, $userId);
    } else {
        $stmt->bind_param("iiii", $userId, $month, $year, $userId);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('SQL statement execution failed: ' . $stmt->error);
    }
    
    $incomeResult = $stmt->get_result();
    
    $incomeLabels = [];
    $incomeData = [];
    $incomeColors = ['#772e2e', '#976e33', '#ab9f34', '#7fa131', '#319832', '#2a7c7b', '#255f6f', '#161a5b', '#44154f'];
    
    while ($row = $incomeResult->fetch_assoc()) {
        $incomeLabels[] = $row['categoryName'] ? $row['categoryName'] : 'Uncategorized';
        $incomeData[] = floatval($row['total']);
    }
    
    $hasIncomeData = array_sum($incomeData) > 0;

    // Calculate percentages for expenses
    $totalExpense = array_sum($expenseData);
    $expensePercentages = [];
    foreach ($expenseData as $value) {
        $expensePercentages[] = $totalExpense > 0 ? round(($value / $totalExpense) * 100, 2) : 0;
    }

    // Calculate percentages for income
    $totalIncome = array_sum($incomeData);
    $incomePercentages = [];
    foreach ($incomeData as $value) {
        $incomePercentages[] = $totalIncome > 0 ? round(($value / $totalIncome) * 100, 2) : 0;
    }

    // Generate appropriate month name for display
    $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    if ($isCurrentMonth) {
        $monthName .= " (up to " . date('jS', mktime(0, 0, 0, $month, $currentDay, $year)) . ")";
    }

    $response = [
        'success' => true,
        'expenseLabels' => $expenseLabels,
        'expenseData' => $expenseData,
        'expenseColors' => array_slice($expenseColors, 0, count($expenseData)),
        'expensePercentages' => $expensePercentages,
        'hasExpenseData' => $hasExpenseData,
        'incomeLabels' => $incomeLabels,
        'incomeData' => $incomeData,
        'incomeColors' => array_slice($incomeColors, 0, count($incomeData)),
        'incomePercentages' => $incomePercentages,
        'hasIncomeData' => $hasIncomeData,
        'selectedMonth' => $selectedMonth,
        'monthName' => $monthName,
        'isCurrentMonth' => $isCurrentMonth
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?> 