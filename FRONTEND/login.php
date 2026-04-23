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
                <h1>Login</h1>
                <p>Don't have account? &nbsp;<button type="button" onclick="window.location.href='register.php'">Register</button></p>
            </div>

            <div class="login">
                <form id="login-form" action="../FUNCTION/loginPost.php" method="post">
                    <input type="text" name="username_email" id="username_email" placeholder="Username or Email" required autocomplete="off"> 
                    <div id="password-toggle">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <button type="button" id="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="toggle-btn"></i>
                        </button>
                    </div>

                    <div class="btn-section">
                        <button type="submit" name="submit" id="login-btn">Login</button>
                    </div>
                </form>
            </div>
        </section>
        
        <div id="intro">
            <h1><i class="fa-solid fa-money-check"></i><br>Cash<br>Compass</h1>
        </div>
    </body>
</html>