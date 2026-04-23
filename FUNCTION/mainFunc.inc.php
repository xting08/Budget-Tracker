<?php
    require_once '../DB/db_connect.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if(!isset($_SESSION['id'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'warning',
                            title: 'Please Login First!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/login.php';
                            }
                        });
                    });
                </script>";
        exit();
    }

    $connect = OpenCon();
    $username = $_SESSION['username'];
    $userId = $_SESSION['id'];
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($connect, $sql);
    $row = mysqli_fetch_array($result);
    
    $profilePic = $row['profilePic'];

    $amountSql = "SELECT SUM(amount) FROM transactions WHERE user_id = '$userId' AND transactionType = 'income' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $amountResult = mysqli_query($connect, $amountSql);
    $amountRow = mysqli_fetch_array($amountResult);
    $totalIncome = $amountRow['SUM(amount)'];

    $expenseSql = "SELECT SUM(amount) FROM transactions WHERE user_id = '$userId' AND transactionType = 'expense' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $expenseResult = mysqli_query($connect, $expenseSql);
    $expenseRow = mysqli_fetch_array($expenseResult);
    $totalExpense = $expenseRow['SUM(amount)'];    

    if (!function_exists('getProfilePic')) {
        function getProfilePic($profilePic) {
            if (empty($profilePic) || !file_exists($profilePic)) {
                echo '../IMG/defaultProfile.png';
            } else {
                echo $profilePic;
            }
        }
    }

    if (!function_exists('getTotalIncome')) {
        function getTotalIncome($totalIncome) {
            switch ($totalIncome) {
                case null:
                    echo "0.00";
                    break;
                default:
                    echo $totalIncome;
            }
        }
    }

    if (!function_exists('getTotalExpense')) {
        function getTotalExpense($totalExpense) {
            switch ($totalExpense) {
                case null:
                    echo "0.00";
                    break;
                default:
                    echo $totalExpense;
            }
        }
    }

    if (!function_exists('getBalance')) {
        function getBalance($totalIncome, $totalExpense) {
            $balance = $totalIncome - $totalExpense;
            switch ($balance) {
                case null:
                    echo "0.00";
                    break;
                default:
                    echo number_format($balance, '2', '.', '');
            }
        }
    }

    if (!function_exists('getSharedExpense')) {
        function getSharedExpense() {
            // Function implementation here
        }
    }
?> 