<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <p style="font-family: sans-serif; text-align: center; margin-top: 50px;">Logging out of DASES...</p>
    <script>
        window.onload = function() {
            // Revoke Google Identity session if logged in via Google
            if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
                google.accounts.id.disableAutoSelect();
            }
            window.location.href = 'login.php';
        };
    </script>
</body>
</html>