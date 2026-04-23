<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
include_once __DIR__ . '/../DB/db_connect.php';
include_once __DIR__ . '/mainFunc.inc.php';

header('Content-Type: application/json');
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

    // Function to call Gemini AI
    function callGeminiAI($prompt) {
        $apiKey = 'AIzaSyDY4BAnM3HxhOC5E0nNX1QRVp2GVW0y7_A';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Failed to call Gemini AI API');
        }

        $response = json_decode($result, true);
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        } else {
            throw new Exception('Invalid response from Gemini AI');
        }
    }

    // Function to get user's comprehensive financial data
    function getUserFinancialData($connect, $userId) {
        $data = [];
        
        // Get current month's transactions
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentDay = date('d');
        
        // Current month expenses by category
        $expenseQuery = "
            SELECT c.categoryName, SUM(t.amount) as total, COUNT(*) as count
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.category_id
            WHERE t.user_id = ?
            AND t.transactionType = 'expense'
            AND MONTH(t.date) = ?
            AND YEAR(t.date) = ?
            AND DAY(t.date) <= ?
            AND (c.user_id = 0 OR c.user_id = ?)
            GROUP BY t.category_id
            ORDER BY total DESC
        ";
        
        $stmt = $connect->prepare($expenseQuery);
        $stmt->bind_param("iiiii", $userId, $currentMonth, $currentYear, $currentDay, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['current_month_expenses'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['current_month_expenses'][] = $row;
        }
        
        // Previous month expenses for comparison
        $prevMonth = $currentMonth - 1;
        $prevYear = $currentYear;
        if ($prevMonth <= 0) {
            $prevMonth = 12;
            $prevYear--;
        }
        
        $prevExpenseQuery = "
            SELECT c.categoryName, SUM(t.amount) as total
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.category_id
            WHERE t.user_id = ?
            AND t.transactionType = 'expense'
            AND MONTH(t.date) = ?
            AND YEAR(t.date) = ?
            AND (c.user_id = 0 OR c.user_id = ?)
            GROUP BY t.category_id
        ";
        
        $stmt = $connect->prepare($prevExpenseQuery);
        $stmt->bind_param("iiii", $userId, $prevMonth, $prevYear, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['previous_month_expenses'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['previous_month_expenses'][] = $row;
        }
        
        // Get user's budgets
        $budgetQuery = "
            SELECT b.budgetName, b.amountLimit, b.startDate, b.endDate, c.categoryName
            FROM budgets b
            LEFT JOIN categories c ON b.category_id = c.category_id
            WHERE b.user_id = ?
            AND b.startDate <= CURDATE()
            AND b.endDate >= CURDATE()
        ";
        
        $stmt = $connect->prepare($budgetQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['active_budgets'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['active_budgets'][] = $row;
        }
        
        // Get user's saving goals
        $savingQuery = "
            SELECT savingName, targetAmount, currentAmount, endDate
            FROM savinggoals
            WHERE user_id = ?
            AND endDate >= CURDATE()
            ORDER BY endDate ASC
        ";
        
        $stmt = $connect->prepare($savingQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['saving_goals'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['saving_goals'][] = $row;
        }
        
        // Get shared expenses
        $sharedQuery = "
            SELECT se.description, se.amount, se.date_created, se.status
            FROM shareexpenses se
            WHERE se.user_id = ?
            AND se.status IN ('pending', 'accepted')
            ORDER BY se.date_created ASC
        ";
        
        $stmt = $connect->prepare($sharedQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data['shared_expenses'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['shared_expenses'][] = $row;
        }
        
        // Get total income for current month
        $incomeQuery = "
            SELECT SUM(t.amount) as total_income
            FROM transactions t
            WHERE t.user_id = ?
            AND t.transactionType = 'income'
            AND MONTH(t.date) = ?
            AND YEAR(t.date) = ?
            AND DAY(t.date) <= ?
        ";
        
        $stmt = $connect->prepare($incomeQuery);
        $stmt->bind_param("iiii", $userId, $currentMonth, $currentYear, $currentDay);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $data['current_month_income'] = $row['total_income'] ?? 0;
        
        return $data;
    }

    // Function to generate expense control suggestions using Gemini AI
    function generateExpenseSuggestions($financialData) {
        $prompt = "You are a financial advisor AI. Analyze the user's financial data and provide 4 personalized expense control suggestions.

User's Financial Data:
" . json_encode($financialData, JSON_PRETTY_PRINT) . "

Instructions:
1. Analyze the user's spending patterns, budgets, saving goals, and shared expenses
2. Provide 4 different types of suggestions:
   - Budget-related suggestions (if they're exceeding budgets)
   - Category-specific suggestions (if certain categories are high)
   - Goal-driven suggestions (if they have saving goals)
   - General financial health suggestions

3. Each suggestion should have:
   - A clear, concise title (max 50 characters)
   - A helpful, actionable suggestion (max 200 characters)
   - Be specific to their data
   - Use friendly, encouraging tone
   - Include specific amounts when relevant

4. Format your response as a JSON array with exactly 4 objects:
[
  {
    \"title\": \"Situation Title\",
    \"suggestion\": \"Specific suggestion text\"
  },
  {
    \"title\": \"Situation Title\", 
    \"suggestion\": \"Specific suggestion text\"
  },
  {
    \"title\": \"Situation Title\",
    \"suggestion\": \"Specific suggestion text\"
  },
  {
    \"title\": \"Situation Title\",
    \"suggestion\": \"Specific suggestion text\"
  }
]

5. If the user has no transaction data, provide general financial tips.
6. Focus on actionable, realistic advice.
7. Use Malaysian Ringgit (RM) for currency.
8. Be encouraging and positive in tone.";

        $aiResponse = callGeminiAI($prompt);
        
        // Clean the response to extract only the JSON part
        $jsonResponse = preg_replace('/```json\s*|\s*```/', '', $aiResponse);
        $suggestions = json_decode($jsonResponse, true);

        if (!$suggestions || !is_array($suggestions) || count($suggestions) !== 4) {
            // Fallback suggestions if AI fails
            $suggestions = [
                [
                    "title" => "Track Your Spending",
                    "suggestion" => "Start by tracking all your daily expenses to identify where your money goes."
                ],
                [
                    "title" => "Set a Budget",
                    "suggestion" => "Create a monthly budget for different categories to control your spending."
                ],
                [
                    "title" => "Save Regularly",
                    "suggestion" => "Set aside 20% of your income for savings and emergency funds."
                ],
                [
                    "title" => "Review Subscriptions",
                    "suggestion" => "Check for unused subscriptions and cancel them to save money monthly."
                ]
            ];
        }

        return $suggestions;
    }

    // Get user's financial data
    $financialData = getUserFinancialData($connect, $userId);
    
    // Generate suggestions
    $suggestions = generateExpenseSuggestions($financialData);
    
    $response = [
        'success' => true,
        'suggestions' => $suggestions,
        'financial_data' => $financialData // Optional: for debugging on the client-side
    ];

    echo json_encode($response);

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}
?> 