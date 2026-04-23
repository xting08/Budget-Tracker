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

    // Get the number of days in the selected month
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
    
    // For current month, limit to current day
    $maxDay = $isCurrentMonth ? $currentDay : $daysInMonth;

    $dailyLabels = [];
    $dailyData = [];

    // Generate all days from 1st to max day of the month
    for ($day = 1; $day <= $maxDay; $day++) {
        $dateString = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $dailyLabels[] = date('d M', strtotime($dateString));
        $dailyData[] = 0; // Initialize with 0
    }

    // Get daily expense data from database
    $dailyExpenseQuery = "
        SELECT DAY(date) as transaction_day, SUM(amount) as total_amount
        FROM transactions
        WHERE user_id = ?
        AND transactionType = 'expense'
        AND MONTH(date) = ?
        AND YEAR(date) = ?
        " . ($isCurrentMonth ? "AND DAY(date) <= ?" : "") . "
        GROUP BY DAY(date)
        ORDER BY DAY(date) ASC
    ";
    
    $stmt = $connect->prepare($dailyExpenseQuery);
    if (!$stmt) {
        throw new Exception('SQL statement preparation failed: ' . $connect->error);
    }
    
    if ($isCurrentMonth) {
        $stmt->bind_param("iiii", $userId, $month, $year, $currentDay);
    } else {
        $stmt->bind_param("iii", $userId, $month, $year);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('SQL statement execution failed: ' . $stmt->error);
    }
    
    $dailyExpenseResult = $stmt->get_result();
    
    if($dailyExpenseResult) {
        while($row = $dailyExpenseResult->fetch_assoc()){
            $dayIndex = intval($row['transaction_day']) - 1;
            if(isset($dailyData[$dayIndex])) {
                $dailyData[$dayIndex] = floatval($row['total_amount']);
            }
        }
    }
    
    $hasDailyData = count(array_filter($dailyData)) > 0;

    // Generate month name for display
    $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    if ($isCurrentMonth) {
        $monthName .= " (up to " . date('jS', mktime(0, 0, 0, $month, $currentDay, $year)) . ")";
    }

    $response = [
        'success' => true,
        'dailyLabels' => $dailyLabels,
        'dailyData' => $dailyData,
        'hasDailyData' => $hasDailyData,
        'selectedMonth' => $selectedMonth,
        'monthName' => $monthName,
        'isCurrentMonth' => $isCurrentMonth
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?> 