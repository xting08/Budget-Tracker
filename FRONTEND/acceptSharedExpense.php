<?php
require_once '../DB/db_connect.php';

// Helper function to output a minimal HTML page with SweetAlert2
function show_swal_and_redirect($icon, $title, $text, $redirect) {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Shared Expense Response</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: " . json_encode($icon) . ",
        title: " . json_encode($title) . ",
        text: " . json_encode($text) . ",
        confirmButtonColor: '#294c4b'
    }).then(function() {
        window.location.href = " . json_encode($redirect) . ";
    });
});
</script>
</body>
</html>";
}

if (!isset($_GET['id']) || !isset($_GET['email']) || !isset($_GET['action'])) {
    show_swal_and_redirect('error', 'Missing Parameters', 'Required parameters are missing.', '../FRONTEND/login.php');
    exit();
}

$connect = OpenCon();
if (!$connect) {
    show_swal_and_redirect('error', 'Database Error', 'DB connection failed.', '../FRONTEND/login.php');
    exit();
}
$shared_expense_id = mysqli_real_escape_string($connect, $_GET['id']);
$recipient_email = mysqli_real_escape_string($connect, $_GET['email']);
$action = mysqli_real_escape_string($connect, $_GET['action']);

mysqli_begin_transaction($connect);

try {
    // Get user ID from email
    $user_query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connect, $user_query);
    if (!$stmt) {
        show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
        exit();
    }
    mysqli_stmt_bind_param($stmt, "s", $recipient_email);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($user_result);

    if (!$user_data) {
        show_swal_and_redirect('error', 'User Not Found', 'User not found.', '../FRONTEND/login.php');
        exit();
    }

    $recipient_id = $user_data['user_id'];

    // Get shared expense details
    $query = "SELECT se.*, ser.percentage, ser.recipient_email, ser.status as recipient_status
             FROM shareexpenses se
             JOIN shared_expense_recipients ser ON se.shareExpense_id = ser.shared_expense_id
             WHERE se.shareExpense_id = ? AND ser.recipient_email = ?";
    $stmt = mysqli_prepare($connect, $query);
    if (!$stmt) {
        show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
        exit();
    }
    mysqli_stmt_bind_param($stmt, "is", $shared_expense_id, $recipient_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $expense = mysqli_fetch_assoc($result);

    if (!$expense) {
        show_swal_and_redirect('error', 'Invalid Invitation', 'Invalid or expired invitation.', '../FRONTEND/login.php');
        exit();
    }

    // Prevent status change if already accepted or rejected
    if ($expense['recipient_status'] === 'accepted' || $expense['recipient_status'] === 'rejected') {
        show_swal_and_redirect(
            'info',
            'Status Already Set',
            'You have already responded to this shared expense. Status cannot be changed.',
            '../FRONTEND/login.php'
        );
        exit();
    }

    if ($action === 'accept' || $action === 'reject') {
        $status = ($action === 'accept') ? 'accepted' : 'rejected';

        // Update recipient status in shared_expense_recipients
        $update_recipient = "UPDATE shared_expense_recipients 
            SET status = ? 
            WHERE shared_expense_id = ? AND recipient_email = ?";
        $stmt = mysqli_prepare($connect, $update_recipient);
        if (!$stmt) {
            show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
            exit();
        }
        mysqli_stmt_bind_param($stmt, "sis", $status, $shared_expense_id, $recipient_email);
        if (!mysqli_stmt_execute($stmt)) {
            show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
            exit();
        }

        if ($action === 'accept') {
            // Add transaction for recipient (should only receive their percentage share)
            $recipient_share = $expense['amount'] * $expense['percentage'] / 100;
            $insert_transaction = "INSERT INTO transactions (user_id, amount, transactionName, description, category_id, transactionType, date, shared_expense_id) 
                                 VALUES (?, ?, ?, ?, ?, 'income', NOW(), ?)";
            $stmt = mysqli_prepare($connect, $insert_transaction);
            if (!$stmt) {
                show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
                exit();
            }
            mysqli_stmt_bind_param($stmt, "idssii", $recipient_id, $recipient_share, $expense['name'], $expense['description'], $expense['category_id'], $shared_expense_id);
            if (!mysqli_stmt_execute($stmt)) {
                show_swal_and_redirect('error', 'Database Error', 'Database error.', '../FRONTEND/login.php');
                exit();
            }
            $message = "You have accepted the shared expense invitation.";
            $icon = "success";
        } else {
            $message = "You have rejected the shared expense invitation.";
            $icon = "info";
        }

        // Check if all recipients have accepted or rejected
        $check_status = "SELECT 
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            COUNT(*) as total
            FROM shared_expense_recipients
            WHERE shared_expense_id = ?";
        $stmt = mysqli_prepare($connect, $check_status);
        mysqli_stmt_bind_param($stmt, "i", $shared_expense_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $status_row = mysqli_fetch_assoc($result);

        if ($status_row['accepted'] == $status_row['total']) {
            // All accepted
            $update_main = "UPDATE shareexpenses SET status = 'accepted' WHERE shareExpense_id = ?";
            $stmt = mysqli_prepare($connect, $update_main);
            mysqli_stmt_bind_param($stmt, "i", $shared_expense_id);
            mysqli_stmt_execute($stmt);
        } elseif ($status_row['rejected'] == $status_row['total']) {
            // All rejected
            $update_main = "UPDATE shareexpenses SET status = 'rejected' WHERE shareExpense_id = ?";
            $stmt = mysqli_prepare($connect, $update_main);
            mysqli_stmt_bind_param($stmt, "i", $shared_expense_id);
            mysqli_stmt_execute($stmt);
        }
    } else {
        show_swal_and_redirect('error', 'Invalid Action', 'Invalid action.', '../FRONTEND/login.php');
        exit();
    }

    // Commit transaction
    mysqli_commit($connect);

    // Show success/info message
    show_swal_and_redirect($icon, 'Shared Expense Response', $message, '../FRONTEND/login.php');

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($connect);
    show_swal_and_redirect('error', 'Error', $e->getMessage(), '../FRONTEND/login.php');
}

mysqli_close($connect);
?> 