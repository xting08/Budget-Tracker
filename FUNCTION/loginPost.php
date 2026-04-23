<?php
include '../DB/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = $_POST['username_email']; 
    $password = $_POST['password'];
    $connect = OpenCon();

    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $dbPassword = $row['password'];
        $isHashed = (strlen($dbPassword) > 30 && (strpos($dbPassword, '$2y$') === 0 || strpos($dbPassword, '$argon2') === 0));
        $loginSuccess = false;
        if ($isHashed) {
            if (password_verify($password, $dbPassword)) {
                $loginSuccess = true;
            }
        } else {
            // Legacy plain text password
            if ($password === $dbPassword) {
                $loginSuccess = true;
                // Upgrade to hash
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $connect->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update->bind_param("si", $hashedPassword, $row['user_id']);
                $update->execute();
            }
        }
        if ($loginSuccess) {
            $_SESSION['id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['profile_pic'] = isset($row['profile_pic']) && $row['profile_pic'] ? $row['profile_pic'] : '../IMG/defaultProfile.png';
            $_SESSION['email'] = $row['email'];
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Login Successful!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/main.php';
                            }
                        });
                    });
                </script>";
            CloseCon($connect);
            exit();
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Invalid Username or Password!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Try Again',
                        }).then(function() {
                            window.location.href = '../FRONTEND/login.php';
                        });
                    });
                </script>";
            CloseCon($connect);
            exit();
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Failed!',
                        text: 'Invalid Username or Password!',
                        icon: 'error',
                        confirmButtonColor: '#5f2824',
                        confirmButtonText: 'Try Again',
                    }).then(function() {
                        window.location.href = '../FRONTEND/login.php';
                    });
                });
            </script>";
        CloseCon($connect);
        exit();
    }
} else {
    header('Location: ../FRONTEND/login.php');
    exit();
}