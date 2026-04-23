<?php
include '../FUNCTION/mainFunc.inc.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['categoryName']);
    $transactionType = $_POST['transactionType'];
    $userId = intval($_SESSION['id']);

    // Basic validation
    if ($categoryName === '' || !in_array($transactionType, ['income', 'expense'])) {
        $error = 'Invalid input.';
    } else {
        // Check for duplicate category for this user and type
        $stmt = $connect->prepare("SELECT * FROM categories WHERE categoryName = ? AND transactionType = ? AND user_id = ?");
        $stmt->bind_param("ssi", $categoryName, $transactionType, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = 'Category already exists.';
        } else {
            $stmt->close();
            // Insert new category
            $stmt = $connect->prepare("INSERT INTO categories (categoryName, transactionType, user_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $categoryName, $transactionType, $userId);
            if ($stmt->execute()) {
                $success = 'Category added successfully!';
            } else {
                $error = 'Failed to add category.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../IMG/favicon.png">
    <title>Add Category - Cash Compass</title>
    <link rel="stylesheet" href="../CSS/set.css">
    <link rel="stylesheet" href="../CSS/sideNav.css">
    <link rel="stylesheet" href="../CSS/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include '../FUNCTION/sideNav.inc.php'; ?>
<section id="main">
    <span id="main-title">
        <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
        <a>Add Category</a>
        <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
            <button id="logout-btn" type="submit">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </form>
    </span>
    <hr />
    <section id="add-category-section" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh;">
        <h1 style="color: #232640; margin-bottom: 1em;">Add New Category</h1>
        <form id="add-category-form" action="" method="POST" style="background: #E8E3CD; padding: 2em 2.5em; border-radius: 1em; box-shadow: 0 2px 8px #23264022; min-width: 320px; max-width: 400px; width: 100%; display: flex; flex-direction: column; gap: 1.5em;">
            <div style="display: flex; flex-direction: column; gap: 0.5em;">
                <label for="categoryName" style="font-weight: bold; color: #232640;">Category Name</label>
                <input type="text" id="categoryName" name="categoryName" maxlength="30" required style="padding: 0.7em; border-radius: 0.5em; border: 2px solid #232640; font-size: 1em;">
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.5em;">
                <label for="transactionType" style="font-weight: bold; color: #232640;">Type</label>
                <select id="transactionType" name="transactionType" required style="padding: 0.7em; border-radius: 0.5em; border: 2px solid #232640; font-size: 1em;">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <button type="submit" style="background-color: #294c4b; color: #E8E3CD; border: 2px solid #232640; border-radius: 0.7em; padding: 0.8em 0; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: background 0.3s;">Add Category</button>
            <a href="categories.php" style="text-align: center; color: #294c4b; text-decoration: underline; margin-top: 0.5em;">Back to Categories</a>
        </form>
    </section>
</section>
<?php if ($success): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo $success; ?>',
        showConfirmButton: false,
        timer: 1200
    }).then(() => {
        window.location.href = 'categories.php';
    });
    setTimeout(function(){ window.location.href = 'categories.php'; }, 1300);
</script>
<?php elseif ($error): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $error; ?>',
        showConfirmButton: true
    });
</script>
<?php endif; ?>
</body>
</html> 