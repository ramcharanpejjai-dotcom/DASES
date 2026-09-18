<?php
require_once 'auth.php';
checkAccess('teacher');
require_once 'db.php';

$teacher_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'pending';
$msg = $_GET['msg'] ?? '';
$search = trim($_GET['search'] ?? '');

$searchParam = "%$search%";

// Fetch pending/assigned papers for this teacher
$pendingSQL = "
    SELECT id, paper_code, dummy_token, subject_name, subject_code, status, uploaded_at 
    FROM papers 
    WHERE assigned_teacher_id = ? AND (status = 'pending' OR status = 'assigned')
";
$pendingParams = [$teacher_id];
if ($search !== '') {
    $pendingSQL .= " AND (paper_code LIKE ? OR subject_name LIKE ? OR subject_code LIKE ?)";
    $pendingParams = array_merge($pendingParams, [$searchParam, $searchParam, $searchParam]);
}
$pendingSQL .= " ORDER BY id DESC";
$pendingStmt = $pdo->prepare($pendingSQL);
$pendingStmt->execute($pendingParams);
$pending_papers = $pendingStmt->fetchAll();

// Fetch completed papers evaluated by this teacher
$completedSQL = "
    SELECT 
        p.id, 
        p.paper_code, 
        p.dummy_token,
        p.subject_name, 
        p.subject_code, 
        p.status, 
        e.total_score, 
        e.external_score, 
        e.internal_marks, 
        e.evaluated_at 
    FROM papers p 
    JOIN evaluations e ON p.id = e.paper_id 
    WHERE p.assigned_teacher_id = ? AND p.status = 'completed'
