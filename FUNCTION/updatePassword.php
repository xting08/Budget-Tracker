<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once '../DB/db_connect.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../FRONTEND/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../FRONTEND/profile.php");
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: ../FRONTEND/profile.php");
        exit();
    }

    if (strlen($newPassword) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: ../FRONTEND/profile.php");
        exit();
    }

    $connect = OpenCon();

    // Verify current password
    $query = "SELECT password FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $dbPassword = $row['password'];
        $isHashed = (strlen($dbPassword) > 30 && (strpos($dbPassword, '$2y$') === 0 || strpos($dbPassword, '$argon2') === 0));
        if ($isHashed) {
            if (!password_verify($currentPassword, $dbPassword)) {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: ../FRONTEND/profile.php");
                CloseCon($connect);
                exit();
            }
        } else {
            // Plain text legacy password
            if ($currentPassword !== $dbPassword) {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: ../FRONTEND/profile.php");
                CloseCon($connect);
                exit();
            }
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: ../FRONTEND/profile.php");
        CloseCon($connect);
        exit();
    }

    // Update password (always hashed)
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $query = "UPDATE users SET password = ? WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Password updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating password.";
    }

    CloseCon($connect);
    header("Location: ../FRONTEND/profile.php");
    exit();
} else {
    header("Location: ../FRONTEND/profile.php");
    exit();
} 