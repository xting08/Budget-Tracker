<?php
    require_once '../DB/db_connect.php';
    $connect = OpenCon();

    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = $_SESSION['id'];
    $transactionID = $_GET['transactionID'];

    $transactionDetailsQuery = "SELECT * FROM transactions WHERE transactionID = $transactionID";
    $transactionDetailsResult = mysqli_query($connect, $transactionDetailsQuery);
    $transactionDetailsRow = mysqli_fetch_assoc($transactionDetailsResult);
    $transactionReceipt = $transactionDetailsRow['receipt_id'];

    if (!empty($transactionReceipt)) {
        $receiptQuery = "SELECT * FROM transaction_receipt WHERE receipt_id = $transactionReceipt";
        $receiptResult = mysqli_query($connect, $receiptQuery);
        $receiptRow = mysqli_fetch_assoc($receiptResult);
        $receiptImage = $receiptRow['receipt_image'];
    }
    
    // Transaction Details
    $transactionType = $transactionDetailsRow['transactionType'];
    $transactionAmount = $transactionDetailsRow['amount'];
    $transactionName = $transactionDetailsRow['transactionName'];
    $transactionDate = $transactionDetailsRow['date'];
    $transactionCategory = $transactionDetailsRow['category_id'];
    $transactionDescription = $transactionDetailsRow['description'];
    $receiptURL = !empty($receiptImage) ? 'data:image/jpg;base64,' . base64_encode($receiptImage) : '';

    $categoryQuery = "SELECT * FROM categories WHERE category_id = $transactionCategory";
    $categoryResult = mysqli_query($connect, $categoryQuery);
    $categoryRow = mysqli_fetch_assoc($categoryResult);

    $allCategoriesSql = "SELECT * FROM categories WHERE (user_id = '$user_id' OR user_id = 0) AND transactionType = '$transactionType'";
    $allCategoriesResult = mysqli_query($connect, $allCategoriesSql);
    $allCategories = array();


    

    while ($row = mysqli_fetch_assoc($allCategoriesResult)) {
        $allCategories[] = $row;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if(isset($_POST['update-transaction'])) {
            // Update Transaction
            $transactionType = $_POST['transaction-type'];
            $transactionAmount = $_POST['transaction-amount'];
            $transactionName = $_POST['transaction-name'];
            $transactionDate = $_POST['transaction-date'];
            $transactionCategory = $_POST['transaction-category'];
            $transactionDescription = $_POST['transaction-description'];
            $shared_expense_id = !empty($_POST['shared_expense_id']) ? intval($_POST['shared_expense_id']) : null;

            // Start transaction
            mysqli_begin_transaction($connect);

            try {
                // Update Transaction
                $update = "UPDATE transactions SET 
                    transactionType = ?, 
                    amount = ?, 
                    transactionName = ?, 
                    date = ?, 
                    category_id = ?, 
                    description = ?,
                    shared_expense_id = ?
                    WHERE transactionID = ?";
                
                $stmt = mysqli_prepare($connect, $update);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($connect));
                }

                mysqli_stmt_bind_param($stmt, "sdssisii", 
                    $transactionType,
                    $transactionAmount,
                    $transactionName,
                    $transactionDate,
                    $transactionCategory,
                    $transactionDescription,
                    $shared_expense_id,
                    $transactionID
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
                }

                // Fetch the latest transaction details after update
                $transactionDetailsQuery = "SELECT * FROM transactions WHERE transactionID = $transactionID";
                $transactionDetailsResult = mysqli_query($connect, $transactionDetailsQuery);
                $transactionDetailsRow = mysqli_fetch_assoc($transactionDetailsResult);
                $transactionDescription = $transactionDetailsRow['description'];
                $transactionAmount = $transactionDetailsRow['amount'];
                $transactionName = $transactionDetailsRow['transactionName'];
                $transactionDate = $transactionDetailsRow['date'];
                $transactionCategory = $transactionDetailsRow['category_id'];

                mysqli_commit($connect);

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Transaction Updated!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/viewTransactionDetails.php?transactionID=$transactionID'
                            }
                        });
                    });
                </script>";
            } catch (Exception $e) {
                mysqli_rollback($connect);
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Transaction Failed to Update: " . addslashes($e->getMessage()) . "',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Transaction Input',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
            }
        } else if (isset($_POST['delete-transaction'])) {
            $delete = "DELETE FROM transactions WHERE transactionID = '$transactionID'";
            $transactionDeleteResult = mysqli_query($connect, $delete);
            $receiptDelete = "DELETE FROM transaction_receipt WHERE receipt_id = '$transactionReceipt'";
            $receiptDeleteResult = mysqli_query($connect, $receiptDelete);

            if($transactionDeleteResult && $receiptDeleteResult) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Your Transaction has been deleted.',
                            icon: 'success',
                            confirmButtonColor: '#294c4b',
                            confirmButtonText: 'Return to Transaction',
                        }).then(function() {
                            window.location.href = '../FRONTEND/transaction.php';
                        });
                    });
                </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Transaction Failed to Delete!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Back',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
            }
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Transaction Failed to Update!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Back',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
        }
    }

    function getExpenseCategory($categoryRow, $allCategories) {
        $type = strtolower($categoryRow['transactionType']);
        echo "<option value='" . htmlspecialchars($categoryRow['category_id']) . "' transaction-type='" . htmlspecialchars($type) . "'>" . htmlspecialchars($categoryRow['categoryName']) . "</option>";
        
        foreach ($allCategories as $row) {
            // Skip the current category row to avoid duplicate
            if ($row['category_id'] == $categoryRow['category_id']) {
                continue;
            }
            $type = strtolower($row['transactionType']);
            echo "<option value='" . htmlspecialchars($row['category_id']) . "' transaction-type='" . htmlspecialchars($type) . "'>" . htmlspecialchars($row['categoryName']) . "</option>";
        }
    }
?>