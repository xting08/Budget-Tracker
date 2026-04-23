<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../DB/db_connect.php';
$connect = OpenCon();

$savingName = isset($_POST['saving-name']) ? $_POST['saving-name'] : null;
$description = isset($_POST['description']) ? $_POST['description'] : null;
$currentAmount = 0;
$targetAmount = isset($_POST['target-amount']) ? $_POST['target-amount'] : null;
$category_id = isset($_POST['saving-category']) ? $_POST['saving-category'] : null;
$startDate = isset($_POST['start-date']) ? $_POST['start-date'] : null;
$endDate = isset($_POST['end-date']) ? $_POST['end-date'] : null;
$userId = isset($_SESSION['id']) ? $_SESSION['id'] : null;

if (!$userId) {
    die('User not logged in or session id not set.');
}

if(isset($_POST['submit'])){
    $errors = [];

    if (!validateSavingName($savingName)) {
        $errors[] = "Saving name is required and must be less than 30 characters.";
    }
    if (!validateCurrentAmount($currentAmount)) {
        $errors[] = "Current amount must be a non-negative number.";
    }
    if (!validateTargetAmount($targetAmount)) {
        $errors[] = "Target amount must be a non-negative number.";
    }
    if (!validateDate($startDate, $endDate)) {
        $errors[] = "End date must be after start date.";
    }
    if (!validateSavingCategory($category_id)) {
        $errors[] = "Saving category is required.";
    }

    if (!empty($errors)) {
        $errorText = implode('<br>', $errors);
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>\n<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Invalid!',
                    html: '$errorText',
                    icon: 'error',
                    confirmButtonColor: '#5f2824',
                    confirmButtonText: 'Check Input',
                }).then(function() {
                    window.history.back();
                });
            });
        </script>";
    } else {
        // start here
        $sql = "INSERT INTO savingGoals (savingName, description, currentAmount, targetAmount, category_id, startDate, endDate, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "ssddsssi", $savingName, $description, $currentAmount, $targetAmount, $category_id, $startDate, $endDate, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>\n<script>
            function showSwalWhenReady() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Saving Goal Added Successfully!',
                        icon: 'success',
                        confirmButtonColor: '#5f2824',
                        confirmButtonText: 'View Saving Goals',
                    }).then(function() {
                        window.location.href = '../FRONTEND/savingGoals.php';
                    });
                } else {
                    setTimeout(showSwalWhenReady, 50);
                }
            }
            document.addEventListener('DOMContentLoaded', showSwalWhenReady);
        </script>";
    }
} else {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>\n<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Invalid!',
                        text: 'Invalid Input!',
                        icon: 'error',
                        confirmButtonColor: '#5f2824',
                        confirmButtonText: 'Check Transaction Input',
                    }).then(function() {
                        window.history.back();
                    });
                });
            </script>";
}

function validateSavingName($savingName){
    if(empty($savingName) || strlen($savingName) > 30){
        return false;
    }
    return true;
}

function validateCurrentAmount($currentAmount){
    if($currentAmount === null || $currentAmount < 0){
        return false;
    }
    return true;
}

function validateTargetAmount($targetAmount){
    if($targetAmount === null || $targetAmount < 0){
        return false;
    }
    return true;
}

function validateDate($startDate, $endDate){
    if (!empty($startDate) && !empty($endDate) && (strtotime($endDate) < strtotime($startDate))) {
        return false;
    }
    return true;
}

function validateSavingCategory($category_id){
    if(empty($category_id)){
        return false;
    }
    return true;
}
?>