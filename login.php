<?php
session_start();
require_once 'db.php';

$error = '';
$requestedRole = $_GET['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Strict Role Authorization: Enforce that user role matches the intended portal
            if (!empty($requestedRole) && $user['role'] !== $requestedRole) {
                if ($requestedRole === 'admin') {
                    $error = 'Access Denied: This account does not have Administrator privileges. Please use the Teacher Portal.';
                } else {
                    $error = 'Access Denied: This account is an Administrator. Please use the Admin Portal.';
                }
            } else {
                // Prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: teacher-dashboard.php");
                }
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $requestedRole ? ucfirst(htmlspecialchars($requestedRole)) . ' Login' : 'Login'; ?> - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
        .login-box {
            max-width: 440px;
            width: 100%;
            padding: 35px 32px;
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }
        
        /* Role Toggle Switcher */
        .role-switch-container {
            display: flex;
            background: #F1F5F9;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #E2E8F0;
            gap: 4px;
        }
        .role-tab {
            flex: 1;
            text-align: center;
            padding: 9px 12px;
            font-size: 0.88rem;
            font-weight: 700;
            color: #64748B;
            text-decoration: none;
            border-radius: 9px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .role-tab.active-admin {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .role-tab.active-teacher {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .role-tab:hover:not(.active-admin):not(.active-teacher) {
            color: #0F172A;
            background: rgba(255, 255, 255, 0.7);
        }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .portal-badge.admin {
            background: #EEF2FF;
            color: #4F46E5;
            border: 1px solid #C7D2FE;
        }
        .portal-badge.teacher {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .portal-badge.general {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #E2E8F0;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 22px 0;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }
        .divider:not(:empty)::before { margin-right: .6em; }
        .divider:not(:empty)::after { margin-left: .6em; }
        .google-btn-container { display: flex; justify-content: center; width: 100%; }
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
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <a href="index.php" class="back-link">← Back to Portal Selection</a>
        
        <div class="login-box">
            <!-- Role Switcher Tabs on Top -->
            <div class="role-switch-container">
                <a href="login.php?role=admin" class="role-tab <?php echo ($requestedRole === 'admin') ? 'active-admin' : ''; ?>">
                    <i data-lucide="shield-check" class="icon-sm"></i> Admin Portal
                </a>
                <a href="login.php?role=teacher" class="role-tab <?php echo ($requestedRole === 'teacher' || empty($requestedRole)) ? 'active-teacher' : ''; ?>">
                    <i data-lucide="pen-tool" class="icon-sm"></i> Teacher Portal
                </a>
            </div>

            <div style="text-align: center; margin-bottom: 22px;">
                <?php if ($requestedRole === 'admin'): ?>
                    <span class="portal-badge admin"><i data-lucide="shield-check" class="icon-xs"></i> Administrator Access</span>
                    <h2 style="color: #1E1B4B; font-size: 1.6rem; font-weight: 800; margin: 4px 0 6px 0; letter-spacing: -0.5px;">
                        DASES Admin Portal
                    </h2>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 0;">
                        Sign in to upload papers, assign teachers & manage results
                    </p>
                <?php else: ?>
                    <span class="portal-badge teacher"><i data-lucide="clipboard-check" class="icon-xs"></i> Evaluator Access</span>
                    <h2 style="color: #064E3B; font-size: 1.6rem; font-weight: 800; margin: 4px 0 6px 0; letter-spacing: -0.5px;">
                        DASES Teacher Portal
                    </h2>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 0;">
                        Sign in to evaluate assigned answer scripts & enter marks
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($_GET['notice'])): ?>
                <div class="flash-alert flash-info" style="padding: 10px 14px; font-size: 0.88rem; margin-bottom: 20px; background: #EEF2FF; color: #3730A3; border: 1.5px solid #C7D2FE; border-radius: 10px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="info" class="icon-sm"></i> <?php echo htmlspecialchars($_GET['notice']); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash-alert flash-danger" style="padding: 10px 14px; font-size: 0.88rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" class="icon-sm"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php<?php echo $requestedRole ? '?role=' . urlencode($requestedRole) : ''; ?>">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="user@dases.edu" autofocus>
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px;">
                        <label style="margin-bottom: 0;">Password</label>
                        <a href="forgot_password.php" style="font-size: 0.8rem; color: var(--primary); font-weight: 700; text-decoration: none;">Forgot?</a>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary" style="<?php echo ($requestedRole === 'teacher' || empty($requestedRole)) ? 'background: linear-gradient(135deg, #10B981 0%, #059669 100%); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);' : ''; ?> width: 100%; padding: 13px; font-size: 1rem; margin-top: 5px;">
                    Sign In as <?php echo ($requestedRole === 'admin') ? 'Admin' : 'Teacher'; ?> →
                </button>
            </form>

            <div class="divider">OR CONTINUE WITH</div>

            <div class="google-btn-container">
                <div id="g_id_onload"
                     data-client_id="149222864112-rik3fb29ti6mec0nlpd2os1p9uthbdgd.apps.googleusercontent.com"
                     data-callback="handleCredentialResponse">
                </div>
                <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with" data-shape="rectangular" data-logo_alignment="left"></div>
            </div>

            <div style="text-align: center; margin-top: 25px; font-size: 0.88rem; color: #64748B; border-top: 1px solid var(--border); padding-top: 18px;">
                Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Create one here</a>
                <div style="margin-top: 8px;">
                    Are you a student? <a href="student-dashboard.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Access Student Dashboard &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function handleCredentialResponse(response) {
            fetch('google_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    credential: response.credential,
                    role: '<?php echo htmlspecialchars($requestedRole); ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert('Authentication Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        }
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>