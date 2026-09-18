<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAccess($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        $loginUrl = "login.php";
        if ($requiredRole) {
            $loginUrl .= "?role=" . urlencode($requiredRole);
        }
        header("Location: $loginUrl");
        exit();
    }

    if ($requiredRole && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole)) {
        $loginUrl = "login.php?role=" . urlencode($requiredRole) . "&notice=" . urlencode("This section requires " . ucfirst($requiredRole) . " access. Please sign in with an authorized account.");
        header("Location: $loginUrl");
        exit();
    }
}
?>