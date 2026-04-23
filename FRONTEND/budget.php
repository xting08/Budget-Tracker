<?php
    include '../FUNCTION/mainFunc.inc.php';

    $user_id = $_SESSION['id'];
    $connect = OpenCon();

    $query = "SELECT * FROM budgets WHERE user_id = ? ORDER BY startDate DESC";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
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
                <a>BUDGET</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />
            <section id="share-expense">
                <div id="add-share-expense-container">
                    <button onclick="window.location.href='addBudget.php'">ADD BUDGET</button>
                </div>
                <section id="shared-expense-container">
                    <?php
                    if (mysqli_num_rows($result) === 0) {
                        echo '<div class="shared-info"><p>No budgets found.</p></div>';
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $budget_id = $row['budget_id'];
                            $name = htmlspecialchars($row['budgetName']);
                            $limit = $row['amountLimit'];
                            $used = $row['currentSpend'];
                            $startDate = $row['startDate'];
                            $endDate = $row['endDate'];
                            $percent_used = $limit > 0 ? min(100, ($used / $limit) * 100) : 0;
                            $percent_remaining = 100 - $percent_used;

                            // Determine status
                            $currentDate = new DateTime();
                            $endDateObj = new DateTime($endDate);
                            $status = 'Ongoing';
                            $statusColor = '#1e3a5c';
                            if ($used > $limit) {
                                $status = 'Already Exceed!';
                                $statusColor = '#b71c1c';
                            } elseif ($currentDate > $endDateObj) {
                                $status = 'Expired';
                                $statusColor = '#b71c1c';
                            }

                            echo '<div class="shared-info">';
                            echo '  <div class="shared-expense-header">';
                            echo '    <h3>' . $name . '</h3>';
                            echo '    <p style="font-size:1em; text-align:right; padding:0; margin:0;"><span style="color:' . $statusColor . '">' . $status . '</span></p>';
                            echo '  </div>';
                            echo '  <div class="shared-expense-content">';
                            // Show total progress bar for the budget
                            echo '<div class="progress-bar-container">';
                            if ($percent_used < 30) {
                                echo '  <div class="progress-bar-filled" style="width: ' . $percent_used . '%;"></div>';
                                echo '  <div class="progress-bar-remaining" style="width: ' . $percent_remaining . '%; display: flex; align-items: center; justify-content: flex-start; padding-left: 0.7em; font-size: 0.7em; color: #5c4442;">';
                                echo '    <span class="progress-bar-text">RM ' . number_format($used,2) . ' used</span>';
                                echo '  </div>';
                            } else {
                                echo '  <div class="progress-bar-filled" style="width: ' . $percent_used . '%; text-align: right; padding-right: 0.7em; font-size: 0.7em; color: #5c4442; align-items: center; display: flex; justify-content: flex-end;">';
                                echo '    <span class="progress-bar-text">RM ' . number_format($used,2) . ' used</span>';
                                echo '  </div>';
                                echo '  <div class="progress-bar-remaining" style="width: ' . $percent_remaining . '%"></div>';
                            }
                            echo '  <span class="shared-expense-amount">RM ' . number_format($limit,2) . ' limit</span>';
                            echo '</div>';
                            echo '    <button class="view-details-btn" onclick="window.location.href=\'viewBudgetDetails.php?id=' . $budget_id . '\'">VIEW DETAILS</button>';
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