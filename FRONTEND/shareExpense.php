<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
include '../FUNCTION/mainFunc.inc.php';

$user_id = $_SESSION['id'];
$connect = OpenCon();

// Updated query: show each shared expense only once, prioritizing 'recipient' role if user is both
$query = "
    SELECT se.shareExpense_id, se.name, se.amount, se.description, se.category_id, se.date_created, se.status,
        CASE
            WHEN ser.recipient_email IS NOT NULL THEN 'recipient'
            ELSE 'creator'
        END as role
    FROM shareexpenses se
    LEFT JOIN shared_expense_recipients ser
        ON se.shareExpense_id = ser.shared_expense_id
        AND ser.recipient_email = ? AND ser.status = 'accepted'
    WHERE se.user_id = ? OR ser.recipient_email = ?
    GROUP BY se.shareExpense_id
    ORDER BY se.date_created DESC
";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "sis", $email, $user_id, $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <title>Cash Compass</title>
        <link rel="stylesheet" href="../CSS/set.css">
        <link rel="stylesheet" href="../CSS/sideNav.css">
        <link rel="stylesheet" href="../CSS/shareExpense.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body>
        <?php include '../FUNCTION/sideNav.inc.php'; ?>
        <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>SHARE EXPENSE</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />
            <section id="share-expense">
                <div id="add-share-expense-container">
                    <button onclick="window.location.href='addShareExpense.php'">ADD SHARE EXPENSE</button>
                </div>
                <section id="shared-expense-container">
                    <?php
                    if (mysqli_num_rows($result) === 0) {
                        echo '<div class="shared-info"><p>No shared expenses found.</p></div>';
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $shared_expense_id = $row['shareExpense_id'];
                            $name = htmlspecialchars($row['name']);
                            $amount = number_format($row['amount'], 2);
                            // Calculate total used by all recipients
                            $total_used_query = "SELECT COALESCE(SUM(amount), 0) as used FROM transactions WHERE shared_expense_id = ? AND transactionType = 'expense'";
                            $total_used_stmt = mysqli_prepare($connect, $total_used_query);
                            mysqli_stmt_bind_param($total_used_stmt, "i", $shared_expense_id);
                            mysqli_stmt_execute($total_used_stmt);
                            $total_used_result = mysqli_stmt_get_result($total_used_stmt);
                            $total_used = mysqli_fetch_assoc($total_used_result)['used'];
                            $percent_used_total = $row['amount'] > 0 ? min(100, ($total_used / $row['amount']) * 100) : 0;
                            $percent_remaining_total = 100 - $percent_used_total;
                            echo '<div class="shared-info">';
                            echo '  <div class="shared-expense-header">';
                            echo '    <h3>' . $name . '</h3>';
                            echo '  </div>';
                            echo '  <div class="shared-expense-content">';
                            // Show total progress bar for the whole shared expense
                            echo '<div class="progress-bar-container">';
                            if ($percent_used_total < 30) {
                                echo '  <div class="progress-bar-filled" style="width: ' . $percent_used_total . '%;"></div>';
                                echo '  <div class="progress-bar-remaining" style="width: ' . $percent_remaining_total . '%; display: flex; align-items: center; justify-content: flex-start; padding-left: 0.7em; font-size: 0.7em; color: #5c4442;">';
                                echo '    <span class="progress-bar-text">RM ' . number_format($total_used,2) . ' used</span>';
                                echo '  </div>';
                            } else {
                                echo '  <div class="progress-bar-filled" style="width: ' . $percent_used_total . '%; text-align: right; padding-right: 0.7em; font-size: 0.7em; color: #5c4442; align-items: center; display: flex; justify-content: flex-end;">';
                                echo '    <span class="progress-bar-text">RM ' . number_format($total_used,2) . ' used</span>';
                                echo '  </div>';
                                echo '  <div class="progress-bar-remaining" style="width: ' . $percent_remaining_total . '%"></div>';
                            }
                            echo '  <span class="shared-expense-amount">RM ' . number_format($row['amount'],2) . ' total</span>';
                            echo '</div>';
                            echo '    <button class="view-details-btn" onclick="window.location.href=\'viewSharedExpenseDetails.php?id=' . $shared_expense_id . '\'">VIEW DETAILS</button>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </section>
            </section>
        </section>
    </body>
</html>