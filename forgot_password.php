<?php
session_start();
require_once 'db.php';
require_once 'audit.php';

$message = '';
$error = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));

        // Invalidate old tokens for this email
        $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $delStmt->execute([$email]);

        // Insert new token with 1 hour expiration using MySQL NOW()
        $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $insStmt->execute([$email, $token]);

        $baseUrl = getDasesBaseUrl();
        $resetUrl = $baseUrl . "/reset_password.php?token=" . urlencode($token);
        $resetLink = $resetUrl;

        // 1. Send dedicated HTML email via mailer service
        sendPasswordResetEmail($email, $user['name'] ?? 'User', $resetUrl);

        // 2. In-system notification
        sendNotification($user['id'], "Password Reset Link Generated", "A password reset link was requested for your account. If you did not request this, please verify your credentials.", "reset_password.php?token=" . urlencode($token));

        logAudit("PASSWORD_RESET_REQUEST", "Reset token generated for $email");
        $message = "Password reset instructions have been dispatched! Check your email or use the direct reset link below.";
    } else {
        $error = "No registered account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DASES</title>
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
        <a href="login.php" style="margin-bottom: 20px; font-size: 0.9rem; color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back to Sign In</a>
        <div class="reset-box">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="display: flex; justify-content: center; margin-bottom: 10px;">
                    <i data-lucide="key-round" style="width: 48px; height: 48px; color: #6366F1;"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Forgot Password?</h2>
                <p style="color: #64748B; font-size: 0.9rem;">Enter your registered email and we'll help you reset your password.</p>
            </div>

            <?php if ($message): ?>
                <div class="flash-alert flash-success" style="margin-bottom: 18px; font-size: 0.88rem; display: flex; align-items: flex-start; gap: 8px;">
                    <i data-lucide="check-circle-2" class="icon-sm" style="margin-top: 3px;"></i>
                    <div>
                        <?php echo htmlspecialchars($message); ?>
                        <?php if ($resetLink): ?>
                            <div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.7); border-radius: 8px; word-break: break-all;">
                                <strong>Direct Reset Link:</strong><br>
                                <a href="<?php echo htmlspecialchars($resetLink); ?>" style="color: #047857; font-weight: 700; font-size: 0.85rem;"><?php echo htmlspecialchars($resetLink); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash-alert flash-danger" style="margin-bottom: 18px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" class="icon-sm"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label>Registered Email Address</label>
                    <input type="email" name="email" placeholder="e.g. yourname@gmail.com" required autofocus>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 13px; font-size: 0.95rem; margin-top: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    Send Reset Link <i data-lucide="arrow-right" class="icon-sm"></i>
                </button>
            </form>

            <div style="text-align: center; margin-top: 24px; font-size: 0.88rem; color: #64748B; border-top: 1px solid var(--border); padding-top: 18px;">
                Remembered your password? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In</a>
            </div>
        </div>
    </div>
    <script src="animations.js?v=3.0.1788777855" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
