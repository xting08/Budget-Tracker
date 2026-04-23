<?php
    include '../FUNCTION/mainFunc.inc.php';

    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="../IMG/favicon.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
    $connect = OpenCon();
    $userId = $_SESSION['id'];

    $budgetName = $_POST['budgetName'];
    $budgetDescription = $_POST['description'];
    $amountLimit = $_POST['amountLimit'];
    $category_id = $_POST['category_id'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    // Validate input
    if (empty($budgetName) || empty($amountLimit) || empty($category_id) || empty($startDate) || empty($endDate)) {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Please fill in all required fields.',
                icon: 'error',
                confirmButtonColor: '#5f2824'
            }).then(function() {
                window.location.href = '../FRONTEND/addBudget.php';
            });
        </script>";
        exit();
    }

    // Validate dates
    if (strtotime($startDate) > strtotime($endDate)) {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Start date cannot be after end date.',
                icon: 'error',
                confirmButtonColor: '#5f2824'
            }).then(function() {
                window.location.href = '../FRONTEND/addBudget.php';
            });
        </script>";
        exit();
    }

    // Insert into database
    $query = "INSERT INTO budgets (budgetName, description, amountLimit, currentSpend, category_id, startDate, endDate, user_id) 
              VALUES (?, ?, ?, 0, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "ssdssss", $budgetName, $budgetDescription, $amountLimit, $category_id, $startDate, $endDate, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Budget added successfully.',
                icon: 'success',
                confirmButtonColor: '#5f2824'
            }).then(function() {
                window.location.href = '../FRONTEND/budget.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Failed to add budget. Please try again.',
                icon: 'error',
                confirmButtonColor: '#5f2824'
            }).then(function() {
                window.location.href = '../FRONTEND/addBudget.php';
            });
        </script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($connect);
?>
</body>
</html>
<?php
    // End output buffering and flush
    ob_end_flush();
?> 