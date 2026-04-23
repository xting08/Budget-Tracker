<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once '../DB/db_connect.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../FRONTEND/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_pic'])) {
    $userId = $_SESSION['id'];
    $file = $_FILES['profile_pic'];
    $connect = OpenCon();

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        header("Location: ../FRONTEND/profile.php");
        exit();
    }

    if ($file['size'] > $maxSize) {
        $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
        header("Location: ../FRONTEND/profile.php");
        exit();
    }

    // Read image content
    $imageData = file_get_contents($file['tmp_name']);

    $query = "UPDATE users SET profilePic = ? WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("bi", $null, $userId); // 'b' for blob, 'i' for int
    $null = NULL;
    $stmt->send_long_data(0, $imageData);

    try {
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile picture updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating profile picture in database.";
        }
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'max_allowed_packet') !== false) {
            $_SESSION['error'] = "File is too large for the server to handle. Please upload a smaller image.";
        } else {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }

    CloseCon($connect);
    header("Location: ../FRONTEND/profile.php");
    exit();
} else {
    header("Location: ../FRONTEND/profile.php");
    exit();
} 