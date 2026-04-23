<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../IMG/favicon.png">
    <title>Cash Compass</title>
    <link rel="stylesheet" href="../CSS/set.css">
    <link rel="stylesheet" href="../CSS/addBudget.css">
    <link rel="stylesheet" href="../CSS/scrollBar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
        include '../FUNCTION/mainFunc.inc.php';
        include '../FUNCTION/addBudget.inc.php';
    ?>
    <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>ADD BUDGET</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />    
        </section>
        <nav id="button-nav">
            <button id="back-btn" onclick="window.location.href='../FRONTEND/budget.php'">
                <i class="fa-solid fa-circle-left"></i> &nbsp;Back
            </button>
        </nav>
    <section id="add-transaction-container">
        <form action="../FUNCTION/addBudgetPost.php" method="post" id="add-transaction-form">
            <div class="form-group">
                <label for="budget-name">Budget Name</label>
                <input type="text" name="budgetName" id="budget-name" class="form-item" placeholder="Enter Budget Name" required>
            </div>
            <div class="form-group">
                <label for="description">Budget Description</label>
                <input type="text" name="description" id="description" class="form-item" placeholder="Enter Budget Description (Optional)">
            </div>
            <div class="form-group">
                <label for="amountLimit">Budget Limit</label>
                <input type="number" step="0.01" min="0" name="amountLimit" id="budget-limit" class="form-item" required>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="budget-category" class="form-item" required>
                    <option value="" disabled selected>Select Category</option>
                    <?php
                        if (!empty($categories)) {
                            foreach ($categories as $category_id => $categoryName) {
                                echo "<option value='" . htmlspecialchars($category_id) . "'>" . htmlspecialchars(ucfirst($categoryName)) . "</option>";
                            }
                        } else {
                            // Default categories if none exist in database
                            $defaultCategories = ['food', 'transportation', 'entertainment', 'shopping', 'other'];
                            foreach ($defaultCategories as $category_id => $categoryName) {
                                echo "<option value='" . htmlspecialchars($category_id) . "'>" . htmlspecialchars(ucfirst($categoryName)) . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="startDate">Start Date</label>
                <input type="date" name="startDate" id="start-date" class="form-item" required>
            </div>
            <div class="form-group">
                <label for="endDate">End Date</label>
                <input type="date" name="endDate" id="end-date" class="form-item" required>
            </div>
            <div class="form-group">
                <button type="submit" name="submit" class="form-item" id="add-transaction-btn">Add Budget</button>
            </div>
        </form>
    </section>
</section>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html> 