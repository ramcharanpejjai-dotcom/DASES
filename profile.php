<?php
require_once 'auth.php';
checkAccess(); // Any logged in user
require_once 'db.php';
require_once 'audit.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$message = '';
$error = '';

// Fetch fresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Handle Profile Name Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    if (!empty($name)) {
        $upd = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        if ($upd->execute([$name, $userId])) {
            $_SESSION['user_name'] = $name;
            $user['name'] = $name;
            logAudit("PROFILE_UPDATE", "User updated display name to: $name");
            $message = "Profile name updated successfully!";
        } else {
            $error = "Failed to update profile name.";
        }
    } else {
        $error = "Name cannot be blank.";
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass)) {
        $error = "All password fields are required.";
    } elseif (!password_verify($current_pass, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($upd->execute([$hashed, $userId])) {
            logAudit("PASSWORD_CHANGE", "User changed account password");
            $message = "Password updated successfully!";
        } else {
            $error = "Failed to update password.";
        }
    }
}

// Mark notifications as read if requested
if (isset($_GET['read_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    header("Location: profile.php");
    exit();
}

// Fetch user notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notifStmt->execute([$userId]);
$notifications = $notifStmt->fetchAll();

$backUrl = ($userRole === 'admin') ? 'admin-dashboard.php' : 'teacher-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile &amp; Settings - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .profile-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
        .notif-item {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.88rem;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .notif-item.unread {
            background: #F0FDF4;
            border-left: 3px solid #10B981;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Top Nav Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1.5px solid var(--border);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 800; color: white;">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div>
                    <h1 style="font-size: 1.4rem; font-weight: 800; color: #0F172A; margin: 0;">Account Profile &amp; Settings</h1>
                    <span style="font-size: 0.82rem; color: #64748B;">Role: <strong style="text-transform: capitalize; color: var(--primary);"><?php echo htmlspecialchars($user['role']); ?></strong> &bull; <?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="btn-secondary" style="padding: 8px 12px; font-size: 0.95rem; border-radius: 8px; cursor: pointer;" title="Toggle Theme" aria-label="Toggle Theme">
                    <i data-lucide="moon" class="icon-sm"></i>
                </button>
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn-secondary" style="padding: 8px 16px; font-size: 0.88rem;">Back to Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="flash-alert flash-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flash-alert flash-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Edit Profile Card -->
            <div class="form-card" style="max-width: 100%;">
                <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: #1E293B;">Personal Details</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="font-size: 0.75rem; color: #94A3B8;">(Read-only)</span></label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #F1F5F9; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Account Role</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled style="background: #F1F5F9; cursor: not-allowed; text-transform: capitalize;">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 11px;">Save Changes</button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="form-card" style="max-width: 100%;">
                <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: #1E293B;">Change Password</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (Min. 6 characters)</label>
                        <input type="password" name="new_password" placeholder="••••••••" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required minlength="6">
                    </div>
                    <button type="submit" class="btn-success" style="width: 100%; padding: 11px;">Update Password</button>
                </form>
            </div>
        </div>

        <!-- In-App Notifications -->
        <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-top: 25px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #1E293B; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="bell" class="icon-md" style="color: var(--accent);"></i> Activity &amp; Email Notifications
                </h3>
                <?php if (count($notifications) > 0): ?>
                    <a href="profile.php?read_all=1" style="font-size: 0.8rem; color: var(--primary); font-weight: 700; text-decoration: none;">Mark all as read</a>
                <?php endif; ?>
            </div>

            <?php if (count($notifications) > 0): ?>
                <div style="border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden;">
                    <?php foreach ($notifications as $n): ?>
                        <div class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                            <div style="color: <?php echo $n['is_read'] ? '#94A3B8' : 'var(--primary)'; ?>; margin-top: 2px;">
                                <i data-lucide="<?php echo $n['is_read'] ? 'mail-open' : 'mail'; ?>" class="icon-md"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: #0F172A;"><?php echo htmlspecialchars($n['title']); ?></div>
                                <div style="color: #475569; margin-top: 2px;"><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                                <div style="font-size: 0.75rem; color: #94A3B8; margin-top: 4px;"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #94A3B8; font-size: 0.9rem; text-align: center; margin: 20px 0;">No notifications yet.</p>
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
