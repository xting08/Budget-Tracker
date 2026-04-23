<?php
    include '../FUNCTION/mainFunc.inc.php';

    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
    $connect = OpenCon();
    $userId = $_SESSION['id'];

    $receiptSql = "SELECT MAX(receipt_id) FROM transaction_receipt WHERE user_id = '$userId'";
    $receiptResult = mysqli_query($connect, $receiptSql);
    $receiptRow = mysqli_fetch_assoc($receiptResult);

    $transactionType = $_POST['transaction-type'];
    $transactionAmount = $_POST['transaction-amount'];
    $transactionName = $_POST['transaction-name'];
    $transactionDate = $_POST['transaction-date'];
    $transactionCategory = $_POST['transaction-category'];
    $transactionDescription = $_POST['transaction-description'];
    $transactionReceipt = ($_POST['transaction-receipt'] == "0") ? null : $receiptRow['MAX(receipt_id)'];
    $shared_expense_id = !empty($_POST['shared_expense_id']) ? intval($_POST['shared_expense_id']) : null;
    
    // Get budget/saving goal if selected
    $budget_saving_id = null;
    if (isset($_POST['budget-saving-checkbox']) && isset($_POST['budget-saving-id']) && !empty($_POST['budget-saving-id'])) {
        $budget_saving_id = intval($_POST['budget-saving-id']);
    }
    
    // Get the items from the form if they exist
    $items = isset($_POST['items']) ? $_POST['items'] : array();
    
    // Debugging: log input values
    error_log("transactionName: " . var_export($transactionName, true));
    error_log("transactionDate: " . var_export($transactionDate, true));
    error_log("transactionDescription: " . var_export($transactionDescription, true));

    function validateTransactionName($transactionName) {
        if (strlen($transactionName) > 0 && strlen($transactionName) < 51) {
            return true;
        } else {
            return false;
        }
    }

    function validateTransactionDate($transactionDate) {
        try {
            $inputDate = new DateTime($transactionDate);
            $currentDate = new DateTime();
            $inputDate->setTime(0, 0, 0);
            $currentDate->setTime(0, 0, 0);
            return $inputDate <= $currentDate;
        } catch (Exception $e) {
            error_log('Date validation error: ' . $e->getMessage());
            return false;
        }
    }

    function validateTransactionDescription($transactionDescription) {
        if (strlen($transactionDescription) < 101) {
            return true;
        } else {
            return false;
        }
    }

    function addItemQuery($connect, $transactionID, $itemName, $itemPrice) {
        $itemName = mysqli_real_escape_string($connect, $itemName);
        $itemPrice = mysqli_real_escape_string($connect, $itemPrice);
        $itemSql = "INSERT INTO item_list (transactionID, itemName, itemPrice) 
                    VALUES ('$transactionID', '$itemName', '$itemPrice')";
        return mysqli_query($connect, $itemSql);
    }

    function addTransactionQuery($connect, $transactionType, $transactionAmount, $transactionName, $transactionDescription, $transactionCategory, $transactionDate, $userId, $transactionReceipt, $shared_expense_id, $budget_saving_id) {
        // Determine if this is a budget or saving goal transaction
        $budget_id = ($transactionType === 'expense' && $budget_saving_id !== null) ? $budget_saving_id : null;
        $saving_id = ($transactionType === 'income' && $budget_saving_id !== null) ? $budget_saving_id : null;

        if ($transactionReceipt == null) {
            $query = "INSERT INTO transactions (transactionType, amount, transactionName, description, category_id, date, user_id, shared_expense_id, budget_id, saving_id) 
                     VALUES ('$transactionType', '$transactionAmount', '$transactionName', '$transactionDescription', '$transactionCategory', '$transactionDate', '$userId', " . 
                     ($shared_expense_id !== null ? "'$shared_expense_id'" : "NULL") . ", " . 
                     ($budget_id !== null ? "'$budget_id'" : "NULL") . ", " .
                     ($saving_id !== null ? "'$saving_id'" : "NULL") . ")";
            $result = mysqli_query($connect, $query);

            $deleteUnusedReceipt = "DELETE FROM transaction_receipt WHERE transactionID IS NULL";
            $deleteUnusedReceiptResult = mysqli_query($connect, $deleteUnusedReceipt);

            if ($result && $deleteUnusedReceiptResult) {
                // If this is a saving goal transaction, update the current amount
                if ($saving_id !== null) {
                    $updateSaving = "UPDATE savinggoals 
                                   SET currentAmount = (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE saving_id = ? AND user_id = ?)
                                   WHERE saving_id = ? AND user_id = ?";
                    $stmtUpdate = mysqli_prepare($connect, $updateSaving);
                    mysqli_stmt_bind_param($stmtUpdate, "iiii", $saving_id, $userId, $saving_id, $userId);
                    mysqli_stmt_execute($stmtUpdate);
                }

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Transaction Added!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/transaction.php';
                            }
                        });
                    });
                </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Transaction Failed to Add!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Transaction Input',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
            } 

        } else if ($transactionReceipt != null) {
            $query = "INSERT INTO transactions (transactionType, amount, transactionName, description, category_id, date, user_id, receipt_id, shared_expense_id, budget_id, saving_id) 
                     VALUES ('$transactionType', '$transactionAmount', '$transactionName', '$transactionDescription', '$transactionCategory', '$transactionDate', '$userId', '$transactionReceipt', " . 
                     ($shared_expense_id !== null ? "'$shared_expense_id'" : "NULL") . ", " . 
                     ($budget_id !== null ? "'$budget_id'" : "NULL") . ", " .
                     ($saving_id !== null ? "'$saving_id'" : "NULL") . ")";
            $result = mysqli_query($connect, $query);

            $result ? $transactionId = mysqli_insert_id($connect) : $transactionId = null;

            $receiptQuery = "UPDATE transaction_receipt SET transactionID = '$transactionId' WHERE receipt_id = '$transactionReceipt' AND user_id = '$userId'";
            $receiptResult = mysqli_query($connect, $receiptQuery);

            $deleteUnusedReceipt = "DELETE FROM transaction_receipt WHERE transactionID IS NULL";
            $deleteUnusedReceiptResult = mysqli_query($connect, $deleteUnusedReceipt);
    
            if ($result && $receiptResult && $deleteUnusedReceiptResult) {
                // If this is a saving goal transaction, update the current amount
                if ($saving_id !== null) {
                    $updateSaving = "UPDATE savinggoals 
                                   SET currentAmount = (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE saving_id = ? AND user_id = ?)
                                   WHERE saving_id = ? AND user_id = ?";
                    $stmtUpdate = mysqli_prepare($connect, $updateSaving);
                    mysqli_stmt_bind_param($stmtUpdate, "iiii", $saving_id, $userId, $saving_id, $userId);
                    mysqli_stmt_execute($stmtUpdate);
                }

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Transaction Added!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/transaction.php';
                            }
                        });
                    });
                </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Transaction Failed to Add!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Transaction Input',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
            }   
        }

        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (isset($item[0]) && isset($item[1])) {
                    addItemQuery($connect, $transactionId, $item[0], $item[1]);
                }
            }
        }

        // After successful transaction insert, update currentSpend in budgets table
        if ($budget_id && $transactionType === 'expense') {
            $updateBudget = "UPDATE budgets 
                             SET currentSpend = (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE budget_id = ? AND user_id = ?)
                             WHERE budget_id = ? AND user_id = ?";
            $stmtUpdate = mysqli_prepare($connect, $updateBudget);
            mysqli_stmt_bind_param($stmtUpdate, "iiii", $budget_id, $userId, $budget_id, $userId);
            mysqli_stmt_execute($stmtUpdate);
        }
    }

    if (isset($_POST['submit'])) {
        // Backend validation for shared expense remaining balance
        if (!empty($shared_expense_id)) {
            // Get user's share and used amount
            $query = "SELECT se.amount, ser.percentage FROM shareexpenses se
                      LEFT JOIN shared_expense_recipients ser ON se.shareExpense_id = ser.shared_expense_id
                      WHERE se.shareExpense_id = ? AND (ser.recipient_id = ? OR se.user_id = ?)";
            $stmt = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param($stmt, "iii", $shared_expense_id, $userId, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $percentage = $row ? $row['percentage'] : 100;
            $my_share = $row ? $row['amount'] * $percentage / 100 : 0;

            $used_query = "SELECT COALESCE(SUM(amount), 0) as used FROM transactions WHERE user_id = ? AND shared_expense_id = ? AND transactionType = 'expense'";
            $used_stmt = mysqli_prepare($connect, $used_query);
            mysqli_stmt_bind_param($used_stmt, "ii", $userId, $shared_expense_id);
            mysqli_stmt_execute($used_stmt);
            $used_result = mysqli_stmt_get_result($used_stmt);
            $used = mysqli_fetch_assoc($used_result)['used'];
            $remaining = $my_share - $used;

            if ($transactionAmount > $remaining) {
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                </head>
                <body>
                <script>
                    Swal.fire({
                        icon: "error",
                        title: "Insufficient Shared Expense Balance",
                        text: "The amount exceeds your remaining shared expense balance.",
                        confirmButtonColor: "#294c4b"
                    }).then(function() {
                        window.location.href = "../FRONTEND/addTransaction.php";
                    });
                </script>
                </body>
                </html>';
            }
        }

        // Validate budget limit if budget is selected
        if (!empty($budget_id) && $transactionType === 'expense') {
            $query = "SELECT b.amountLimit, COALESCE(SUM(t.amount), 0) as used 
                     FROM budgets b 
                     LEFT JOIN transactions t ON b.budget_id = t.budget_id 
                     WHERE b.budget_id = ? AND b.user_id = ?";
            $stmt = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param($stmt, "ii", $budget_id, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row) {
                $remaining = $row['amountLimit'] - $row['used'];
                if ($transactionAmount > $remaining) {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Budget Limit Exceeded',
                            text: 'The amount exceeds your remaining budget limit.',
                            confirmButtonColor: '#5f2824'
                        }).then(function() {
                            window.location.href = '../FRONTEND/addTransaction.php';
                        });
                    </script>";
                    exit();
                }
            }
        }

        if (!validateTransactionName($transactionName) || !validateTransactionDate($transactionDate) || !validateTransactionDescription($transactionDescription)) {
            // Display validation errors directly to the user (not just in error log)
            $errorMessages = [];
            if (!validateTransactionName($transactionName)) {
                $errorMessages[] = "Invalid Transaction Name.";
            }
            if (!validateTransactionDate($transactionDate)) {
                $errorMessages[] = "Invalid Transaction Date.";
            }
            if (!validateTransactionDescription($transactionDescription)) {
                $errorMessages[] = "Invalid Transaction Description.";
            }
            $errorText = implode(" ", $errorMessages);
            echo "<script>
                    Swal.fire({
                        title: 'Invalid!',
                        text: '" . addslashes($errorText) . "',
                        icon: 'error',
                        confirmButtonColor: '#5f2824',
                        confirmButtonText: 'Check Transaction Input!',
                    }).then(function() {
                        window.location.href = '../FRONTEND/addTransaction.php';
                    });
                </script>";
            exit();
        } else {
            addTransactionQuery($connect, $transactionType, $transactionAmount, $transactionName, $transactionDescription, $transactionCategory, $transactionDate, $userId, $transactionReceipt, $shared_expense_id, $budget_saving_id);
        }
    } else {
        echo "<script>
                Swal.fire({
                    title: 'Invalid!',
                    text: 'Invalid Input!',
                    icon: 'error',
                    confirmButtonColor: '#5f2824',
                    confirmButtonText: 'Check Transaction Input',
                }).then(function() {
                    window.location.href = '../FRONTEND/addTransaction.php';
                });
            </script>";
    }
?>
</body>
</html>
<?php
    // End output buffering and flush
    ob_end_flush();
?>