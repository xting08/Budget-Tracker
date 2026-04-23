<?php
include '../FUNCTION/mainFunc.inc.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../IMG/favicon.png">
    <title>Cash Compass</title>
    <link rel="stylesheet" href="../CSS/set.css">
    <link rel="stylesheet" href="../CSS/sideNav.css">
    <link rel="stylesheet" href="../CSS/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include '../FUNCTION/sideNav.inc.php'; ?>
    <section id="main">
        <span id="main-title">
            <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
            <a>PROFILE</a>
            <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                <button id="logout-btn" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </span>
        <hr />
        <section id="profile">
            <div id="profile-container">
                <div id="profile-picture-section">
                    <h2>Profile Picture</h2>
                    <div id="current-profile-pic">
                        <?php
                            if (session_status() === PHP_SESSION_NONE) session_start();
                            $profilePic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../IMG/defaultProfile.png';
                            
                            // Fetch user details
                            include_once '../DB/db_connect.php';
                            $userId = $_SESSION['id'];
                            $connect = OpenCon();
                            $query = "SELECT username, email FROM users WHERE user_id = ?";
                            $stmt = $connect->prepare($query);
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $userDetails = $result->fetch_assoc();
                            CloseCon($connect);
                        ?>
                        <img src="../FUNCTION/getProfilePic.php" alt="Profile Picture" id="profile-pic-preview">
                    </div>
                    <form action="../FUNCTION/updateProfilePic.php" method="post" enctype="multipart/form-data" id="profile-pic-form">
                        <div id="profile-pic-upload">
                            <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*" required>
                            <label for="profile-pic-input" class="upload-btn">Choose File</label>
                        </div>
                        <button type="submit" id="update-pic-btn">Update Profile Picture</button>
                    </form>
                    <div id="user-info">
                        <div class="info-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($userDetails['username']); ?>" readonly>
                        </div>
                        <div class="info-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($userDetails['email']); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div id="password-section">
                    <h2>Change Password</h2>
                    <form action="../FUNCTION/updatePassword.php" method="post" id="password-form">
                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <div class="password-input-container">
                                <input type="password" name="current_password" id="current-password" required>
                                <i class="fa-solid fa-eye-slash toggle-password" data-target="current-password"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <div class="password-input-container">
                                <input type="password" name="new_password" id="new-password" required>
                                <i class="fa-solid fa-eye-slash toggle-password" data-target="new-password"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password</label>
                            <div class="password-input-container">
                                <input type="password" name="confirm_password" id="confirm-password" required>
                                <i class="fa-solid fa-eye-slash toggle-password" data-target="confirm-password"></i>
                            </div>
                        </div>
                        <button type="submit" id="update-password-btn">Update Password</button>
                    </form>
                </div>
            </div>
        </section>
    </section>

    <script>
        // SweetAlert for profile pic update
        <?php if (isset($_SESSION['success'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo addslashes($_SESSION['success']); ?>',
                    confirmButtonColor: '#294c4b'
                });
            });
        <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo addslashes($_SESSION['error']); ?>',
                    confirmButtonColor: '#5f2824'
                });
            });
        <?php unset($_SESSION['error']); endif; ?>

        // Preview profile picture before upload
        document.getElementById('profile-pic-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-pic-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                }
            });
        });

        // Password form validation
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirm password do not match!');
                return;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
        });
    </script>
</body>

</html> 