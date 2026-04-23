<?php
ob_start();
    require_once '../DB/db_connect.php';
    include '../FUNCTION/mainFunc.inc.php';
    include '../FUNCTION/mailHelper.php';
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        header("Location: ../FRONTEND/login.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
        $connect = OpenCon();
        $user_id = $_SESSION['id'];
        // Fetch current user's email from DB
        $userEmail = '';
        $userQuery = mysqli_query($connect, "SELECT email FROM users WHERE user_id = '" . mysqli_real_escape_string($connect, $user_id) . "'");
        if ($userQuery && $userRow = mysqli_fetch_assoc($userQuery)) {
            $userEmail = strtolower(trim($userRow['email']));
        }
        
        // Get form data
        $amount = mysqli_real_escape_string($connect, $_POST['share-amount']);
        $name = mysqli_real_escape_string($connect, $_POST['shared-name']);
        $description = mysqli_real_escape_string($connect, $_POST['share-description']);
        $category_id = mysqli_real_escape_string($connect, $_POST['share-category']);
        $recipients = $_POST['recipients'];
        $percentages = $_POST['percentages'];

        // Prevent creator from choosing only themselves as recipient
        $other_recipients = array_filter($recipients, function($email) use ($userEmail) {
            return strtolower(trim($email)) !== $userEmail;
        });
        if (count($other_recipients) < 1) {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    icon: "error",
                    title: "Invalid Recipients",
                    text: "You must select at least one recipient other than yourself.",
                    confirmButtonColor: "#294c4b"
                }).then(function() {
                    window.location.href = "../FRONTEND/addShareExpense.php";
                });
            </script>
            </body>
            </html>';
            exit();
        }

        // Validate required fields
        if (empty($amount) || empty($name) || empty($description) || empty($category_id) || empty($recipients) || empty($percentages)) {
            echo "<b>Missing required fields</b>";
            exit();
        }

        // Validate total percentage
        $total_percentage = array_sum($percentages);
        if ($total_percentage != 100) {
            echo "<b>Invalid total percentage: $total_percentage</b>";
            exit();
        }

        // Check user's balance
        $amountSql = "SELECT SUM(amount) FROM transactions WHERE user_id = ? AND transactionType = 'income' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        $stmt = mysqli_prepare($connect, $amountSql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $amountResult = mysqli_stmt_get_result($stmt);
        $amountRow = mysqli_fetch_array($amountResult);
        $totalIncome = $amountRow['SUM(amount)'] ?? 0;

        $expenseSql = "SELECT SUM(amount) FROM transactions WHERE user_id = ? AND transactionType = 'expense' AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        $stmt = mysqli_prepare($connect, $expenseSql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $expenseResult = mysqli_stmt_get_result($stmt);
        $expenseRow = mysqli_fetch_array($expenseResult);
        $totalExpense = $expenseRow['SUM(amount)'] ?? 0;

        $balance = $totalIncome - $totalExpense;

        if ($balance < $amount) {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Insufficient Balance",
                        text: "Your current balance (RM ' . number_format($balance, 2) . ') is insufficient to share this expense (RM ' . number_format($amount, 2) . ').",
                        confirmButtonColor: "#294c4b"
                    }).then(function() {
                        window.location.href = "../FRONTEND/addShareExpense.php";
                    });
                });
            </script>
            </body>
            </html>';
            exit();
        }

        // Validate all recipient emails exist
        foreach ($recipients as $recipient) {
            $recipientEmail = mysqli_real_escape_string($connect, $recipient);
            $userCheck = mysqli_query($connect, "SELECT user_id FROM users WHERE email = '$recipientEmail'");
            if (mysqli_num_rows($userCheck) === 0) {
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                </head>
                <body>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            icon: "error",
                            title: "Recipient Not Found",
                            text: "The email ' . htmlspecialchars($recipient) . ' does not exist in the system.",
                            confirmButtonColor: "#294c4b"
                        }).then(function() {
                            window.location.href = "../FRONTEND/addShareExpense.php";
                        });
                    });
                </script>
                </body>
                </html>';
                exit();
            }
        }

        try {
            // Insert into shared_expenses table
            $insert_shared = "INSERT INTO shareexpenses (user_id, amount, name, description, category_id, date_created, status) 
                            VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
            $stmt = mysqli_prepare($connect, $insert_shared);
            if (!$stmt) {
                echo "Prepare failed: " . mysqli_error($connect);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, "idssi", $user_id, $amount, $name, $description, $category_id);
            if (!mysqli_stmt_execute($stmt)) {
                echo "Execute failed: " . mysqli_stmt_error($stmt);
                exit();
            }
            
            $shared_expense_id = mysqli_insert_id($connect);    

            // Insert into shared_expense_recipients table
            $insert_recipient = "INSERT INTO shared_expense_recipients (shared_expense_id, recipient_email, percentage, status) 
                               VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($connect, $insert_recipient);
            if (!$stmt) {
                echo "Prepare failed for recipients: " . mysqli_error($connect);
                exit();
            }

            foreach ($recipients as $index => $recipient) {
                $percentage = $percentages[$index];
                // If the recipient is the creator, set status as 'accepted', otherwise 'pending'
                $status = ($recipient === $_SESSION['email']) ? 'accepted' : 'pending';
                mysqli_stmt_bind_param($stmt, "isds", $shared_expense_id, $recipient, $percentage, $status);
                if (!mysqli_stmt_execute($stmt)) {  
                    echo "Execute failed for recipient: " . mysqli_stmt_error($stmt);
                    exit();
                }

                // If this is the creator's entry, add the transaction immediately
                if ($status === 'accepted') {
                    $recipient_amount = ($amount * $percentage) / 100;
                    $insert_transaction = "INSERT INTO transactions (user_id, amount, transactionName, description, category_id, transactionType, date, shared_expense_id) 
                                        VALUES (?, ?, ?, ?, ?, 'income', NOW(), ?)";
                    $stmt2 = mysqli_prepare($connect, $insert_transaction);
                    if (!$stmt2) {
                        echo "Prepare failed for creator transaction: " . mysqli_error($connect);
                        exit();
                    }
                    mysqli_stmt_bind_param($stmt2, "idssii", $user_id, $recipient_amount, $name, $description, $category_id, $shared_expense_id);
                    if (!mysqli_stmt_execute($stmt2)) {
                        echo "Execute failed for creator transaction: " . mysqli_stmt_error($stmt2);
                        exit();
                    }
                }
            }

            // Send email to recipient (keep for flow)
            foreach ($recipients as $index => $recipient) {
                // Skip sending email to creator
                if ($recipient === $_SESSION['email']) {
                    continue;
                }
                $percentage = $percentages[$index];
                $recipient_amount = ($amount * $percentage) / 100;
                $subject = "Shared Expense Invitation - Cash Compass";
                $body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #294c4b; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background-color: #f7f2dd; }
                        .button { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                        .accept { background-color: #294c4b; color: white; }
                        .reject { background-color: #5f2824; color: white; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Shared Expense Invitation</h2>
                        </div>
                        <div class='content'>
                            <p>You have been invited to share an expense:</p>
                            <p><strong>Expense Name:</strong> {$name}</p>
                            <p><strong>Description:</strong> {$description}</p>
                            <p><strong>Your Share:</strong> {$recipient_amount}</p>
                            <p><strong>Your Percentage:</strong> {$percentage}%</p>
                            <p>Please click one of the following buttons to respond:</p>
                            <p style='text-align: center;'>
                                <a href='http://localhost/Budget%20Tracker/FRONTEND/acceptSharedExpense.php?id={$shared_expense_id}&email={$recipient}&action=accept' 
                                   class='button accept'>
                                    Accept
                                </a>
                                <a href='http://localhost/Budget%20Tracker/FRONTEND/acceptSharedExpense.php?id={$shared_expense_id}&email={$recipient}&action=reject' 
                                   class='button reject'>
                                    Reject
                                </a>
                            </p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message from Cash Compass. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>";

                if (!sendMail($recipient, $subject, $body)) {
                    throw new Exception("Failed to send email to {$recipient}");
                }
            }

            // Show only the SweetAlert success alert after successful creation, wrapped in minimal HTML
            echo '<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    Swal.fire({
        icon: "success",
        title: "Shared Expense Created",
        text: "Invitations have been sent to all recipients",
        confirmButtonColor: "#294c4b"
    }).then(function() {
        window.location.href = "../FRONTEND/shareExpense.php";
    });
</script>
</body>
</html>';
            exit();

        } catch (Exception $e) {
            echo "<pre>Error: " . $e->getMessage() . "</pre>";
            exit();
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please fill in all required fields.',
                confirmButtonColor: '#294c4b'
            }).then(function() {
                window.location.href = '../FRONTEND/addShareExpense.php';
            });
        </script>";
        exit();
    }
ob_end_flush();
?>