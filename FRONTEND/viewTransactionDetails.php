<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

include "../FUNCTION/updateTransactionPost.inc.php";

    // Get the transaction ID from the URL or context
    $transactionID = isset($_GET['transactionID']) ? intval($_GET['transactionID']) : 0;

    // Fetch items for this transaction
    $itemList = [];
    if ($transactionID > 0) {
        require_once '../DB/db_connect.php';
        $connect = OpenCon();
        $sql = "SELECT itemName, itemPrice FROM item_list WHERE transactionID = $transactionID";
        $result = mysqli_query($connect, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $itemList[] = [
                    'itemName' => $row['itemName'],
                    'itemPrice' => $row['itemPrice']
                ];
            }
        }

        // Fetch shared expenses for dropdown (show each only once, prioritize recipient entry)
        $shared_expenses = [];
        $user_id = $_SESSION['id'];
        // 1. Owner
        $query1 = "SELECT se.shareExpense_id, se.name, se.amount, COALESCE(ser.percentage, 100) as percentage
                   FROM shareexpenses se
                   LEFT JOIN shared_expense_recipients ser
                     ON se.shareExpense_id = ser.shared_expense_id
                     AND ser.recipient_email = ? AND ser.status = 'accepted'
                   WHERE se.user_id = ?
                   GROUP BY se.shareExpense_id";
        $stmt1 = mysqli_prepare($connect, $query1);
        mysqli_stmt_bind_param($stmt1, "si", $email, $user_id);
        mysqli_stmt_execute($stmt1);
        $result1 = mysqli_stmt_get_result($stmt1);
        while ($row = mysqli_fetch_assoc($result1)) {
            $shared_expenses[$row['shareExpense_id']] = $row;
        }
        // 2. Recipient
        $query2 = "SELECT se.shareExpense_id, se.name, se.amount, ser.percentage
                   FROM shareexpenses se
                   JOIN shared_expense_recipients ser
                     ON se.shareExpense_id = ser.shared_expense_id
                   WHERE ser.recipient_email = ? AND ser.status = 'accepted'
                   GROUP BY se.shareExpense_id";
        $stmt2 = mysqli_prepare($connect, $query2);
        mysqli_stmt_bind_param($stmt2, "s", $email);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        while ($row = mysqli_fetch_assoc($result2)) {
            $shared_expenses[$row['shareExpense_id']] = $row; // Overwrite duplicates with recipient info
        }
        // Convert to indexed array for foreach
        $shared_expenses = array_values($shared_expenses);

        // Get current shared expense if any
        $current_shared_expense = null;
        $sql = "SELECT shared_expense_id FROM transactions WHERE transactionID = $transactionID";
        $result = mysqli_query($connect, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $current_shared_expense = $row['shared_expense_id'];
        }

        // Fetch transaction details including budget and saving goal info
        $transaction = null;
        $sql = "SELECT t.*, b.budgetName, s.savingName
                FROM transactions t
                LEFT JOIN budgets b ON t.budget_id = b.budget_id
                LEFT JOIN savinggoals s ON t.saving_id = s.saving_id
                WHERE t.transactionID = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "i", $transactionID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaction = mysqli_fetch_assoc($result);
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <title>Cash Compass</title>
        <link rel="stylesheet" href="../CSS/addTransaction.css">
        <link rel="stylesheet" href="../CSS/scrollBar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="../JS/viewReceipt.js"></script>
        <script src="../JS/sweetAlert.js"></script>
    </head>

    <body>
        <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>TRANSACTION DETAILS</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />    
        </section>
        <nav id="button-nav">
            <button id="back-btn" onclick="window.location.href='../FRONTEND/transaction.php'">
                <i class="fa-solid fa-circle-left"></i> &nbsp;Back
            </button>
        </nav>
        <section id="add-transaction-container">
            <form method="post" id="add-transaction-form">
                <div class="form-group">
                    <label for="transaction-type">Transaction Type</label>
                    <input type="text" name="transaction-type" id="transaction-type" class="form-item" value="<?php echo ucfirst($transaction['transactionType']) ?>" readonly>
                </div>
                <?php if (!empty($transaction['budget_id'])): ?>
                <div class="form-group">
                    <label>Budget:</label>
                    <input type="text" class="form-item-focus" value="<?php echo htmlspecialchars($transaction['budgetName']); ?>" readonly>
                </div>
                <?php endif; ?>
                <?php if (!empty($transaction['saving_id'])): ?>
                <div class="form-group">
                    <label>Saving Goal:</label>
                    <input type="text" class="form-item-focus" value="<?php echo htmlspecialchars($transaction['savingName']); ?>" readonly>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="transaction-amount">Transaction Amount</label>
                    <input type="number" step="0.01" min="0" name="transaction-amount" id="transaction-amount" class="form-item" placeholder="0.00"
                    value="<?php echo $transaction['amount'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-name">Transaction Name</label>
                    <input type="text" name="transaction-name" id="transaction-name" class="form-item" placeholder="Enter Transaction Name"
                    value="<?php echo $transaction['transactionName']?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-date">Transaction Date</label>
                    <input type="date" name="transaction-date" id="transaction-date" class="form-item"
                    value="<?php echo $transaction['date'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="transaction-category">Transaction Category</label>
                    <select name="transaction-category" id="transaction-category" class="form-item" required>
                        <?php getExpenseCategory($categoryRow, $allCategories); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transaction-description" required>Transaction Description</label>
                    <input type="text" name="transaction-description" id="transaction-description" class="form-item" placeholder="Enter Transaction Description"
                    value="<?php echo $transaction['description'] ?>">
                </div>
                <!-- Shared Expense Dropdown (now readonly input) -->
                <div class="form-group" id="shared-expense-container" style="display: flex;">
                    <?php
                    $selected_se = null;
                    foreach ($shared_expenses as $se) {
                        if ($current_shared_expense == $se['shareExpense_id']) {
                            $selected_se = $se;
                            break;
                        }
                    }
                    if ($selected_se) {
                        $shared_expense_text = htmlspecialchars($selected_se['name']) . ' (Share: RM' . number_format($selected_se['amount'] * $selected_se['percentage'] / 100, 2) . ')';
                        echo '<label for="shared_expense_id">Related Shared Expense</label>';
                        echo '<input type="text" class="form-item-focus" style="background-color: #f7f2dd; border: 2px solid #bdcdd9; color:rgb(145, 156, 165); font-style: italic;" value="' . $shared_expense_text . '" readonly>';
                    }
                    // If no shared expense is selected, do not show the label or input field
                    ?>
                </div>
                <div class="form-group">
                    <button type="submit" name="update-transaction" class="form-item" id="add-transaction-btn">Update Transaction</button>
                </div>
            </form>
            <div id="add-transaction-image">
                <form method="post" id="delete-transaction-form">
                    <input type="hidden" name="delete-transaction" value="1">
                    <input type="submit" value="Delete Transaction" id="delete-transaction-btn">
                </form>
                <?php if (!empty($receiptURL)): ?>
                <div id="receipt-uploaded">
                    <div id="receipt-image-container">
                        <img id="receipt-image-details" src="<?php echo $receiptURL ?>" alt="Receipt Image">
                        <p id="receipt-image-text"><i class='fa-solid fa-triangle-exclamation'></i>&nbsp; Automatically extracted transaction details might be inaccurate. <br/>Please verify the details before added the transaction.</p>
                    </div>
                </div>
                <div id="receipt-image-modal-details" style="display: none;">
                    <span id="receipt-image-modal-close-details">&times;</span> 
                    <img id="receipt-image-modal-image-details">
                </div>
                <?php endif; ?>
            </div>
        </section>
        <section id="receipt-item-container">
            <div id="receipt-item-header">
                <h3>Receipt Items</h3>
            </div>
            <div id="receipt-item-list">
                <table id="receipt-item-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Item Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($itemList)): ?>
                            <?php foreach ($itemList as $item): ?>
                                <tr>
                                    <td><?php echo $item['itemName']; ?></td>
                                    <td><?php echo number_format($item['itemPrice'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          var form = document.getElementById('delete-transaction-form');
          if (form) {
            form.addEventListener('submit', function(e) {
              e.preventDefault();

              Swal.fire({
                title: "Are you absolutely sure?",
                text: "This action cannot be undone. Do you really want to delete this transaction?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, I'm sure"
              }).then((firstResult) => {
                if (firstResult.isConfirmed) {
                  Swal.fire({
                    title: "Final Confirmation",
                    text: "This is your last chance! Delete this transaction?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                  }).then((secondResult) => {
                    if (secondResult.isConfirmed) {
                      form.submit();
                    }
                  });
                }
              });
            });
          }

          const transactionType = document.getElementById('transaction-type');
          const sharedExpenseContainer = document.getElementById('shared-expense-container');

          function updateSharedExpenseVisibility() {
            if (transactionType.value.toLowerCase() === 'expense') {
              sharedExpenseContainer.style.display = 'flex';
            } else {
              sharedExpenseContainer.style.display = 'none';
            }
          }

          // Initial check
          updateSharedExpenseVisibility();

          // Listen for changes
          transactionType.addEventListener('change', updateSharedExpenseVisibility);
        });
        </script>
    </body>
</html>