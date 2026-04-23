<?php
    session_start();
    
    // Only destroy session if it's a confirmed logout
    if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
        session_destroy();
        header("Location: ../FRONTEND/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout Confirmation</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Are you sure you want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit a form to handle the logout
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'logoutPost.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'confirmed';
                    input.value = 'true';
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    window.location.href = document.referrer || '../FRONTEND/main.php';
                }
            });
        });
    </script>
</body>
</html>