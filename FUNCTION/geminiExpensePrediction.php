<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
include_once '../DB/db_connect.php';
include_once '../FUNCTION/mainFunc.inc.php';

// Function to handle the main prediction logic
function handlePredictionRequest($connect, $userId, $period) {
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    try {
        if (!isset($_SESSION['id'])) {
            throw new Exception('User not logged in');
        }

        if (!isset($connect)) {
            throw new Exception('Database connection failed.');
        }

        // --- Caching Logic Start ---
        $cacheDir = __DIR__ . '/prediction_cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . "/prediction_user_{$userId}_period_{$period}.json";

        $currentDay = date('j');
        $updateWindowStartDay = 25;

        // Always get fresh actual data
        $actualExpenseData = getActualExpenseData($connect, $userId, $period);
        $labels = $actualExpenseData['labels'];
        $data = $actualExpenseData['data'];
        $tooltips = $actualExpenseData['tooltips'];

        $currentMonth = date('n');
        $currentYear = date('Y');

        $prediction_period_label = getPredictionForNextPeriod($period, $currentMonth, $currentYear);
        $labels[] = $prediction_period_label;
        $tooltips[] = "Predicted Expense";
        $data[] = null; 

        // Check if we can use cached predictions
        $cachedPredictions = null;
        if ($currentDay < $updateWindowStartDay && file_exists($cacheFile)) {
            $cachedResponse = json_decode(file_get_contents($cacheFile), true);
            if ($cachedResponse && isset($cachedResponse['success']) && $cachedResponse['success'] && isset($cachedResponse['predictedData'])) {
                $cachedPredictions = $cachedResponse['predictedData'];
            }
        }

        // Generate predictions (either from cache or AI)
        if ($cachedPredictions !== null) {
            $predictedData = $cachedPredictions;
            $fromCache = true;
        } else {
            $transactions = getUserDetailedTransactionHistory($connect, $userId, 12);
            $predictedData = generateAIPredictions($transactions, $period, $labels);
            $fromCache = false;
        }

        $response = [
            'success' => true,
            'labels' => $labels,
            'data' => $data,
            'predictedData' => $predictedData,
            'tooltips' => $tooltips,
            'period' => $period,
            'fromCache' => $fromCache
        ];

        // Cache only the predictions for future use
        if (!$fromCache) {
            $cacheData = [
                'success' => true,
                'predictedData' => $predictedData,
                'period' => $period,
                'cachedAt' => date('Y-m-d H:i:s')
            ];
            file_put_contents($cacheFile, json_encode($cacheData));
        }
        // --- Caching Logic End ---

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    return $response;
}

// Only execute the main logic if this file is called directly (not included)
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $userId = intval($_SESSION['id']);
    $period = $_GET['period'] ?? '1M';
    
    $response = handlePredictionRequest($connect, $userId, $period);
    echo json_encode($response);
    exit();
}

// Function to call Gemini AI
function callGeminiAI($prompt) {
    // Actual Gemini API call - using the same key as in BACKEND/app.py
    $apiKey = "AIzaSyDY4BAnM3HxhOC5E0nNX1QRVp2GVW0y7_A";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=$apiKey";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
        ]
    ];

    // Use cURL instead of file_get_contents for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($result === FALSE) {
        throw new Exception('cURL Error: ' . $curlError);
    }
    
    if ($httpCode === 429) {
        throw new Exception('API_QUOTA_EXCEEDED');
    }
    
    if ($httpCode !== 200) {
        throw new Exception('HTTP Error: ' . $httpCode . ' - ' . $result);
    }

    $response = json_decode($result, true);
    
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        return $response['candidates'][0]['content']['parts'][0]['text'];
    } else {
        throw new Exception('Invalid response from Gemini AI: ' . print_r($response, true));
    }
}

