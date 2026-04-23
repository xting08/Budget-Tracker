<?php
    require_once '../DB/db_connect.php';
    $connect = OpenCon();

    $user_id = $_SESSION['id'];

    // Function to get all budget categories
    function getBudgetCategories() {
        global $connect;
        $query = "SELECT DISTINCT category_id, categoryName FROM categories WHERE transactionType = 'Expense'";
        $result = mysqli_query($connect, $query);
        
        $categories = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $category_id = $row['category_id'];
            $categoryName = $row['categoryName'];
            $categories[$category_id] = $categoryName;
        }
        
        return $categories;
    }

    // Function to get active budgets for the user
    function getActiveBudgets($userId) {
        global $connect;
        $currentDate = date('Y-m-d');
        $query = "SELECT * FROM budgets 
                 WHERE user_id = ? 
                 AND startDate <= ? 
                 AND endDate >= ?
                 ORDER BY endDate ASC";
        
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, "iss", $userId, $currentDate, $currentDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $budgets = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $budgets[] = $row;
        }
        
        return $budgets;
    }

    // Get categories for the dropdown
    $categories = getBudgetCategories();
    
    // Get active budgets
    $activeBudgets = getActiveBudgets($user_id);
?> 