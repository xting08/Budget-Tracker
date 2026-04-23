<?php
include '../FUNCTION/mainFunc.inc.php';
$connect = OpenCon();
$categories = [];
$query = "SELECT category_id, categoryName FROM categories WHERE transactionType = 'expense'";
$result = mysqli_query($connect, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $categories[$row['category_id']] = $row['categoryName'];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <title>Cash Compass</title>
        <link rel="stylesheet" href="../CSS/set.css">
        <link rel="stylesheet" href="../CSS/addSavingGoals.css">
        <link rel="stylesheet" href="../CSS/scrollBar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>

    <body>
        <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>ADD SAVING GOAL</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />    
        </section>
        <nav id="button-nav">
            <button id="back-btn" onclick="window.location.href='../FRONTEND/savingGoals.php'">
                <i class="fa-solid fa-circle-left"></i> &nbsp;Back
            </button>
        </nav>
        <section id="add-transaction-container">
            <form action="../FUNCTION/addSavingGoalsPost.php" method="post" id="add-transaction-form">
                <div class="form-group">
                    <label for="saving-name">Saving Name</label>
                    <input type="text" name="saving-name" id="saving-name" class="form-item" placeholder="Enter Saving Name" required>
                </div>
                <div class="form-group">
                    <label for="description">Saving Description</label>
                    <input type="text" name="description" id="description" class="form-item" placeholder="Enter Saving Description (Optional)">
                </div>
                <div class="form-group">
                    <label for="target-amount">Target Amount</label>
                    <input type="text" name="target-amount" id="target-amount" class="form-item" placeholder="Enter Target Amount" required>
                </div>
                <div class="form-group">
                <label for="category_id">Category</label>
                <select name="saving-category" id="saving-category" class="form-item" required>
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
                    <label for="start-date">Start Date</label>
                    <input type="date" name="start-date" id="start-date" class="form-item" required>
                </div>
                <div class="form-group">
                    <label for="end-date">End Date</label>
                    <input type="date" name="end-date" id="end-date" class="form-item" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="submit" class="form-item" id="add-transaction-btn">Add Saving Goal</button>
                </div>
            </form>
        </section>
    </body>
</html>