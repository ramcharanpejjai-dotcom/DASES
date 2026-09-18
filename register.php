<?php
session_start();
require_once 'db.php';

$message = '';
$error = '';

// Predefined Admin Security PIN for authorized administrative account creation
define('ADMIN_SECURITY_PIN', 'DASES@ADMIN2026');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'teacher';
    $admin_pin = trim($_POST['admin_pin'] ?? '');

    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        // Enforce Admin PIN validation if registering as admin
        if ($role === 'admin' && $admin_pin !== ADMIN_SECURITY_PIN) {
            $error = 'Invalid Admin Security PIN. Administrator registration requires authorization.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'An account with this email already exists!';
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user into database
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                    $message = 'Account created successfully! You can now log in.';
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - DASES</title>
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
            background: radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.12) 0%, transparent 60%),
                        linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
        }
        .register-box {
            max-width: 460px;
            width: 100%;
            padding: 40px 35px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-xl);
            border: 1.5px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-xl);
        }
        .back-link {
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--primary); }
        #adminPinGroup { display: none; }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <a href="index.php" class="back-link">← Back to Portal Selection</a>
        <div class="register-box">
            <div style="text-align: center; margin-bottom: 22px;">
                <span class="portal-badge general" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: #EEF2FF; color: #4F46E5; border: 1px solid #C7D2FE; text-transform: uppercase; margin-bottom: 8px;">
                    <i data-lucide="file-signature" class="icon-xs"></i> Institutional Registration
                </span>
                <h2 style="color: #0F172A; font-size: 1.6rem; font-weight: 800; margin: 4px 0 6px 0; letter-spacing: -0.5px;">
                    Create DASES Account
                </h2>
                <p style="color: #64748B; font-size: 0.88rem; margin: 0;">
                    Register for Teacher or Administrator Access
                </p>
            </div>

            <?php if ($error): ?>
                <div class="flash-alert flash-danger" style="padding: 10px 14px; font-size: 0.88rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" class="icon-sm"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="flash-alert flash-success" style="padding: 10px 14px; font-size: 0.88rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="check-circle-2" class="icon-sm"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Dr. Sarah Connor" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" autofocus>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="user@dases.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label>Account Role</label>
                    <select name="role" id="roleSelect" onchange="toggleAdminPin()" required>
                        <option value="teacher" <?php echo (($_POST['role'] ?? '') === 'teacher') ? 'selected' : ''; ?>>Teacher / Evaluator</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator (PIN Required)</option>
                    </select>
                </div>

                <div class="form-group" id="adminPinGroup">
                    <label style="color: #DC2626;">Admin Security PIN <span style="font-weight: normal; font-size: 0.8rem;">(Required for Admin role)</span></label>
                    <input type="password" name="admin_pin" id="adminPinInput" placeholder="Enter Admin Security PIN">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 13px; font-size: 1rem; margin-top: 10px;">
                    Sign Up →
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px; font-size: 0.88rem; color: #64748B; border-top: 1px solid var(--border); padding-top: 20px;">
                Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In here</a>
            </div>
        </div>
    </div>

    <script>
        function toggleAdminPin() {
            const role = document.getElementById('roleSelect').value;
            const pinGroup = document.getElementById('adminPinGroup');
            const pinInput = document.getElementById('adminPinInput');
            if (role === 'admin') {
                pinGroup.style.display = 'block';
                pinInput.setAttribute('required', 'required');
            } else {
                pinGroup.style.display = 'none';
                pinInput.removeAttribute('required');
                pinInput.value = '';
            }
        }
        toggleAdminPin();
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>