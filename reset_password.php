<?php
session_start();
require_once 'db.php';
require_once 'audit.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = '';
$success = '';
$validToken = false;
$email = '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$token]);
    $resetRecord = $stmt->fetch();

    if ($resetRecord) {
        $validToken = true;
        $email = $resetRecord['email'];
    } else {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $error = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($upd->execute([$hashed, $email])) {
            // Delete used token
            $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$email]);

            logAudit("PASSWORD_RESET_SUCCESS", "Password updated successfully for $email");
            $success = "Your password has been reset successfully! You can now log in.";
            $validToken = false; // Form completed
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }
        .reset-box {
            max-width: 440px;
            width: 100%;
            padding: 36px 32px;
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            box-shadow: var(--shadow-xl);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="reset-box">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="display: flex; justify-content: center; margin-bottom: 12px; color: var(--accent);">
                    <i data-lucide="key-round" class="icon-hero"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Set New Password</h2>
                <?php if ($validToken): ?>
                    <p style="color: #64748B; font-size: 0.9rem;">Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong></p>
                <?php endif; ?>
            </div>

            <?php if ($success): ?>
                <div class="flash-alert flash-success" style="margin-bottom: 20px;">
                    <div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <i data-lucide="check-circle-2" class="icon-md" style="flex-shrink: 0; margin-top: 2px;"></i>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                        <br>
                        <a href="login.php" class="btn-primary" style="display: inline-block; padding: 8px 16px; font-size: 0.88rem; text-decoration: none;">Proceed to Login →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash-alert flash-danger" style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="alert-circle" class="icon-md" style="flex-shrink: 0;"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
                <?php if (!$validToken && !$success): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="forgot_password.php" class="btn-secondary">Request New Reset Link</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST" action="reset_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Min. 6 characters" required autofocus minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 13px; font-size: 0.95rem; margin-top: 8px;">
                        Update Password &amp; Login →
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>
