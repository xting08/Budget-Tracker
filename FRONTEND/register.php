<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <link rel="stylesheet" href="../CSS/set.css">
        <link rel="stylesheet" href="../CSS/login.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="../JS/checkInput.js"></script>
        <title>Budget Tracker</title>
    </head>

    <body>
        <section id="login-section">
            <div class="title">
                <h1>Register</h1>
                <p>Already have an account? &nbsp;<button type="button" onclick="window.location.href='login.php'">Login</button></p>
                
            </div>

            <div class="login">
                <form id="login-form" action="../FUNCTION/registerPost.php" method="post">
                    <p id="fn-error">&nbsp;</p>
                    <input type="text" name="first-name" id="fname" placeholder="First Name" oninput="validateFName()" onblur="validateFName()" required>
                    <p id="ln-error">&nbsp;</p>
                    <input type="text" name="last-name" id="lname" placeholder="Last Name" oninput="validateLName()" onblur="validateLName()" required>
                    <p id="un-error">&nbsp;</p>
                    <input type="text" name="username" id="username"  placeholder="Username" oninput="validateUsername()" onblur="validateUsername()" required>
                    <p id="email-error">&nbsp;</p>
                    <input type="email" name="email" id="email" placeholder="Email" oninput="validateEmail()" onblur="validateEmail()" required>
                    <p id="p-error">&nbsp;</p>
                    <div id="password-toggle">
                        <input type="password" name="password" id="password" placeholder="Password" oninput="validatePwd()" onblur="validatePwd()" required>
                        <button type="button" id="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="toggle-btn"></i>
                        </button>
                    </div>
                    <p id="cp-error">&nbsp;</p>
                    <div id="password-toggle">
                        <input type="password" name="confirm-password" id= "c-password" placeholder="Confirm Password" oninput="validateCPwd()" onblur="validateCPwd()" required>
                        <button type="button" id="toggle-password" onclick="toggleConfirmPasswordVisibility()">
                            <i class="fa-solid fa-eye" id="toggle-c-btn"></i>
                        </button>
                    </div>
                    <div class="btn-section">
                        <button type="submit" name="submit" id="login-btn">Register</button>
                    </div>
                </form>
            </div>
        </section>
        
        <div id="intro">
            <h1><i class="fa-solid fa-money-check"></i><br>Cash<br>Compass</h1>
        </div>
    </body>
</html>