";
$completedParams = [$teacher_id];
if ($search !== '') {
    $completedSQL .= " AND (p.paper_code LIKE ? OR p.subject_name LIKE ? OR p.subject_code LIKE ?)";
    $completedParams = array_merge($completedParams, [$searchParam, $searchParam, $searchParam]);
}
$completedSQL .= " ORDER BY e.id DESC";
$completedStmt = $pdo->prepare($completedSQL);
$completedStmt->execute($completedParams);
$completed_papers = $completedStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluator Portal - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .flash-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        .flash-success { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; }
        .btn-eval { background: #7C3AED; color: white; }
        .btn-eval:hover { background: #6D28D9; }
        .btn-marksheet { background: #059669; color: white; }
        .btn-marksheet:hover { background: #047857; }
        
        /* Notification Tray */
        .notif-wrapper { position: relative; }
        .notif-btn { background: none; border: none; cursor: pointer; font-size: 1.25rem; position: relative; padding: 6px; border-radius: 8px; }
        .notif-badge { position: absolute; top: 2px; right: 2px; background: #EF4444; color: white; border-radius: 10px; font-size: 0.65rem; font-weight: 800; padding: 1px 5px; }
        .notif-dropdown {
            position: absolute; top: 120%; right: 0; width: 320px; background: white; border: 1px solid #E2E8F0;
            border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: none; z-index: 999;
            max-height: 380px; overflow-y: auto;
        }
        .notif-item { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; font-size: 0.82rem; line-height: 1.4; }
        .notif-item:hover { background: #F8FAFC; }
        .notif-item.unread { background: #EFF6FF; }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <div class="top-navbar-inner">
            <a href="teacher-dashboard.php?tab=dashboard" class="top-brand">
                <div class="top-brand-badge" style="background: linear-gradient(135deg, #10B981, #059669);">E</div>
                <div class="top-brand-text">
                    <h1>DASES</h1>
                    <span>Evaluator Portal</span>
                </div>
            </a>

            <nav class="top-nav-links">
                <a href="teacher-dashboard.php?tab=dashboard" class="top-nav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="teacher-dashboard.php?tab=pending" class="top-nav-link <?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending (<?php echo count($pending_papers); ?>)</a>
                <a href="teacher-dashboard.php?tab=completed" class="top-nav-link <?php echo $tab === 'completed' ? 'active' : ''; ?>">Completed (<?php echo count($completed_papers); ?>)</a>
            </nav>

            <div class="top-nav-actions">
                <!-- Notification Bell -->
                <div class="notif-wrapper">
                    <button type="button" class="notif-btn" onclick="toggleNotifications()" id="notif-bell-btn" title="Notifications" aria-label="Notifications">
                        <i data-lucide="bell" class="icon-md"></i> <span class="notif-badge" id="notif-badge-count" style="display:none;">0</span>
                    </button>
                    <div class="notif-dropdown" id="notif-dropdown-box">
                        <div style="padding: 10px 14px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 0.88rem;">Notifications</strong>
                            <button type="button" onclick="markAllNotificationsRead()" style="background:none; border:none; color:#6366F1; font-size:0.75rem; cursor:pointer; font-weight:700;">Mark all read</button>
                        </div>
                        <div id="notif-list-content">
                            <div style="padding: 16px; text-align: center; color: #94A3B8; font-size: 0.8rem;">Loading notifications...</div>
                        </div>
                    </div>
                </div>

                <!-- Dark Mode Toggle -->
                <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="btn-sm" style="background: #F1F5F9; border: 1px solid #CBD5E1; color: #334155; border-radius: 8px;" title="Toggle Theme" aria-label="Toggle Theme">
                    <i data-lucide="moon" class="icon-sm"></i>
                </button>

                <a href="profile.php" class="user-badge-pill">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="user-role-tag" style="background: #DCFCE7; color: #166534;">Evaluator</span>
                </a>
                <a href="logout.php" style="color: #EF4444; font-size: 0.88rem; font-weight: 700; text-decoration: none; padding: 6px 10px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if ($msg === 'evaluated'): ?>
            <div class="flash-alert flash-success">
                Evaluation submitted successfully! Question marks and on-screen script annotations have been saved.
            </div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
            <div class="dashboard-header">
                <h1>Evaluator Overview</h1>
            </div>
            <div class="metrics-grid" style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
                <div class="card" style="flex: 1; min-width: 220px;">
                    <h3>Pending Evaluation</h3>
                    <div class="number" style="color: #D97706;"><?php echo count($pending_papers); ?></div>
                </div>
                <div class="card" style="flex: 1; min-width: 220px;">
                    <h3>Completed Evaluation</h3>
                    <div class="number" style="color: #059669;"><?php echo count($completed_papers); ?></div>
                </div>
            </div>

            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                <h3 style="color: #1F2937; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="shield-alert" class="icon-md" style="color: var(--accent);"></i> On-Screen Marking Guidelines
                </h3>
                <p style="color: #4B5563; font-size: 0.9rem; line-height: 1.6;">
                    Use the <strong>Digital Studio</strong> to mark answer sheets directly. You can use the red pen marker, $\checkmark$ ticks, $\times$ crosses, and sticky notes on the script. Marks entered in Section A and B are dynamically verified and pass/fail thresholds are updated in real-time.
                </p>
            </div>

        <?php elseif ($tab === 'pending'): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                <h2 style="margin: 0;">Pending Answer Scripts (<?php echo count($pending_papers); ?>)</h2>
                <form method="GET" action="teacher-dashboard.php" style="display: flex; gap: 8px;">
                    <input type="hidden" name="tab" value="pending">
                    <input type="text" name="search" placeholder="Search code, subject..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.88rem;">
                    <button type="submit" class="btn-primary" style="padding: 7px 14px; font-size: 0.88rem;">Search</button>
                    <?php if ($search): ?><a href="teacher-dashboard.php?tab=pending" class="btn-secondary" style="padding: 7px 10px; font-size: 0.88rem; color: #EF4444; display: inline-flex; align-items: center;"><i data-lucide="x" class="icon-xs"></i></a><?php endif; ?>
                </form>
            </div>
            <table class="data-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Paper Code</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_papers) > 0): ?>
                        <?php foreach ($pending_papers as $paper): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($paper['paper_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($paper['subject_name'] ?? ''); ?> (<?php echo htmlspecialchars($paper['subject_code']); ?>)</td>
                                <td><span class="badge pending">Pending Evaluation</span></td>
                                <td>
                                    <a href="teacher-eval.php?paper_id=<?php echo $paper['id']; ?>" class="btn-sm btn-eval">
                                        <i data-lucide="pen-tool" class="icon-xs"></i> Open Evaluation Studio
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 25px; color: #6B7280;">No pending papers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'completed'): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                <h2 style="margin: 0;">Completed Evaluations (<?php echo count($completed_papers); ?>)</h2>
                <form method="GET" action="teacher-dashboard.php" style="display: flex; gap: 8px;">
                    <input type="hidden" name="tab" value="completed">
                    <input type="text" name="search" placeholder="Search code, subject..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.88rem;">
                    <button type="submit" class="btn-primary" style="padding: 7px 14px; font-size: 0.88rem;">Search</button>
                    <?php if ($search): ?><a href="teacher-dashboard.php?tab=completed" class="btn-secondary" style="padding: 7px 10px; font-size: 0.88rem; color: #EF4444;">Clear</a><?php endif; ?>
                </form>
            </div>
            <table class="data-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Paper Code</th>
                        <th>Subject</th>
                        <th>External (80)</th>
                        <th>Internal (20)</th>
                        <th>Grand Total</th>
                        <th>Evaluation Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($completed_papers) > 0): ?>
                        <?php foreach ($completed_papers as $paper): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($paper['paper_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($paper['subject_name'] ?? ''); ?> (<?php echo htmlspecialchars($paper['subject_code']); ?>)</td>
                                <td><?php echo number_format((float)$paper['external_score'], 1); ?> / 80</td>
                                <td><?php echo number_format((float)$paper['internal_marks'], 1); ?> / 20</td>
                                <td>
                                    <?php
                                        $ts = (float)$paper['total_score'];
                                        $passed = $ts >= 35;
                                        echo '<strong style="color:' . ($passed ? '#059669' : '#DC2626') . ';">' . number_format($ts, 1) . ' / 100</strong> ';
                                        if ($passed) {
                                            echo '<span style="display:inline-block; margin-left:4px; padding:2px 8px; border-radius:20px; font-size:0.75rem; font-weight:700; background:#DCFCE7; color:#15803D; border:1px solid #86EFAC;">PASS</span>';
                                        } else {
                                            echo '<span style="display:inline-block; margin-left:4px; padding:2px 8px; border-radius:20px; font-size:0.75rem; font-weight:700; background:#FEE2E2; color:#B91C1C; border:1px solid #FCA5A5;">FAIL</span>';
                                        }
                                    ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($paper['evaluated_at'])); ?></td>
                                <td>
                                    <a href="view-marksheet.php?paper_id=<?php echo $paper['id']; ?>" class="btn-sm btn-marksheet">
                                        <i data-lucide="file-text" class="icon-xs"></i> View Marksheet
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 25px; color: #6B7280;">No evaluated papers yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Notification Scripts -->
    <script>
        function toggleNotifications() {
            const box = document.getElementById('notif-dropdown-box');
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
            if (box.style.display === 'block') {
                fetchNotifications();
            }
        }

        async function fetchNotifications() {
            try {
                const res = await fetch('notifications_api.php?action=fetch');
                const data = await res.json();
                if (data.success) {
                    const badge = document.getElementById('notif-badge-count');
                    if (data.unread_count > 0) {
                        badge.style.display = 'block';
                        badge.innerText = data.unread_count;
                    } else {
                        badge.style.display = 'none';
                    }

                    const list = document.getElementById('notif-list-content');
                    if (data.notifications.length === 0) {
                        list.innerHTML = `<div style="padding: 16px; text-align: center; color: #94A3B8; font-size: 0.8rem;">No notifications.</div>`;
                    } else {
                        list.innerHTML = data.notifications.map(n => `
                            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                                <strong style="color: #0F172A;">${n.title}</strong>
                                <p style="margin: 2px 0 4px 0; color: #475569;">${n.message}</p>
                                <span style="font-size: 0.72rem; color: #94A3B8;">${n.created_at}</span>
                            </div>
                        `).join('');
                    }
                }
            } catch (e) { console.error(e); }
        }

        async function markAllNotificationsRead() {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            await fetch('notifications_api.php', { method: 'POST', body: formData });
            fetchNotifications();
        }

        setInterval(fetchNotifications, 30000);
        window.addEventListener('DOMContentLoaded', () => {
            fetchNotifications();
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>