// Function to get detailed user transaction history for AI analysis
function getUserDetailedTransactionHistory($connect, $userId, $months = 12) {
    $sql = "SELECT 
                t.amount,
                t.date,
                t.description,
                c.categoryName,
                MONTH(t.date) as month,
                YEAR(t.date) as year,
                DAY(t.date) as day,
                DAYOFWEEK(t.date) as day_of_week
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.category_id
            WHERE t.user_id = ? 
            AND t.transactionType = 'expense'
            AND t.date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            ORDER BY t.date DESC";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL statement preparation failed: ' . $connect->error);
    }
    
    $stmt->bind_param("ii", $userId, $months);
    
    if (!$stmt->execute()) {
        throw new Exception('SQL statement execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $transactions = [];
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

// Function to analyze spending patterns and generate AI-powered predictions
function generateAIPredictions($transactions, $period, $labels) {
    if (empty($transactions)) {
        return array_fill(0, count($labels), 0);
    }

    // Calculate basic statistics
    $totalAmount = array_sum(array_column($transactions, 'amount'));
    $avgAmount = $totalAmount > 0 && count($transactions) > 0 ? $totalAmount / count($transactions) : 0;
    $monthlyAverages = [];
    $categoryAverages = [];
    
    foreach ($transactions as $transaction) {
        $monthKey = $transaction['year'] . '-' . str_pad($transaction['month'], 2, '0', STR_PAD_LEFT);
        $category = $transaction['categoryName'] ?: 'Uncategorized';
        
        if (!isset($monthlyAverages[$monthKey])) $monthlyAverages[$monthKey] = 0;
        if (!isset($categoryAverages[$category])) $categoryAverages[$category] = 0;
        
        $monthlyAverages[$monthKey] += $transaction['amount'];
        $categoryAverages[$category] += $transaction['amount'];
    }
    
    $avgMonthlyExpense = count($monthlyAverages) > 0 ? array_sum($monthlyAverages) / count($monthlyAverages) : 0;
    
    $analysisData = [
        'average_monthly_expense' => $avgMonthlyExpense,
        'monthly_patterns' => $monthlyAverages,
        'recent_trend' => array_slice($monthlyAverages, -3, 3, true)
    ];

    // Try to call Gemini AI first
    try {
        // Create AI prompt
        $prompt = "You are an expert financial analyst AI. Your task is to create a realistic spending forecast based on the user's historical data.

Periods to Predict: " . json_encode($labels) . "

User's Historical Spending Data (by month):
" . json_encode($analysisData['monthly_patterns']) . "

Prediction Instructions:
Your forecast must be dynamic and reflect real-world spending habits. For each future period, you must:
1.  **Analyze Seasonality:** Examine the monthly historical data. Identify months or seasons with consistently higher or lower spending (e.g., holidays in December, vacations in summer). Your predictions for future periods must incorporate these seasonal patterns. For example, a Q4 (Oct-Dec) prediction should likely be higher than a Q3 (Jul-Sep) prediction if the user has a history of holiday spending.
2.  **Analyze Trends:** Is the user's overall spending trending up or down over the past year? Project this general trend into your forecast.
3.  **Provide Varied Predictions:** You MUST NOT return the same average value for every period. Each prediction must be a unique calculation based on the specific months within that period and the overall trends. A flat line is an incorrect response.

Your response MUST be a single, valid JSON object and nothing else.
The JSON object must have a key 'predicted_amounts', which holds an array of numerical values. The number of values must exactly match the number of periods to predict.

Example of a GOOD response (varied amounts):
{\"predicted_amounts\": [1350.50, 1420.00, 1380.75, 1600.00]}

Example of a BAD response (flat line):
{\"predicted_amounts\": [1437.81, 1437.81, 1437.81, 1437.81]}";

        // Call Gemini AI
        $aiResponse = callGeminiAI($prompt);
        
        // Clean the response to extract only the JSON part
        $jsonResponse = preg_replace('/```json\s*|\s*```/', '', $aiResponse);
        $aiData = json_decode($jsonResponse, true);

        $predictedAmounts = isset($aiData['predicted_amounts']) ? $aiData['predicted_amounts'] : null;

        if ($predictedAmounts !== null && is_array($predictedAmounts) && count($predictedAmounts) === count($labels)) {
            return $predictedAmounts;
        }
    } catch (Exception $e) {
        // If API quota exceeded or other error, fall back to statistical predictions
        if (strpos($e->getMessage(), 'API_QUOTA_EXCEEDED') !== false) {
            // Log the quota exceeded for debugging
            error_log("Gemini API quota exceeded, using fallback predictions");
        }
    }

    // Fallback to statistical predictions when AI is unavailable
    $fallback_multiplier = 1;
    switch ($period) {
        case '3M': $fallback_multiplier = 3; break;
        case '6M': $fallback_multiplier = 6; break;
        case '1Y': $fallback_multiplier = 12; break;
    }
    
    $avgExpensePerPeriod = $avgMonthlyExpense * $fallback_multiplier;
    if($period === '1Y') {
        $avgExpensePerPeriod = $avgMonthlyExpense * 12;
    }
    
    // Generate varied predictions based on recent trends
    $predictedAmounts = [];
    $recentMonths = array_values(array_slice($monthlyAverages, -3, 3, true));
    $trendFactor = count($recentMonths) > 1 ? ($recentMonths[count($recentMonths)-1] - $recentMonths[0]) / count($recentMonths) : 0;
    
    for ($i = 0; $i < count($labels); $i++) {
        // Add some variation based on trend and random factor
        $variation = $trendFactor * ($i + 1) + (rand(-10, 10) / 100 * $avgExpensePerPeriod);
        $predictedAmounts[] = round($avgExpensePerPeriod + $variation, 2);
    }
    
    return $predictedAmounts;
}

// Function to get actual historical expense data
function getActualExpenseData($connect, $userId, $period) {
    $labels = [];
    $data = [];
    $tooltips = [];
    $currentYear = date('Y');
    $currentMonth = date('n');
    $currentDay = date('d');

    switch ($period) {
        case '1M':
            // Monthly aggregation - current year from January to current month
            for ($month = 1; $month <= $currentMonth; $month++) {
                $monthName = date('M Y', mktime(0, 0, 0, $month, 1, $currentYear));
                $monthNameTooltip = $monthName;
                if ($month == $currentMonth) {
                    $monthNameTooltip .= " (up to " . date('jS', mktime(0, 0, 0, $month, $currentDay, $currentYear)) . ")";
                }
                $labels[] = $monthName;
                $tooltips[] = $monthNameTooltip;
                $data[] = 0;
            }
            
            $sql = "SELECT YEAR(date) as year, MONTH(date) as month, SUM(amount) as total 
                    FROM transactions 
                    WHERE user_id = ? AND transactionType = 'expense' 
                    AND YEAR(date) = ? AND MONTH(date) BETWEEN 1 AND ?
                    AND (MONTH(date) < ? OR (MONTH(date) = ? AND DAY(date) <= ?))
                    GROUP BY year, month 
                    ORDER BY year, month ASC";
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iiiiii", $userId, $currentYear, $currentMonth, $currentMonth, $currentMonth, $currentDay);
            break;

        case '3M':
            // Quarterly aggregation
            $startYear = $currentYear - 1;
            $currentQuarter = ceil($currentMonth / 3);
            
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $maxQuarter = ($year == $currentYear) ? $currentQuarter : 4;
                for ($quarter = 1; $quarter <= $maxQuarter; $quarter++) {
                    $quarterNames = ['Jan - Mar', 'Apr - Jun', 'Jul - Sep', 'Oct - Dec'];
                    $quarterLabel = $quarterNames[$quarter - 1] . ' ' . $year;
                    $quarterLabelTooltip = $quarterLabel;
                    if ($year == $currentYear && $quarter == $currentQuarter) {
                        $quarterLabelTooltip .= " (up to " . date('jS', mktime(0, 0, 0, $currentMonth, $currentDay, $currentYear)) . ")";
                    }
                    
                    $labels[] = $quarterLabel;
                    $tooltips[] = $quarterLabelTooltip;
                    $data[] = 0;
                }
            }
            
            $sql = "SELECT 
                        YEAR(date) as year,
                        CASE 
                            WHEN MONTH(date) BETWEEN 1 AND 3 THEN 1
                            WHEN MONTH(date) BETWEEN 4 AND 6 THEN 2
                            WHEN MONTH(date) BETWEEN 7 AND 9 THEN 3
                            WHEN MONTH(date) BETWEEN 10 AND 12 THEN 4
                        END as quarter,
                        SUM(amount) as total
                    FROM transactions 
                    WHERE user_id = ? AND transactionType = 'expense' 
                    AND YEAR(date) >= ? AND YEAR(date) <= ?
                    AND (YEAR(date) < ? OR (YEAR(date) = ? AND (
                        (MONTH(date) < ?) OR 
                        (MONTH(date) = ? AND DAY(date) <= ?)
                    )))
                    GROUP BY year, quarter 
                    ORDER BY year, quarter ASC";
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iiiiiiii", $userId, $startYear, $currentYear, $currentYear, $currentYear, $currentMonth, $currentMonth, $currentDay);
            break;

        case '6M':
            // Semi-annual aggregation
            $startYear = $currentYear - 2;
            $currentHalfYear = ($currentMonth <= 6) ? 1 : 2;
            
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $maxHalfYear = ($year == $currentYear) ? $currentHalfYear : 2;
                for ($halfYear = 1; $halfYear <= $maxHalfYear; $halfYear++) {
                    $halfYearNames = ['Jan - Jun', 'Jul - Dec'];
                    $halfYearLabel = $halfYearNames[$halfYear - 1] . ' ' . $year;
                    $halfYearLabelTooltip = $halfYearLabel;
                    if ($year == $currentYear && $halfYear == $currentHalfYear) {
                        $halfYearLabelTooltip .= " (up to " . date('jS', mktime(0, 0, 0, $currentMonth, $currentDay, $currentYear)) . ")";
                    }
                    
                    $labels[] = $halfYearLabel;
                    $tooltips[] = $halfYearLabelTooltip;
                    $data[] = 0;
                }
            }
            
            $sql = "SELECT 
                        YEAR(date) as year,
                        CASE 
                            WHEN MONTH(date) BETWEEN 1 AND 6 THEN 1
                            WHEN MONTH(date) BETWEEN 7 AND 12 THEN 2
                        END as half_year,
                        SUM(amount) as total
                    FROM transactions 
                    WHERE user_id = ? AND transactionType = 'expense' 
                    AND YEAR(date) >= ? AND YEAR(date) <= ?
                    AND (YEAR(date) < ? OR (YEAR(date) = ? AND (
                        (MONTH(date) < ?) OR 
                        (MONTH(date) = ? AND DAY(date) <= ?)
                    )))
                    GROUP BY year, half_year 
                    ORDER BY year, half_year ASC";
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iiiiiiii", $userId, $startYear, $currentYear, $currentYear, $currentYear, $currentMonth, $currentMonth, $currentDay);
            break;

        case '1Y':
            // Yearly aggregation
            $startYear = $currentYear - 2;
            
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $yearLabel = $year;
                $yearLabelTooltip = $yearLabel;
                if ($year == $currentYear) {
                    $yearLabelTooltip .= " (up to " . date('jS', mktime(0, 0, 0, $currentMonth, $currentDay, $currentYear)) . ")";
                }
                $labels[] = $yearLabel;
                $tooltips[] = $yearLabelTooltip;
                $data[] = 0;
            }
            
            $sql = "SELECT YEAR(date) as year, SUM(amount) as total 
                    FROM transactions 
                    WHERE user_id = ? AND transactionType = 'expense' 
                    AND YEAR(date) >= ? AND YEAR(date) <= ?
                    AND (YEAR(date) < ? OR (YEAR(date) = ? AND (
                        (MONTH(date) < ?) OR 
                        (MONTH(date) = ? AND DAY(date) <= ?)
                    )))
                    GROUP BY year 
                    ORDER BY year ASC";
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iiiiiiii", $userId, $startYear, $currentYear, $currentYear, $currentYear, $currentMonth, $currentMonth, $currentDay);
            break;

        default:
            throw new Exception('Invalid period specified.');
    }

    if (!$stmt->execute()) {
        throw new Exception('SQL statement execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    
    switch ($period) {
        case '1M':
            while ($row = $result->fetch_assoc()) {
                $monthIndex = $row['month'] - 1;
                $data[$monthIndex] = floatval($row['total']);
            }
            break;
        case '3M':
            $startYear = $currentYear - 1;
            while ($row = $result->fetch_assoc()) {
                $yearIndex = $row['year'] - $startYear;
                $quarterIndex = $row['quarter'] - 1;
                $dataIndex = ($yearIndex * 4) + $quarterIndex;
                if ($dataIndex < count($data)) {
                    $data[$dataIndex] = floatval($row['total']);
                }
            }
            break;
        case '6M':
             $startYear = $currentYear - 2;
            while ($row = $result->fetch_assoc()) {
                $yearIndex = $row['year'] - $startYear;
                $halfYearIndex = $row['half_year'] - 1;
                $dataIndex = ($yearIndex * 2) + $halfYearIndex;
                if ($dataIndex < count($data)) {
                    $data[$dataIndex] = floatval($row['total']);
                }
            }
            break;
        case '1Y':
             $startYear = $currentYear - 2;
            while ($row = $result->fetch_assoc()) {
                $yearIndex = $row['year'] - $startYear;
                if ($yearIndex < count($data)) {
                    $data[$yearIndex] = floatval($row['total']);
                }
            }
            break;
    }

    return ['labels' => $labels, 
            'data' => $data,
            'tooltips' => $tooltips 
           ];
}

function getPredictionForNextPeriod($period, $currentMonth, $currentYear) {
    switch ($period) {
        case '1M':
            return date('M Y', mktime(0, 0, 0, $currentMonth + 1, 1, $currentYear));
        case '3M':
            $startNextQuarter = new DateTime("$currentYear-$currentMonth-01");
            $startNextQuarter->modify('first day of next month');
            $endNextQuarter = clone $startNextQuarter;
            $endNextQuarter->modify('+2 months');
            return $startNextQuarter->format('M') . ' - ' . $endNextQuarter->format('M Y');
        case '6M':
            $startNextPeriod = new DateTime("$currentYear-$currentMonth-01");
            $startNextPeriod->modify('first day of next month');
            $endNextPeriod = clone $startNextPeriod;
            $endNextPeriod->modify('+5 months');
            return $startNextPeriod->format('M') . ' - ' . $endNextPeriod->format('M Y');
        case '1Y':
            return $currentYear + 1;
    }
    return 'Prediction';
}
?> 