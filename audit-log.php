<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';
require_once 'audit.php';

// View mode: 'audit' or 'emails'
$view = ($_GET['view'] ?? 'audit') === 'emails' ? 'emails' : 'audit';

// Search & Filter
$search = trim($_GET['search'] ?? '');
$action = trim($_GET['action'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$whereClauses = [];
$params = [];

if ($view === 'audit') {
    if ($search !== '') {
        $whereClauses[] = "(user_name LIKE ? OR details LIKE ? OR ip_address LIKE ?)";
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like]);
    }
    if ($action !== '') {
        $whereClauses[] = "action = ?";
        $params[] = $action;
    }

    $whereSQL = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    $stmt = $pdo->prepare("SELECT * FROM audit_logs $whereSQL ORDER BY id DESC LIMIT 150");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Distinct actions for dropdown
    $actionsStmt = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
    $distinctActions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    // Email logs view
    if ($search !== '') {
        $whereClauses[] = "(recipient_email LIKE ? OR recipient_name LIKE ? OR subject LIKE ? OR body_text LIKE ?)";
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }
    if ($statusFilter !== '') {
        $whereClauses[] = "status = ?";
        $params[] = $statusFilter;
    }

    $whereSQL = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    $stmt = $pdo->prepare("SELECT * FROM email_logs $whereSQL ORDER BY id DESC LIMIT 150");
    $stmt->execute($params);
    $emailLogs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <div class="top-navbar-inner">
            <a href="admin-dashboard.php?tab=dashboard" class="top-brand">
                <div class="top-brand-badge">D</div>
                <div class="top-brand-text">
                    <h1>DASES</h1>
                    <span>Admin Suite</span>
                </div>
            </a>

            <nav class="top-nav-links">
                <a href="admin-dashboard.php?tab=dashboard" class="top-nav-link">Dashboard</a>
                <a href="admin-dashboard.php?tab=upload" class="top-nav-link">Upload Papers</a>
                <a href="admin-dashboard.php?tab=manage" class="top-nav-link">Manage Papers</a>
                <a href="admin-dashboard.php?tab=assign" class="top-nav-link">Assign Evaluator</a>
                <a href="admin-dashboard.php?tab=status" class="top-nav-link">Paper Status</a>
                <a href="audit-log.php" class="top-nav-link active">Audit Log</a>
            </nav>

            <div class="top-nav-actions">
                <!-- Dark Mode Toggle -->
                <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="btn-sm" style="background: #F1F5F9; border: 1px solid #CBD5E1; color: #334155; border-radius: 8px;" title="Toggle Theme" aria-label="Toggle Theme">
                    <i data-lucide="moon" class="icon-sm"></i>
                </button>
                <a href="admin-dashboard.php" class="btn-secondary" style="font-size: 0.84rem; padding: 7px 13px;">Dashboard</a>
                <a href="profile.php" class="user-badge-pill">
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></span>
                    <span class="user-role-tag">Admin</span>
                </a>
                <a href="logout.php" style="color: #EF4444; font-size: 0.88rem; font-weight: 700; text-decoration: none; padding: 6px 10px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="dashboard-header" style="margin-bottom: 20px;">
            <div>
                <h1>System Audit &amp; Dispatch Monitoring</h1>
                <p style="color: #64748B; font-size: 0.88rem; margin-top: 4px;">Track system security events, personnel actions, and outgoing teacher/student notification emails.</p>
            </div>
        </div>

        <!-- View Switcher Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <a href="audit-log.php?view=audit" class="btn-sm <?php echo $view === 'audit' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="shield-check" class="icon-xs"></i> System Audit Logs
            </a>
            <a href="audit-log.php?view=emails" class="btn-sm <?php echo $view === 'emails' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="mail" class="icon-xs"></i> Email &amp; Dispatch Logs
            </a>
        </div>

        <?php if ($view === 'audit'): ?>
            <!-- Audit Filter Bar -->
            <form method="GET" action="audit-log.php" style="background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
                <input type="hidden" name="view" value="audit">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Search Logs</label>
                    <input type="text" name="search" placeholder="Search user, IP, or details..." value="<?php echo htmlspecialchars($search); ?>" style="width:100%; padding:9px 12px; border:1px solid #D1D5DB; border-radius:8px; font-size:0.9rem;">
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Action Type</label>
                    <select name="action" style="width:100%; padding:9px 12px; border:1px solid #D1D5DB; border-radius:8px; font-size:0.9rem;">
                        <option value="">All Actions</option>
                        <?php foreach ($distinctActions as $act): ?>
                            <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action === $act ? 'selected' : ''; ?>><?php echo htmlspecialchars($act); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding: 9px 20px;">Filter</button>
                    <?php if ($search || $action): ?>
                        <a href="audit-log.php?view=audit" style="margin-left: 8px; font-size: 0.85rem; color: #EF4444; text-decoration: none; font-weight: 700;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#<?php echo $log['id']; ?></td>
                                <td style="white-space: nowrap;"><?php echo date('d M Y, h:i:s A', strtotime($log['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong></td>
                                <td><span style="font-size: 0.78rem; font-weight: 700; padding: 2px 8px; border-radius: 12px; background: #F1F5F9; color: #475569; text-transform: uppercase;"><?php echo htmlspecialchars($log['user_role'] ?? 'guest'); ?></span></td>
                                <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                <td style="max-width: 320px; font-size: 0.88rem; color: #475569;"><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                                <td><span style="font-family: monospace; font-size: 0.82rem; color: #64748B;"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 25px; color: #94A3B8;">No audit records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <!-- Email Logs Filter Bar -->
            <form method="GET" action="audit-log.php" style="background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
                <input type="hidden" name="view" value="emails">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Search Emails</label>
                    <input type="text" name="search" placeholder="Search recipient, subject, or content..." value="<?php echo htmlspecialchars($search); ?>" style="width:100%; padding:9px 12px; border:1px solid #D1D5DB; border-radius:8px; font-size:0.9rem;">
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Dispatch Status</label>
                    <select name="status" style="width:100%; padding:9px 12px; border:1px solid #D1D5DB; border-radius:8px; font-size:0.9rem;">
                        <option value="">All Statuses</option>
                        <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent (Mail Server Accepted)</option>
                        <option value="logged" <?php echo $statusFilter === 'logged' ? 'selected' : ''; ?>>Logged (Local Host Dispatched)</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding: 9px 20px;">Filter</button>
                    <?php if ($search || $statusFilter): ?>
                        <a href="audit-log.php?view=emails" style="margin-left: 8px; font-size: 0.85rem; color: #EF4444; text-decoration: none; font-weight: 700;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sent Time</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($emailLogs) > 0): ?>
                        <?php foreach ($emailLogs as $m): ?>
                            <tr>
                                <td>#<?php echo $m['id']; ?></td>
                                <td style="white-space: nowrap;"><?php echo date('d M Y, h:i:s A', strtotime($m['sent_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['recipient_name'] ?: 'User'); ?></strong><br>
                                    <span style="font-size: 0.8rem; color: #64748B;"><?php echo htmlspecialchars($m['recipient_email']); ?></span>
                                </td>
                                <td><strong style="color: #1E293B;"><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                                <td>
                                    <?php if ($m['status'] === 'sent'): ?>
                                        <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:700; background:#DCFCE7; color:#15803D;">DELIVERED</span>
                                    <?php elseif ($m['status'] === 'logged'): ?>
                                        <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:700; background:#E0E7FF; color:#4338CA;" title="<?php echo htmlspecialchars($m['error_message'] ?? 'Dispatched on localhost'); ?>">DISPATCHED</span>
                                    <?php else: ?>
                                        <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:700; background:#FEE2E2; color:#B91C1C;">FAILED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 250px; font-size: 0.82rem; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars(substr($m['body_text'] ?? '', 0, 80)); ?>...
                                </td>
                                <td>
                                    <button type="button" class="btn-sm btn-secondary" onclick="viewEmailModal(<?php echo $m['id']; ?>)">
                                        <i data-lucide="eye" class="icon-xs"></i> View Message
                                    </button>
                                    <div id="email-preview-data-<?php echo $m['id']; ?>" style="display:none;" 
                                         data-subject="<?php echo htmlspecialchars($m['subject']); ?>"
                                         data-recipient="<?php echo htmlspecialchars(($m['recipient_name'] ? $m['recipient_name'] . ' <' : '') . $m['recipient_email'] . ($m['recipient_name'] ? '>' : '')); ?>"
                                         data-date="<?php echo date('d M Y, h:i A', strtotime($m['sent_at'])); ?>">
                                        <?php echo !empty($m['body_html']) ? $m['body_html'] : nl2br(htmlspecialchars($m['body_text'])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 25px; color: #94A3B8;">No email dispatch logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Email View Modal -->
            <div class="modal-backdrop" id="email-viewer-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
                <div style="max-width:700px; width:100%; max-height:90vh; background:#FFFFFF; border-radius:16px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                    <div style="padding:16px 24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; background:#F8FAFC;">
                        <div>
                            <h3 id="modal-email-subject" style="margin:0; font-size:1.1rem; color:#0F172A;">Email Preview</h3>
                            <span id="modal-email-meta" style="font-size:0.8rem; color:#64748B;"></span>
                        </div>
                        <button type="button" onclick="closeEmailModal()" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:#64748B;">&times;</button>
                    </div>
                    <div id="modal-email-body" style="padding:20px; overflow-y:auto; flex:1; background:#F1F5F9;">
                    </div>
                    <div style="padding:12px 24px; border-top:1px solid #E2E8F0; text-align:right; background:#FFFFFF;">
                        <button type="button" onclick="closeEmailModal()" class="btn-secondary" style="padding:7px 16px; border-radius:8px;">Close</button>
                    </div>
                </div>
            </div>

            <script>
                function viewEmailModal(id) {
                    const dataEl = document.getElementById('email-preview-data-' + id);
                    if (!dataEl) return;
                    document.getElementById('modal-email-subject').innerText = dataEl.getAttribute('data-subject');
                    document.getElementById('modal-email-meta').innerText = 'To: ' + dataEl.getAttribute('data-recipient') + ' • ' + dataEl.getAttribute('data-date');
                    document.getElementById('modal-email-body').innerHTML = dataEl.innerHTML;
                    const modal = document.getElementById('email-viewer-modal');
                    modal.style.display = 'flex';
                }
                function closeEmailModal() {
                    document.getElementById('email-viewer-modal').style.display = 'none';
                }
            </script>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>
