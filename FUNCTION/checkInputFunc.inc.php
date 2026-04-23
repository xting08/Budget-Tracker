<?php
    require_once '../DB/db_connect.php';

    $connect =OpenCon();

    // --- AJAX email validation endpoint ---
    if (isset($_GET['email'])) {
        error_reporting(0);
        ini_set('display_errors', 0);
        header('Content-Type: application/json');
        $email = mysqli_real_escape_string($connect, $_GET['email']);
        $query = "SELECT user_id FROM users WHERE email = '$email'";
        $result = mysqli_query($connect, $query);
        $exists = ($result && mysqli_num_rows($result) > 0);
        echo json_encode(['exists' => $exists]);
        exit();
    }

    $firstName = $_POST['first-name'];
    $lastName = $_POST['last-name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    $userSql = "SELECT username, email FROM users WHERE username = '$username' OR email = '$email'";
    $userResult = mysqli_query($connect, $userSql);
    $userRow = mysqli_fetch_array($userResult);

    if ($userRow > 0) {
        $dbUsername = $userRow['username'];
        $dbEmail = $userRow['email'];
    } else {
        $dbUsername = null;
        $dbEmail = null;
    }

    function validateFisrtName($firstName) {
        if (empty($firstName) || 
           strlen($firstName) > 50) {
            return false;
        } else {
            return true;
        }
    }

    function validateLastName($lastName) {
        if (empty($lastName) || 
           (strlen($lastName) > 50)) {
            return false;
        } else {
            return true;
        }
    }

    function validateUsername($username, $dbUsername) {
        if (empty($username) || 
           (strlen($username) > 30) || 
           (strlen($username) < 5) || 
           ($username == $dbUsername)) {
            return false;
        } else {
            return true;
        }
    }

    function validateEmail($email, $dbEmail) {
        if (empty($email) || 
           (strlen($email) > 50) || 
           !filter_var($email, FILTER_VALIDATE_EMAIL) || 
           ($email == $dbEmail)) {
            return false;
        } else {
            return true;
        }
    }

    function validatePassword($password) {
        if (empty($password) || 
           (strlen($password) < 8) || 
           (strlen($password) > 30)) {
            return false;
        } else {
            return true;
        }
    }

    function validateConfirmPassword($password, $confirmPassword) {
        if (empty($confirmPassword) || 
           ($password != $confirmPassword)) {
            return false;
        } else {
            return true;
        }
    }

    function validateAll($firstName, $lastName, $username, $email, $password, $confirmPassword, $dbUsername, $dbEmail) {
        if (validateFisrtName($firstName) && validateLastName($lastName) && validateUsername($username, $dbUsername) && validateEmail($email, $dbEmail) && validatePassword($password) && validateConfirmPassword($password, $confirmPassword)) {
            return true;
        } else {
            return false;
        }
    }

    function insertUser($connect, $firstName, $lastName, $username, $password, $email) {
        $sql = "INSERT INTO users (firstName, lastName, username, password, email) VALUES ('$firstName', '$lastName', '$username', '$password', '$email')";
        $insert = $connect -> query($sql);

        if ($insert) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Registration Successful!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.location.href = '../FRONTEND/login.php';
                            }
                        });
                    });
                </script>";
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Failed!',
                            text: 'Registration Failed!',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Check Registration Input',
                        }).then(function() {
                            window.location.href = 'window.history.back()';
                        });
                    });
                </script>";
        }
    }
?>