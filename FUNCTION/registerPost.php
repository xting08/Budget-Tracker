<?php
    include 'checkInputFunc.inc.php';
    include '../DB/db_connect.php';
    session_start();
    
    if(isset($_POST['submit'])) {
        if(!validateFisrtName($firstName) || !validateLastName($lastName) || !validatePassword($password) || !validateConfirmPassword($password, $confirmPassword)) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Invalid Input!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Registration Input',
                        }).then(function() {
                            window.location.href = '../FRONTEND/register.php';
                        });
                    });
                </script>";
        } else if (!validateUsername($username, $dbUsername)) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Username already exists!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Try another username',
                        }).then(function() {
                            window.location.href = '../FRONTEND/register.php';
                        });
                    });
                </script>";
        } else if (!validateEmail($email, $dbEmail)) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Email already exists!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Try another email',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
        } else {
            $connect = OpenCon();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $connect->prepare($query);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            if ($stmt->execute()) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Please login.',
                                confirmButtonColor: '#294c4b',
                                confirmButtonText: 'Go to Login',
                            }).then(function() {
                                window.location.href = '../FRONTEND/login.php';
                            });
                        });
                    </script>";
                exit();
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                title: 'Failed!',
                                text: 'Registration failed! Username or email may already exist.',
                                icon: 'error',
                                confirmButtonColor: '#5f2824',
                                confirmButtonText: 'Try Again',
                            }).then(function() {
                                window.location.href = '../FRONTEND/register.php';
                            });
                        });
                    </script>";
                exit();
            }
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Registration Failed!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Registration Input',
                        }).then(function() {
                            window.location.href = '../FRONTEND/register.php';
                        });
                    });
                </script>";
    }
?>