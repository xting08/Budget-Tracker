<?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }

    // Assuming you have a database connection file
    include_once '../DB/db_connect.php';
    $connect = openCon();

    // Fetch transactions from the database
    $where_clause = "WHERE user_id = " . intval($_SESSION['id']);
    
    // Filter by transaction type
    if (isset($_POST['transaction-history-filter']) && $_POST['transaction-history-filter'] !== 'all') {
        $type = mysqli_real_escape_string($connect, $_POST['transaction-history-filter']);
        if ($type === 'shared') {
            $where_clause .= " AND shared_expense_id IS NOT NULL";
        } else {
            $where_clause .= " AND transactionType = '" . $type . "'";
        }
    }
    
    // Filter by date range - default to current month if no filter is specified
    if (isset($_POST['transaction-history-date']) && $_POST['transaction-history-date'] !== 'all') {
        $date_range = mysqli_real_escape_string($connect, $_POST['transaction-history-date']);
        switch ($date_range) {
            case 'today':
                $where_clause .= " AND DATE(date) = CURDATE()";
                break;
            case 'this-week':
                $where_clause .= " AND YEARWEEK(date) = YEARWEEK(CURDATE())";
                break;
            case 'this-month':
                $where_clause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
                break;
            case 'last-3-month':
                $where_clause .= " AND date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                break;
            case 'this-year':
                $where_clause .= " AND YEAR(date) = YEAR(CURDATE())";
                break;
        }
    } else {
        // Default to current month if no date filter is specified
        $where_clause .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    }
    
    $sql = "SELECT transactionID, transactionName, amount, date, transactionType, user_id, shared_expense_id, saving_id, budget_id 
            FROM transactions $where_clause 
            ORDER BY date DESC";
    $result = mysqli_query($connect, $sql);

    if (!$result) {
        echo '<div style="color: #5f2824; text-align: center; padding: 1em;">Error loading transactions. Please try again.</div>';
        exit();
    }

    if (mysqli_num_rows($result) > 0) {
        echo '<table id="transaction-history-table">';
        echo '<thead>
                <tr>
                    <th colspan="2">Transaction Name</th>
                    <th>Date</th>
                    <th colspan="2" style="text-align: left;">Amount (RM)</th>
                    <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody>';
        while ($row = mysqli_fetch_assoc($result)) {
            $transactionName = $row['transactionName'];
            $transactionID = $row['transactionID'];
            $amount = number_format($row['amount'], 2);
            $date = htmlspecialchars($row['date']);

            // Color for amount: red for expense, green for income
            $amountColor = ($row['transactionType'] === 'expense') ? '#5f2824' : '#294c4b';
            $amountType = ($row['transactionType'] === 'expense') ? '-' : '+';
            
            // Add shared expense indicator
            $sharedIndicator = $row['shared_expense_id'] ? ' <i class="fa-solid fa-users" title="Shared Expense" style="color:rgb(117, 59, 130); font-size: 0.7em;"></i>' : '';
            $savingIndicator = $row['saving_id'] ? ' <i class="fa-solid fa-vault" title="Saving Goal" style="color: #319832; font-size: 0.7em;"></i>' : '';
            $budgetIndicator = $row['budget_id'] ? ' <i class="fa-solid fa-money-bill" title="Budget" style="color:rgb(160, 80, 74); font-size: 0.7em;"></i>' : '';

            echo "<tr>
                    <td id='table-transaction-name'>" . (strlen($transactionName) > 25 ? substr($transactionName, 0, 25) . "..." : $transactionName) . "</td>
                    <td id='table-icon'>" . $sharedIndicator . $savingIndicator . $budgetIndicator . "</td>
                    <td id='table-date'>$date</td>
                    <td id='table-amountType' style='color:$amountColor;'>$amountType</td>
                    <td id='table-amount' style='color:$amountColor;'>$amount</td>
                    <td><button id='table-view-btn' onclick='window.location.href=\"viewTransactionDetails.php?transactionID=$transactionID\"'>View</button></td>
                  </tr>";
        }
        echo '</tbody>
              </table>';
    } else {
        echo '<table id="transaction-history-table">
              <thead>
                <tr>
                    <th>Transaction Name</th>
                    <th>Date</th>
                    <th>Amount (RM)</th>
                    <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                    <td colspan="4" style="text-align:center;">No transactions found.</td>
                </tr>
              </tbody>
              </table>';
    }
?>
