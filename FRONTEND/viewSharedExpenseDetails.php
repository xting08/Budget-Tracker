<?php
require_once '../DB/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
}

if (!isset($_GET['id'])) {
    echo "Missing shared expense ID.";
    exit();
}

$shared_expense_id = intval($_GET['id']);
$user_id = $_SESSION['id'];
$user_email = $email;
$connect = OpenCon();

// Get main shared expense info
$query = "SELECT * FROM shareexpenses WHERE shareExpense_id = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "i", $shared_expense_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$expense = mysqli_fetch_assoc($result);

if (!$expense) {
    echo "Shared expense not found.";
    exit();
}

// Determine if user is creator
$is_creator = ($expense['user_id'] == $user_id);

// Get recipient row for this user (if any)
$my_rec = null;
// Try by email
$query = "SELECT ser.*, u.username, u.user_id FROM shared_expense_recipients ser
          LEFT JOIN users u ON ser.recipient_email = u.email
          WHERE ser.shared_expense_id = ? AND ser.recipient_email = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "is", $shared_expense_id, $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$my_rec = mysqli_fetch_assoc($result);
// If not found by email, try by username
if (!$my_rec && $username) {
    $query = "SELECT ser.*, u.username, u.user_id FROM shared_expense_recipients ser
              LEFT JOIN users u ON ser.recipient_email = u.email
              WHERE ser.shared_expense_id = ? AND u.username = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "is", $shared_expense_id, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_rec = mysqli_fetch_assoc($result);
}
// If not found and user is creator, try by user_id
if (!$my_rec && $is_creator) {
    $query = "SELECT ser.*, u.username, u.user_id FROM shared_expense_recipients ser
              LEFT JOIN users u ON ser.recipient_email = u.email
              WHERE ser.shared_expense_id = ? AND u.user_id = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "ii", $shared_expense_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_rec = mysqli_fetch_assoc($result);
}

// Set share details for 'Your Share' section
if ($my_rec) {
    $percentage = $my_rec['percentage'];
    $my_share = $expense['amount'] * $percentage / 100;
    // Calculate usage (only expenses)
    $used_query = "SELECT IFNULL(SUM(amount),0) as used FROM transactions WHERE user_id = ? AND shared_expense_id = ? AND transactionType = 'expense'";
    $stmt = mysqli_prepare($connect, $used_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $shared_expense_id);
    mysqli_stmt_execute($stmt);
    $used_result = mysqli_stmt_get_result($stmt);
    $used_row = mysqli_fetch_assoc($used_result);
    $used = $used_row['used'];
    $remaining = $my_share - $used;
} else if ($is_creator) {
    // Creator but not a recipient row: show 100%
    $percentage = 100;
    $my_share = $expense['amount'];
    $used_query = "SELECT IFNULL(SUM(amount),0) as used FROM transactions WHERE user_id = ? AND shared_expense_id = ? AND transactionType = 'expense'";
    $stmt = mysqli_prepare($connect, $used_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $shared_expense_id);
    mysqli_stmt_execute($stmt);
    $used_result = mysqli_stmt_get_result($stmt);
    $used_row = mysqli_fetch_assoc($used_result);
    $used = $used_row['used'];
    $remaining = $my_share - $used;
} else {
    // Not a recipient and not creator (should not happen)
    $percentage = 0;
    $my_share = 0;
    $used = 0;
    $remaining = 0;
}

// Calculate if fully used
$is_fully_used = ($used >= $my_share && $my_share > 0);

// Get all recipients
$rec_query = "SELECT ser.*, u.username, u.user_id FROM shared_expense_recipients ser LEFT JOIN users u ON ser.recipient_email = u.email WHERE shared_expense_id = ?";
$stmt = mysqli_prepare($connect, $rec_query);
mysqli_stmt_bind_param($stmt, "i", $shared_expense_id);
mysqli_stmt_execute($stmt);
$rec_result = mysqli_stmt_get_result($stmt);

// Calculate per-recipient usage and check if all are fully used
$all_fully_used = true;
$recipients = [];
$total_used = 0;
if ($rec_result) {
    while ($row = mysqli_fetch_assoc($rec_result)) {
        $recipient_id = $row['user_id'];
        $share = $expense['amount'] * $row['percentage'] / 100;
        $used_query = "SELECT IFNULL(SUM(amount),0) as used FROM transactions WHERE user_id = ? AND shared_expense_id = ? AND transactionType = 'expense'";
        $used_stmt = mysqli_prepare($connect, $used_query);
        mysqli_stmt_bind_param($used_stmt, "ii", $recipient_id, $shared_expense_id);
        mysqli_stmt_execute($used_stmt);
        $used_result = mysqli_stmt_get_result($used_stmt);
        $used = mysqli_fetch_assoc($used_result)['used'];
        $remaining = $share - $used;
        $row['share'] = $share;
        $row['used'] = $used;
        $row['remaining'] = $remaining;
        $recipients[] = $row;
        $total_used += $used;
        if ($used < $share) {
            $all_fully_used = false;
        }
    }
}
$total_amount = $expense['amount'];
$percent_used_total = $total_amount > 0 ? min(100, ($total_used / $total_amount) * 100) : 0;
$percent_remaining_total = 100 - $percent_used_total;
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
        #add-shared-expense-container h2 {
            color: #232640;
            margin-bottom: 1.2em;
        }
        #add-shared-expense-container p {
            font-size: 1.1em;
            color: #4e606f;
            margin: 0.3em 0 0.7em 0;
        }
        #add-shared-expense-container h3 {
            color: #294c4b;
            margin-top: 1.5em;
            margin-bottom: 0.7em;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5em;
        }
        .info-row b {
            color: #232640;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
            background: #E8E3CD;
            border-radius: 0.5em;
            overflow: hidden;
        }
        th, td {
            padding: 0.8em 1.2em;
            text-align: center;
        }
        th {
            background: #4e606f;
            color: #fffbea;
            font-weight: 600;
            font-size: 1em;
        }
        td {
            border-bottom: 1px dashed #8c716f;
            color: #232640;
            font-size: 1em;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover td {
            background: #f1ecd7;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.7em 2em;
            margin-bottom: 1.5em;
        }
        .details-grid div {
            font-size: 1.1em;
            color: #4e606f;
        }
        .your-share-section {
            margin-bottom: 1.5em;
        }
        .your-share-section h3 {
            margin-bottom: 0.5em;
            color: #294c4b;
        }
        .your-share-section div {
            font-size: 1.1em;
            color: #4e606f;
            margin-bottom: 0.3em;
        }
        .details-card {
            background:rgb(254, 251, 232);
            border-radius: 0.7em;
            border: 2px solid #8c716f;
            padding: 2em 2.5em;
            margin: 2em auto;
            width: 30%;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.7em;
        }
        .details-label {
            font-weight: 600;
            color: #232640;
            min-width: 140px;
        }
        .details-card > hr {
            border: none;
            border-top: 1px solid #d2cbcb;
            margin: 1.5em 0;
        }
        #shared-expense-details-container {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            gap: 3em;
            width: 100%;
        }
        .recipients-card {
            margin-top: 1em;
            width: 70%;
        }
    </style>
