<?php
session_start();
include_once '../DB/db_connect.php';
include_once '../FUNCTION/mainFunc.inc.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$userId = intval($_SESSION['id']);
$period = $_GET['period'] ?? '1M';

// Get user's name for personalization
$userName = $_SESSION['username'] ?? 'User';

// Get prediction data for the selected period
$predictionData = null;
try {
    // Include the prediction functions directly
    include_once '../FUNCTION/geminiExpensePrediction.php';
    
    // Call the prediction function directly
    $predictionResponse = handlePredictionRequest($connect, $userId, $period);
    
    if ($predictionResponse['success']) {
        $predictionData = [
            'success' => true,
            'predicted_expense' => end($predictionResponse['predictedData']),
            'labels' => $predictionResponse['labels'],
            'data' => $predictionResponse['data'],
            'predictedData' => $predictionResponse['predictedData']
        ];
    }
} catch (Exception $e) {
    // Handle error silently
}

// Get user's actual spending data for comparison
$actualSpending = [];
$monthlyAverages = [];
try {
    $spendingQuery = "SELECT 
                        DATE_FORMAT(date, '%Y-%m') as month,
                        SUM(amount) as total_spent,
                        COUNT(*) as transaction_count
                      FROM transactions 
                      WHERE user_id = ? AND transactionType = 'expense'
                      GROUP BY DATE_FORMAT(date, '%Y-%m')
                      ORDER BY month DESC
                      LIMIT 12";
    $stmt = $connect->prepare($spendingQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $actualSpending[] = $row;
        $monthlyAverages[] = $row['total_spent'];
    }
} catch (Exception $e) {
    // Handle error silently
}

$averageMonthlySpending = !empty($monthlyAverages) ? array_sum($monthlyAverages) / count($monthlyAverages) : 0;
$highestSpending = !empty($monthlyAverages) ? max($monthlyAverages) : 0;
$lowestSpending = !empty($monthlyAverages) ? min($monthlyAverages) : 0;

// Get top spending categories
$topCategories = [];
try {
    $categoryQuery = "SELECT 
                        c.categoryName,
                        SUM(t.amount) as total_spent
                      FROM transactions t
                      JOIN categories c ON t.category_id = c.category_id
                      WHERE t.user_id = ? AND t.transactionType = 'expense'
                      GROUP BY c.category_id, c.categoryName
                      ORDER BY total_spent DESC
                      LIMIT 5";
    $stmt = $connect->prepare($categoryQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $topCategories[] = $row;
    }
} catch (Exception $e) {
    // Handle error silently
}

// Get period label
$periodLabels = [
    '1M' => 'Monthly',
    '3M' => 'Quarterly', 
    '6M' => 'Semi-Annual',
    '1Y' => 'Yearly'
];
$periodLabel = $periodLabels[$period] ?? 'Monthly';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Explanation - Cash Compass</title>
    <link rel="stylesheet" href="../CSS/sideNav.css">
    <link rel="stylesheet" href="../CSS/set.css">
    <link rel="stylesheet" href="../CSS/main.css">
    <link rel="stylesheet" href="../CSS/predictionExplanation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../IMG/favicon.png">
