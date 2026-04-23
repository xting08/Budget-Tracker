<?php
require_once '../DB/db_connect.php';
session_start();

if (!isset($_GET['id'])) {
    echo "Missing budget ID.";
    exit();
}

$budget_id = intval($_GET['id']);
$user_id = $_SESSION['id'];
$connect = OpenCon();

// Get main budget info
$query = "SELECT * FROM budgets WHERE budget_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ii", $budget_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$budget = mysqli_fetch_assoc($result);

if (!$budget) {
    echo "Budget not found.";
    exit();
}

// Calculate usage
$limit = $budget['amountLimit'];
$used = $budget['currentSpend'];
$remaining = $limit - $used;
$percent_used = $limit > 0 ? min(100, ($used / $limit) * 100) : 0;
$percent_remaining = 100 - $percent_used;

// Get all transactions for this budget
$trans_query = "SELECT * FROM transactions WHERE budget_id = ? AND user_id = ? ORDER BY date DESC";
$stmt = mysqli_prepare($connect, $trans_query);
mysqli_stmt_bind_param($stmt, "ii", $budget_id, $user_id);
mysqli_stmt_execute($stmt);
$trans_result = mysqli_stmt_get_result($stmt);
$transactions = [];
while ($row = mysqli_fetch_assoc($trans_result)) {
    $transactions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../IMG/favicon.png">
    <title>Cash Compass</title>
    <link rel="stylesheet" href="../CSS/addSharedExpense.css">
    <link rel="stylesheet" href="../CSS/scrollBar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .details-card { background:rgb(254, 251, 232); border-radius: 0.7em; border: 2px solid #8c716f; padding: 2em 2.5em; margin: 2em auto; width: 30%; }
        .details-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.7em; }
        .details-label { font-weight: 600; color: #232640; min-width: 140px; }
        .details-card > hr { border: none; border-top: 1px solid #d2cbcb; margin: 1.5em 0; }
        #budget-details-container { display: flex; flex-direction: row; align-items: flex-start; justify-content: space-between; gap: 3em; width: 100%; }
        .transactions-card { margin-top: 1em; width: 70%; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; background: #E8E3CD; border-radius: 0.5em; overflow: hidden; }
        th, td { padding: 0.8em 1.2em; text-align: center; }
        th { background: #4e606f; color: #fffbea; font-weight: 600; font-size: 1em; }
        td { border-bottom: 1px dashed #8c716f; color: #232640; font-size: 1em; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f1ecd7; }
    </style>
</head>
<body>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>BUDGET DETAILS</a>
            <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                <button id="logout-btn" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </span>
        <hr />    
    </section>
    <nav id="button-nav">
        <button id="back-btn" onclick="window.location.href='../FRONTEND/budget.php'">
            <i class="fa-solid fa-circle-left"></i> &nbsp;Back
        </button>
    </nav>
    <section id="budget-details-container">
        <div class="details-card">
            <h2>Budget Details</h2>
            <hr>
            <div class="details-row"><span class="details-label">Name:</span><span><?= htmlspecialchars($budget['budgetName']) ?></span></div>
            <div class="details-row"><span class="details-label">Limit:</span><span>RM<?= number_format($limit,2) ?></span></div>
            <div class="details-row"><span class="details-label">Description:</span><span><?= htmlspecialchars($budget['description']) ?></span></div>
            <div class="details-row"><span class="details-label">Start Date:</span><span><?= htmlspecialchars($budget['startDate']) ?></span></div>
            <div class="details-row"><span class="details-label">End Date:</span><span><?= htmlspecialchars($budget['endDate']) ?></span></div>
            <hr>
            <h3>Usage</h3>
            <div class="progress-bar-container" style="margin-bottom:1em;">
                <?php if ($percent_used < 30): ?>
                    <div class="progress-bar-filled" style="width: <?= $percent_used ?>%"></div>
                    <div class="progress-bar-remaining" style="width: <?= $percent_remaining ?>%; display: flex; align-items: center; justify-content: flex-start; padding-left: 0.7em; font-size: 0.7em; color: #5c4442;">
                        <span class="progress-bar-text">RM <?= number_format($used,2) ?> used</span>
                    </div>
                <?php else: ?>
                    <div class="progress-bar-filled" style="width: <?= $percent_used ?>%; text-align: right; padding-right: 0.7em; font-size: 0.7em; color: #5c4442; align-items: center; display: flex; justify-content: flex-end;">
                        <span class="progress-bar-text">RM <?= number_format($used,2) ?> used</span>
                    </div>
                    <div class="progress-bar-remaining" style="width: <?= $percent_remaining ?>%"></div>
                <?php endif; ?>
                <span class="shared-expense-amount">RM <?= number_format($limit,2) ?> limit</span>
            </div>
            <div class="details-row"><span class="details-label">Amount Remaining:</span><span>RM<?= number_format($remaining,2) ?></span></div>
            <form method="post" action="../FUNCTION/deleteBudgetPost.php" id="delete-budget-form">
                <input type="hidden" name="budget_id" value="<?= $budget_id ?>">
                <button type="button" id="delete-budget-btn" style="font-weight: 600; background:#5f2824;color:#f7f2dd;padding:1em 2em;border:none;border-radius:8px;cursor:pointer; width: 100%; margin-top: 1em;">
                    Delete Budget
                </button>
            </form>
        </div>
        <div class="transactions-card">
            <h3>Transactions in this Budget</h3>
            <table border="1">
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
                <?php foreach ($transactions as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['transactionName']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td>RM<?= number_format($row['amount'],2) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="4">No transactions found for this budget.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.getElementById('delete-budget-btn').addEventListener('click', function(e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to delete this budget?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5f2824',
            cancelButtonColor: '#8c716f',
            confirmButtonText: 'Yes, continue',
            cancelButtonText: 'Cancel'
        }).then((firstResult) => {
            if (firstResult.isConfirmed) {
                Swal.fire({
                    title: 'This action is permanent!',
                    text: "Are you absolutely sure you want to delete? This cannot be undone.",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#5f2824',
                    cancelButtonColor: '#8c716f',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((secondResult) => {
                    if (secondResult.isConfirmed) {
                        document.getElementById('delete-budget-form').submit();
                    }
                });
            }
        });
    });
    </script>
</body>
</html> 