</head>
<body>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>SHARED EXPENSE DETAILS</a>
            <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                <button id="logout-btn" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </span>
        <hr />    
    </section>
    <nav id="button-nav">
        <button id="back-btn" onclick="window.location.href='../FRONTEND/shareExpense.php'">
            <i class="fa-solid fa-circle-left"></i> &nbsp;Back
        </button>
    </nav>
    <section id="shared-expense-details-container">
        <div class="details-card">
            <h2>Shared Expense Details</h2>
            <hr>
            <div class="details-row"><span class="details-label">Name:</span><span><?= htmlspecialchars($expense['name']) ?></span></div>
            <div class="details-row"><span class="details-label">Total Amount:</span><span>RM<?= number_format($expense['amount'],2) ?></span></div>
            <div class="details-row"><span class="details-label">Description:</span><span><?= htmlspecialchars($expense['description']) ?></span></div>
            <div class="details-row"><span class="details-label">Date:</span><span><?= htmlspecialchars($expense['date_created']) ?></span></div>
            <hr>
            <h3>Total Usage</h3>
            <div class="progress-bar-container" style="margin-bottom:1em;">
                <?php if ($percent_used_total < 30): ?>
                    <div class="progress-bar-filled" style="width: <?= $percent_used_total ?>%"></div>
                    <div class="progress-bar-remaining" style="width: <?= $percent_remaining_total ?>%; display: flex; align-items: center; justify-content: flex-start; padding-left: 0.7em; font-size: 0.7em; color: #5c4442;">
                        <span class="progress-bar-text">RM <?= number_format($total_used,2) ?> used</span>
                    </div>
                <?php else: ?>
                    <div class="progress-bar-filled" style="width: <?= $percent_used_total ?>%; text-align: right; padding-right: 0.7em; font-size: 0.7em; color: #5c4442; align-items: center; display: flex; justify-content: flex-end;">
                        <span class="progress-bar-text">RM <?= number_format($total_used,2) ?> used</span>
                    </div>
                    <div class="progress-bar-remaining" style="width: <?= $percent_remaining_total ?>%"></div>
                <?php endif; ?>
                <span class="shared-expense-amount">RM <?= number_format($total_amount,2) ?> total</span>
            </div>
            <h3>You Get</h3>
            <div class="details-row"><span class="details-label">Percentage of Shared Expense:</span><span><?= $percentage ?>%</span></div>
            <div class="details-row"><span class="details-label">Your Allocated Amount:</span><span>RM<?= number_format($my_share,2) ?></span></div>
            <?php if ((($expense['status'] == 'rejected' && $expense['user_id'] == $user_id) || $all_fully_used)): ?>
                <button id="delete-shared-expense-btn" onclick="confirmDelete(<?= $shared_expense_id ?>)" style="font-weight: 600; background:#5f2824;color:#f7f2dd;padding:1em 2em;border:none;border-radius:8px;cursor:pointer; width: 100%; margin-top: 1em;">Delete Shared Expense</button>
            <?php endif; ?>
        </div>
        <div class="recipients-card">
            <h3>Recipients</h3>
            <table border="1">
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Share</th>
                    <th>Used</th>
                    <th>Remaining</th>
                </tr>
                <?php foreach ($recipients as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['recipient_email']) ?></td>
                    <td><?= $row['percentage'] ?>%</td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>RM<?= number_format($row['share'],2) ?></td>
                    <td>RM<?= number_format($row['used'],2) ?></td>
                    <td>RM<?= number_format($row['remaining'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            // First confirmation
            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#5f2824",
                cancelButtonColor: "#294c4b",
                confirmButtonText: "Yes, proceed"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Second confirmation
                    Swal.fire({
                        title: "Final Confirmation",
                        text: "Are you absolutely sure you want to delete this shared expense?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#5f2824",
                        cancelButtonColor: "#294c4b",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Send delete request
                            fetch('deleteSharedExpense.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'id=' + id
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "Your shared expense has been deleted.",
                                        icon: "success"
                                    }).then(() => {
                                        window.location.href = 'shareExpense.php';
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
                                        text: data.message || "Failed to delete shared expense.",
                                        icon: "error"
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: "Error!",
                                    text: "An error occurred while deleting the shared expense.",
                                    icon: "error"
                                });
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html> 