</head>
<body>
    <?php include '../FUNCTION/sideNav.inc.php'; ?>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>PREDICTION EXPLANATION</a>
            <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                <button id="logout-btn" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </span>
        <hr />
        
        <section id="explanation-container">
            <div class="explanation-header">
                <div class="back-button">
                    <a href="main.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <div class="period-selector">
                    <label for="period-select">View Explanation for:</label>
                    <select id="period-select" onchange="changePeriod(this.value)">
                        <option value="1M" <?php echo $period === '1M' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="3M" <?php echo $period === '3M' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="6M" <?php echo $period === '6M' ? 'selected' : ''; ?>>Semi-Annual</option>
                        <option value="1Y" <?php echo $period === '1Y' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="explanation-content">
                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-chart-line"></i> How Your <?php echo $periodLabel; ?> Predictions Work</h2>
                    </div>
                    <div class="section-content">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div class="info-text">
                                <h3>AI-Powered Analysis</h3>
                                <p>Our advanced AI model analyzes your spending patterns from the past 12 months to generate realistic predictions. The model considers seasonal trends, spending habits, and historical data to forecast your future expenses.</p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fa-solid fa-calendar-alt"></i>
                            </div>
                            <div class="info-text">
                                <h3>Seasonal Patterns</h3>
                                <p>The AI identifies months or seasons with consistently higher or lower spending (like holiday seasons, vacation periods, or back-to-school months) and incorporates these patterns into your predictions.</p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fa-solid fa-arrow-trend-up"></i>
                            </div>
                            <div class="info-text">
                                <h3>Trend Analysis</h3>
                                <p>Your overall spending trends (increasing, decreasing, or stable) are analyzed and projected into future periods to provide more accurate forecasts.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-chart-pie"></i> Your Spending Analysis</h2>
                    </div>
                    <div class="section-content">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-calculator"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Average Monthly Spending</h4>
                                    <p class="stat-value">RM<?php echo number_format($averageMonthlySpending, 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Highest Month</h4>
                                    <p class="stat-value">RM<?php echo number_format($highestSpending, 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Lowest Month</h4>
                                    <p class="stat-value">RM<?php echo number_format($lowestSpending, 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Total Transactions</h4>
                                    <p class="stat-value"><?php echo array_sum(array_column($actualSpending, 'transaction_count')); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($predictionData && isset($predictionData['predicted_expense'])): ?>
                        <div class="current-prediction">
                            <div class="prediction-highlight">
                                <h3><i class="fas fa-crystal-ball"></i> Your Next <?php echo ucfirst(strtolower($period)); ?> Prediction</h3>
                                <p class="prediction-amount">RM <?php echo number_format($predictionData['predicted_expense'], 2); ?></p>
                                <p class="prediction-note">Based on your spending patterns and AI analysis</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($topCategories)): ?>
                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-tags"></i> Your Top Spending Categories</h2>
                    </div>
                    <div class="section-content">
                        <div class="category-list">
                            <?php foreach ($topCategories as $index => $category): ?>
                            <div class="category-item">
                                <div class="category-rank">#<?php echo $index + 1; ?></div>
                                <div class="category-info">
                                    <h4><?php echo htmlspecialchars($category['categoryName']); ?></h4>
                                    <p>RM<?php echo number_format($category['total_spent'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-lightbulb"></i> Why These Predictions?</h2>
                    </div>
                    <div class="section-content">
                        <div class="prediction-reasons">
                            <div class="reason-item">
                                <div class="reason-number">1</div>
                                <div class="reason-content">
                                    <h4>Historical Data Analysis</h4>
                                    <p>Your past spending behavior is the foundation of our predictions. The AI analyzes patterns in your transaction history to understand your spending habits.</p>
                                </div>
                            </div>
                            
                            <div class="reason-item">
                                <div class="reason-number">2</div>
                                <div class="reason-content">
                                    <h4>Seasonal Adjustments</h4>
                                    <p>Different months have different spending patterns. Holiday seasons, back-to-school periods, and vacation months are factored into the predictions.</p>
                                </div>
                            </div>
                            
                            <div class="reason-item">
                                <div class="reason-number">3</div>
                                <div class="reason-content">
                                    <h4>Trend Projection</h4>
                                    <p>If your spending has been increasing or decreasing over time, this trend is projected into future periods to provide more realistic forecasts.</p>
                                </div>
                            </div>
                            
                            <div class="reason-item">
                                <div class="reason-number">4</div>
                                <div class="reason-content">
                                    <h4>Category Weighting</h4>
                                    <p>Your spending in different categories (food, transportation, entertainment, etc.) is analyzed to understand which areas drive your expenses.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-exclamation-triangle"></i> Important Notes</h2>
                    </div>
                    <div class="section-content">
                        <div class="warning-cards">
                            <div class="warning-card">
                                <div class="warning-icon">
                                    <i class="fa-solid fa-info-circle"></i>
                                </div>
                                <div class="warning-content">
                                    <h4>Predictions Are Estimates</h4>
                                    <p>These predictions are based on historical data and AI analysis. They are estimates and may not reflect actual future spending due to unexpected events or changes in behavior.</p>
                                </div>
                            </div>
                            
                            <div class="warning-card">
                                <div class="warning-icon">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <div class="warning-content">
                                    <h4>Updated Monthly</h4>
                                    <p>Predictions are updated on the 25th of each month to ensure they reflect your most recent spending patterns and provide the most accurate forecasts.</p>
                                </div>
                            </div>
                            
                            <div class="warning-card">
                                <div class="warning-icon">
                                    <i class="fa-solid fa-chart-bar"></i>
                                </div>
                                <div class="warning-content">
                                    <h4>Use as Guidance</h4>
                                    <p>Use these predictions as a guide for your financial planning. They can help you set realistic budgets and identify potential areas for spending optimization.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="explanation-section">
                    <div class="section-header">
                        <h2><i class="fa-regular fa-lightbulb"></i> Tips for Better Predictions</h2>
                    </div>
                    <div class="section-content">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="info-text">
                                <h3>Add More Transactions</h3>
                                <p>Regularly add your expenses to improve prediction accuracy. More data leads to better forecasts.</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="info-text">
                                <h3>Categorize Properly</h3>
                                <p>Use appropriate categories for your transactions to help the AI understand your spending patterns better.</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-text">
                                <h3>Be Consistent</h3>
                                <p>Add transactions regularly and consistently to maintain accurate predictions over time.</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="info-text">
                                <h3>Monitor Regularly</h3>
                                <p>Check your predictions regularly and compare them with actual spending to understand your financial patterns.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <script>
        function changePeriod(period) {
            window.location.href = 'predictionExplanation.php?period=' + period;
        }
    </script>
</body>
</html> 