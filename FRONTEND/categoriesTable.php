<?php
include '../FUNCTION/mainFunc.inc.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$month_str = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$userId = intval($_SESSION['id']);

// Sanitize and validate date input
$date_parts = explode('-', $month_str);
$year = isset($date_parts[0]) ? intval($date_parts[0]) : date('Y');
$month = isset($date_parts[1]) ? intval($date_parts[1]) : date('m');

// Query to get categories with monthly totals and all-time transaction counts
$query = "
    SELECT 
        c.*, 
        COUNT(t_month.transactionID) as transaction_count,
        COALESCE(SUM(CASE WHEN t_month.transactionType = 'income' THEN t_month.amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN t_month.transactionType = 'expense' THEN t_month.amount ELSE 0 END), 0) as total_expense
    FROM categories c
    LEFT JOIN transactions t_month ON c.category_id = t_month.category_id 
        AND t_month.user_id = $userId 
        AND YEAR(t_month.date) = $year 
        AND MONTH(t_month.date) = $month
    WHERE (c.user_id = 0 OR c.user_id = $userId)
    " . ($type !== 'all' ? "AND c.transactionType = '$type'" : "") . "
    GROUP BY c.category_id
    ORDER BY c.categoryName ASC";

$result = mysqli_query($connect, $query);
?>

<div id="transaction-history-item">
    <table id="transaction-history-table">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Type</th>
                <th>Transactions</th>
                <th colspan="2">Total (RM)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $totalAmount = $row['transactionType'] === 'income' ? $row['total_income'] : $row['total_expense'];
                    $amountColor = $row['transactionType'] === 'income' ? '#294c4b' : '#5f2824';
                    $amountPrefix = $row['transactionType'] === 'income' ? '+' : '-';
                    ?>
                    <tr>
                        <td id="table-transaction-name">
                            <?php echo htmlspecialchars($row['categoryName']); ?>
                            <?php if ($row['user_id'] === 0) { ?>
                                <i class="fa-solid fa-lock" style="color: #6C8396;"></i>
                            <?php } ?>
                        </td>
                        <td id="table-date" style="color: <?php echo $amountColor; ?>;">
                            <?php echo ucfirst($row['transactionType']); ?>
                        </td>
                        <td id="table-amount">
                            <?php echo $row['transaction_count']; ?>
                        </td>
                        <td id="table-amount" style="color: <?php echo $amountColor; ?>; text-align: right; margin:0; padding:0;">
                            <?php echo $amountPrefix; ?>
                        </td>
                        <td id="table-amount" style="color: <?php echo $amountColor; ?>; text-align: left;">
                            <?php echo number_format($totalAmount, 2); ?>
                        </td>
                        <td>
                            <?php if ($row['user_id'] != 0): ?>
                                <button id="table-delete-btn" onclick="deleteCategory(<?php echo $row['category_id']; ?>)" title="Delete Category"><i class="fa-solid fa-trash"></i></button>
                            <?php else: ?>
                                <button id="table-delete-disabled" style="cursor:not-allowed;" title="Default Category cannot be deleted"><i class="fa-solid fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No categories found.</td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>
