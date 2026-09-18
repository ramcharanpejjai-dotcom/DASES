<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';
require_once 'audit.php';

// Handle settings toggles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_setting') {
    $key = $_POST['setting_key'] ?? '';
    $val = $_POST['setting_val'] ?? '0';
    if ($key === 'anonymous_evaluation') {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $val, $val]);
        logAudit("SETTING_CHANGED", "Anonymous Evaluation Mode set to: " . ($val === '1' ? 'ENABLED' : 'DISABLED'));
        header("Location: admin-dashboard.php?tab=" . urlencode($_GET['tab'] ?? 'dashboard') . "&msg=setting_updated");
        exit;
    }
}

// Determine active tab (defaults to 'dashboard')
$tab = $_GET['tab'] ?? 'dashboard';
$msg = $_GET['msg'] ?? '';

// Check current Anonymous Evaluation setting
$anonSettingStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'anonymous_evaluation'");
$isAnonymousActive = ($anonSettingStmt->fetchColumn() === '1');

// Search & Filter params
$search        = trim($_GET['search'] ?? '');
$filterStatus  = $_GET['filter_status'] ?? '';
$filterTeacher = intval($_GET['filter_teacher'] ?? 0);

// Dynamic query for papers
$whereClauses = [];
$queryParams  = [];

if ($search !== '') {
    $whereClauses[] = "(p.student_pin LIKE ? OR p.paper_code LIKE ? OR p.subject_name LIKE ? OR p.subject_code LIKE ? OR p.dummy_token LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $queryParams = array_merge($queryParams, [$likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch]);
}
if ($filterStatus !== '') {
    $whereClauses[] = "p.status = ?";
    $queryParams[] = $filterStatus;
}
if ($filterTeacher > 0) {
    $whereClauses[] = "p.assigned_teacher_id = ?";
    $queryParams[] = $filterTeacher;
}

$whereSQL = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$papersStmt = $pdo->prepare("
    SELECT 
        p.*, 
        u.name as teacher_name,
        e.id as evaluation_id,
        e.external_score,
        e.internal_marks,
        e.total_score,
        e.evaluated_at
    FROM papers p 
    LEFT JOIN users u ON p.assigned_teacher_id = u.id 
    LEFT JOIN evaluations e ON p.id = e.paper_id
    $whereSQL
    ORDER BY p.id DESC
");
$papersStmt->execute($queryParams);
$papers = $papersStmt->fetchAll();

// Fetch teachers
$teachersStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");
$teachers = $teachersStmt->fetchAll();

// Enrolled users statistics (Admins & Teachers) - Strictly for Admin visibility
$teacherCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
$totalTeachersCount = (int)$teacherCountStmt->fetchColumn();

$adminCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$totalAdminsCount = (int)$adminCountStmt->fetchColumn();

$totalUsersCount = $totalTeachersCount + $totalAdminsCount;

// Enrolled users list query with workload stats for 'users' tab
$userRoleFilter = $_GET['user_role'] ?? '';
$userSearch = trim($_GET['user_search'] ?? '');

$userWhere = [];
$userParams = [];

if ($userRoleFilter === 'teacher' || $userRoleFilter === 'admin') {
    $userWhere[] = "u.role = ?";
    $userParams[] = $userRoleFilter;
}
if ($userSearch !== '') {
    $userWhere[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $userLike = '%' . $userSearch . '%';
    $userParams[] = $userLike;
    $userParams[] = $userLike;
}

$userWhereSQL = count($userWhere) > 0 ? "WHERE " . implode(" AND ", $userWhere) : "";

$enrolledUsersStmt = $pdo->prepare("
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        u.role,
        (SELECT COUNT(*) FROM papers p WHERE p.assigned_teacher_id = u.id) as assigned_papers_count,
        (SELECT COUNT(*) FROM papers p JOIN evaluations e ON p.id = e.paper_id WHERE p.assigned_teacher_id = u.id AND p.status = 'completed') as completed_evaluations_count
    FROM users u
    $userWhereSQL
    ORDER BY CASE WHEN u.role = 'admin' THEN 1 ELSE 2 END, u.name ASC
");
$enrolledUsersStmt->execute($userParams);
$enrolledUsersList = $enrolledUsersStmt->fetchAll();

// Statistics calculation
$allPapersStmt = $pdo->query("
    SELECT p.status, e.total_score 
    FROM papers p LEFT JOIN evaluations e ON p.id = e.paper_id
");
$allPapers = $allPapersStmt->fetchAll();

$totalPapers     = count($allPapers);
$completedPapers = 0;
$pendingPapers   = 0;
$totalScoreSum   = 0;
$passCount       = 0;
$failCount       = 0;
$scoreRanges     = [0, 0, 0, 0, 0];

foreach ($allPapers as $p) {
    if ($p['status'] === 'completed') {
        $completedPapers++;
        $score = (float)($p['total_score'] ?? 0);
        $totalScoreSum += $score;
        if ($score >= 35) { $passCount++; } else { $failCount++; }
        if      ($score <= 20) $scoreRanges[0]++;
        elseif  ($score <= 40) $scoreRanges[1]++;
        elseif  ($score <= 60) $scoreRanges[2]++;
        elseif  ($score <= 80) $scoreRanges[3]++;
        else                   $scoreRanges[4]++;
    } else {
        $pendingPapers++;
    }
}
$avgScore = $completedPapers > 0 ? round($totalScoreSum / $completedPapers, 1) : 0;

// Fetch Re-Evaluations list
$reevalsStmt = $pdo->query("
    SELECT r.*, p.paper_code, p.subject_name, u.name as current_teacher_name 
    FROM reevaluations r 
    JOIN papers p ON r.paper_id = p.id 
    LEFT JOIN users u ON p.assigned_teacher_id = u.id 
    ORDER BY r.id DESC
");
$reevaluationsList = $reevalsStmt->fetchAll();
$pendingReevalsCount = 0;
foreach ($reevaluationsList as $rv) {
    if ($rv['status'] === 'pending') $pendingReevalsCount++;
}

// Fetch Moderation / Borderline papers (Score 30 to 37)
$moderationStmt = $pdo->query("
    SELECT p.*, u.name as teacher_name, e.total_score, e.external_score, e.internal_marks, e.evaluated_at 
    FROM papers p 
    JOIN evaluations e ON p.id = e.paper_id 
    LEFT JOIN users u ON p.assigned_teacher_id = u.id 
    WHERE p.status = 'completed' AND e.total_score BETWEEN 30 AND 37 
    ORDER BY e.total_score ASC
");
$moderationList = $moderationStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - DASES Enterprise Suite</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="js/curriculum.js?v=1.0"></script>
    <style>
        .action-btn-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; border: none; cursor: pointer; }
        .btn-view { background: #3B82F6; color: white; }
        .btn-view:hover { background: #2563EB; }
        .btn-marksheet { background: #10B981; color: white; }
        .btn-marksheet:hover { background: #059669; }
        .btn-delete { background: #EF4444; color: white; }
        .btn-delete:hover { background: #DC2626; }
        .btn-reassign { background: #F59E0B; color: white; }
        .btn-reassign:hover { background: #D97706; }
        .flash-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        .flash-success { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
        .flash-danger { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .pin-tag { background: #EFF6FF; color: #1D4ED8; font-weight: 700; padding: 2px 6px; border-radius: 4px; border: 1px solid #BFDBFE; font-family: monospace; }
        .tab-badge { background: #EF4444; color: white; border-radius: 12px; padding: 1px 7px; font-size: 0.72rem; font-weight: 800; margin-left: 4px; }
        
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

        /* Modal */
        .modal-backdrop {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        }
        .modal-box { background: white; border-radius: 14px; max-width: 500px; width: 100%; padding: 24px; }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <div class="top-navbar-inner">
            <a href="admin-dashboard.php?tab=dashboard" class="top-brand">
                <div class="top-brand-badge">D</div>
                <div class="top-brand-text">
                    <h1>DASES</h1>
                    <span>Enterprise Suite</span>
                </div>
            </a>

            <nav class="top-nav-links">
                <a href="admin-dashboard.php?tab=dashboard" class="top-nav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="admin-dashboard.php?tab=upload" class="top-nav-link <?php echo $tab === 'upload' ? 'active' : ''; ?>">Upload</a>
                <a href="admin-dashboard.php?tab=manage" class="top-nav-link <?php echo $tab === 'manage' ? 'active' : ''; ?>">Papers</a>
                <a href="admin-dashboard.php?tab=assign" class="top-nav-link <?php echo $tab === 'assign' ? 'active' : ''; ?>">Assign</a>
                <a href="admin-dashboard.php?tab=status" class="top-nav-link <?php echo $tab === 'status' ? 'active' : ''; ?>">Status</a>
                <a href="admin-dashboard.php?tab=reevals" class="top-nav-link <?php echo $tab === 'reevals' ? 'active' : ''; ?>">
                    Re-evaluations <?php if ($pendingReevalsCount > 0): ?><span class="tab-badge"><?php echo $pendingReevalsCount; ?></span><?php endif; ?>
                </a>
                <a href="admin-dashboard.php?tab=moderation" class="top-nav-link <?php echo $tab === 'moderation' ? 'active' : ''; ?>">Moderation Queue</a>
                <a href="admin-dashboard.php?tab=users" class="top-nav-link <?php echo $tab === 'users' ? 'active' : ''; ?>">
                    Enrolled Users <span style="background: rgba(99, 102, 241, 0.15); color: #4F46E5; border-radius: 12px; padding: 1px 7px; font-size: 0.72rem; font-weight: 800; margin-left: 4px;"><?php echo $totalUsersCount; ?></span>
                </a>
                <a href="audit-log.php" class="top-nav-link">Audit Log</a>
            </nav>

            <div class="top-nav-actions">
                <!-- Anonymous Evaluation Switch -->
                <form method="POST" style="margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                    <input type="hidden" name="action" value="toggle_setting">
                    <input type="hidden" name="setting_key" value="anonymous_evaluation">
                    <input type="hidden" name="setting_val" value="<?php echo $isAnonymousActive ? '0' : '1'; ?>">
                    <button type="submit" class="btn-sm" style="background: <?php echo $isAnonymousActive ? '#4F46E5' : '#E2E8F0'; ?>; color: <?php echo $isAnonymousActive ? '#FFF' : '#475569'; ?>; border-radius: 20px; font-weight: 700;" title="Toggle double-blind anonymous evaluation mask">
                        <i data-lucide="shield" class="icon-xs"></i> Blind Mask: <?php echo $isAnonymousActive ? 'ON' : 'OFF'; ?>
                    </button>
                </form>

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

                <a href="export_csv.php" class="btn-primary" style="background: #059669; font-size: 0.84rem; padding: 7px 13px;">Export CSV</a>
                <a href="profile.php" class="user-badge-pill">
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></span>
                    <span class="user-role-tag">Admin</span>
                </a>
                <a href="logout.php" style="color: #EF4444; font-size: 0.88rem; font-weight: 700; text-decoration: none; padding: 6px 10px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if ($msg === 'uploaded'): ?>
            <div class="flash-alert flash-success">Paper script uploaded successfully! Generated unique paper code and stored student PIN.</div>
        <?php elseif ($msg === 'bulk_uploaded'): ?>
            <div class="flash-alert flash-success">Batch upload complete! Successfully processed <strong><?php echo intval($_GET['count'] ?? 0); ?></strong> answer script(s).</div>
        <?php elseif ($msg === 'assigned'): ?>
            <div class="flash-alert flash-success">Evaluator successfully assigned to paper.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="flash-alert flash-danger">Paper and associated evaluation records deleted.</div>
        <?php elseif ($msg === 'setting_updated'): ?>
            <div class="flash-alert flash-success">System settings updated successfully!</div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
            <div class="dashboard-header">
                <h1>System Overview &amp; Analytics</h1>
            </div>
            <div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="card">
                    <h3>Total Papers</h3>
                    <div class="number"><?php echo $totalPapers; ?></div>
                </div>
                <div class="card">
                    <h3>Pending Evaluation</h3>
                    <div class="number" style="color: #D97706;"><?php echo $pendingPapers; ?></div>
                </div>
                <div class="card">
                    <h3>Completed</h3>
                    <div class="number" style="color: #059669;"><?php echo $completedPapers; ?></div>
                </div>
                <div class="card">
                    <h3>Average Score</h3>
                    <div class="number" style="color: #4F46E5;"><?php echo $avgScore; ?> <span style="font-size: 1rem; color: var(--text-muted);">/ 100</span></div>
                </div>
                <div class="card">
                    <h3>Registered Evaluators</h3>
                    <div class="number" style="color: #2563EB;"><?php echo count($teachers); ?></div>
                </div>
                <div class="card">
                    <h3>Pass Rate</h3>
                    <div class="number" style="color: #10B981;">
                        <?php echo $completedPapers > 0 ? round(($passCount / $completedPapers) * 100) : 0; ?>%
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 30px;">
                <div class="card" style="padding: 24px; border-radius: 14px;">
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px;">Pass / Fail Distribution</h3>
                    <?php if ($completedPapers > 0): ?>
                        <canvas id="passFailChart" height="220"></canvas>
                    <?php else: ?>
                        <p style="color: var(--text-muted); text-align:center; padding:40px 0;">No completed evaluations yet.</p>
                    <?php endif; ?>
                </div>

                <div class="card" style="padding: 24px; border-radius: 14px;">
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px;">Score Distribution</h3>
                    <?php if ($completedPapers > 0): ?>
                        <canvas id="scoreDistChart" height="220"></canvas>
                    <?php else: ?>
                        <p style="color: var(--text-muted); text-align:center; padding:40px 0;">No completed evaluations yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-top: 28px; padding: 22px; border-radius: 12px;">
                <h3 style="margin-bottom: 10px;">Double-Blind Evaluation Security Policy</h3>
                <p style="font-size: 0.9rem; line-height: 1.6;">
                    When <strong>Blind Mask Mode</strong> is enabled, all candidate roll numbers are hashed into anonymous tokens (e.g. <code>ANON-7A8B9C</code>) on the evaluator screens, completely eliminating evaluator bias.
                </p>
            </div>

            <script>
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
            <?php if ($completedPapers > 0): ?>
            new Chart(document.getElementById('passFailChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Pass (≥35)', 'Fail (<35)'],
                    datasets: [{
                        data: [<?php echo $passCount; ?>, <?php echo $failCount; ?>],
                        backgroundColor: ['#10B981', '#EF4444'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 6
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
            });

            new Chart(document.getElementById('scoreDistChart'), {
                type: 'bar',
                data: {
                    labels: ['0–20', '21–40', '41–60', '61–80', '81–100'],
                    datasets: [{
                        label: 'Students',
                        data: <?php echo json_encode($scoreRanges); ?>,
                        backgroundColor: ['#EF4444','#F97316','#FBBF24','#34D399','#6366F1'],
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
            <?php endif; ?>
            </script>

        <?php elseif ($tab === 'upload'): ?>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button type="button" id="btnSingleMode" class="btn-primary" onclick="setUploadMode('single')" style="font-size: 0.9rem; padding: 9px 18px;">Single Script Upload</button>
                <button type="button" id="btnBulkMode" class="btn-secondary" onclick="setUploadMode('bulk')" style="font-size: 0.9rem; padding: 9px 18px;">Bulk / Batch Upload</button>
            </div>

            <div class="form-card" id="singleUploadForm">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem;">Upload Single Answer Script</h2>
                        <p style="margin: 3px 0 0; font-size: 0.82rem; color: #64748B;">Assign student script with C-23 diploma curriculum auto-tagging.</p>
                    </div>
                    <span class="badge" style="background: #EEF2FF; color: #4F46E5; font-weight: 700; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px;">
                        <i data-lucide="book-open" class="icon-xs"></i> C-23 SBTET AP
                    </span>
                </div>

                <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Student PIN / Roll Number <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="student_pin" id="single_student_pin" placeholder="e.g. 23001-CM-042" required autofocus style="font-family: monospace; font-weight: 600;">
                    </div>

                    <!-- Smart Curriculum Selectors -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 700; color: #1E293B;">Branch / Diploma Stream <span style="color:#EF4444;">*</span></label>
                            <select id="single_branch" onchange="onCurriculumFilterChange('single')" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card, #fff); color: var(--text-main, #0F172A); font-weight: 600;">
                                <option value="CME" selected>Computer Engineering (CME)</option>
                                <option value="ECE">Electronics & Communication (ECE)</option>
                                <option value="ME">Mechanical Engineering (ME)</option>
                                <option value="EEE">Electrical & Electronics (EEE)</option>
                                <option value="CE">Civil Engineering (CE)</option>
                                <option value="custom">Other / Custom Branch</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 700; color: #1E293B;">Semester <span style="color:#EF4444;">*</span></label>
                            <select name="semester" id="single_semester" onchange="onCurriculumFilterChange('single')" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card, #fff); color: var(--text-main, #0F172A); font-weight: 600;">
                                <option value="Sem-1" selected>Sem-1 (Combined 1st & 2nd Sem)</option>
                                <option value="Sem-3">Sem-3</option>
                                <option value="Sem-4">Sem-4</option>
                                <option value="Sem-5">Sem-5</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Subject Picker -->
                    <div class="form-group" style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px 14px; border-radius: 10px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            <span style="display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="sparkles" class="icon-xs" style="color: #4F46E5;"></i> Select Subject from C-23 Curriculum:
                            </span>
                            <span style="font-size: 0.75rem; color: #64748B; font-weight: 500;">Auto-fills Name & Code</span>
                        </label>
                        <select id="single_subject_picker" onchange="onSubjectPickerChange('single')" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #CBD5E1; background: white; font-size: 0.88rem; color: #0F172A; font-weight: 500;">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Subject Name <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="subject_name" id="single_subject_name" placeholder="e.g. Data Structures Through C" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Code <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="subject_code" id="single_subject_code" placeholder="e.g. CM-304" required style="font-weight: 700; font-family: monospace;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Choose Answer Script PDF <span style="color:#EF4444;">*</span></label>
                        <input type="file" name="pdf_script" accept=".pdf,application/pdf" required>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn-success">Upload Script</button>
                        <button type="reset" class="btn-secondary" onclick="setTimeout(() => onCurriculumFilterChange('single'), 50);">Reset</button>
                    </div>
                </form>
            </div>

            <div class="form-card" id="bulkUploadForm" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem;">Batch / Bulk Script Upload</h2>
                        <p style="margin: 3px 0 0; font-size: 0.82rem; color: #64748B;">Upload multiple answer sheets for a single exam paper with C-23 subject mapping.</p>
                    </div>
                    <span class="badge" style="background: #EEF2FF; color: #4F46E5; font-weight: 700; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px;">
                        <i data-lucide="layers" class="icon-xs"></i> Batch Mode
                    </span>
                </div>

                <form action="bulk_upload.php" method="POST" enctype="multipart/form-data">
                    <!-- Smart Curriculum Selectors -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 700; color: #1E293B;">Branch / Diploma Stream <span style="color:#EF4444;">*</span></label>
                            <select id="bulk_branch" onchange="onCurriculumFilterChange('bulk')" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card, #fff); color: var(--text-main, #0F172A); font-weight: 600;">
                                <option value="CME" selected>Computer Engineering (CME)</option>
                                <option value="ECE">Electronics & Communication (ECE)</option>
                                <option value="ME">Mechanical Engineering (ME)</option>
                                <option value="EEE">Electrical & Electronics (EEE)</option>
                                <option value="CE">Civil Engineering (CE)</option>
                                <option value="custom">Other / Custom Branch</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 700; color: #1E293B;">Semester <span style="color:#EF4444;">*</span></label>
                            <select name="semester" id="bulk_semester" onchange="onCurriculumFilterChange('bulk')" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card, #fff); color: var(--text-main, #0F172A); font-weight: 600;">
                                <option value="Sem-1" selected>Sem-1 (Combined 1st & 2nd Sem)</option>
                                <option value="Sem-3">Sem-3</option>
                                <option value="Sem-4">Sem-4</option>
                                <option value="Sem-5">Sem-5</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Subject Picker -->
                    <div class="form-group" style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px 14px; border-radius: 10px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            <span style="display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="sparkles" class="icon-xs" style="color: #4F46E5;"></i> Select Subject from C-23 Curriculum:
                            </span>
                            <span style="font-size: 0.75rem; color: #64748B; font-weight: 500;">Auto-fills Name & Code</span>
                        </label>
                        <select id="bulk_subject_picker" onchange="onSubjectPickerChange('bulk')" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #CBD5E1; background: white; font-size: 0.88rem; color: #0F172A; font-weight: 500;">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Subject Name <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="subject_name" id="bulk_subject_name" placeholder="e.g. Operating Systems" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Code <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="subject_code" id="bulk_subject_code" placeholder="e.g. CM-303" required style="font-weight: 700; font-family: monospace;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Select Multiple PDF Answer Scripts <span style="color:#EF4444;">*</span></label>
                        <input type="file" name="pdf_files[]" accept=".pdf,application/pdf" multiple required>
                    </div>
                    <div class="form-group">
                        <label>Student PINs List (Optional — 1 per line)</label>
                        <textarea name="pins_text" id="bulk_pins_text" rows="4" placeholder="23001-CM-001&#10;23001-CM-002" style="font-family: monospace;"></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Start Batch Upload</button>
                </form>
            </div>

            <script>
            function setUploadMode(mode) {
                document.getElementById('singleUploadForm').style.display = (mode === 'single') ? 'block' : 'none';
                document.getElementById('bulkUploadForm').style.display = (mode === 'bulk') ? 'block' : 'none';
                document.getElementById('btnSingleMode').className = (mode === 'single') ? 'btn-primary' : 'btn-secondary';
                document.getElementById('btnBulkMode').className = (mode === 'bulk') ? 'btn-primary' : 'btn-secondary';
            }

            function onCurriculumFilterChange(prefix) {
                const branchElem = document.getElementById(prefix + '_branch');
                const semElem = document.getElementById(prefix + '_semester');
                const pickerElem = document.getElementById(prefix + '_subject_picker');
                const pinElem = document.getElementById(prefix + '_student_pin');
                const nameElem = document.getElementById(prefix + '_subject_name');
                const codeElem = document.getElementById(prefix + '_subject_code');

                if (!branchElem || !semElem || !pickerElem) return;
                const branch = branchElem.value;
                const sem = semElem.value;

                updateSubjectOptions(branch, sem, pickerElem);

                // Update PIN placeholder hint based on branch
                if (pinElem && branch !== 'custom' && typeof C23_CURRICULUM !== 'undefined' && C23_CURRICULUM[branch]) {
                    pinElem.placeholder = `e.g. 23001-${C23_CURRICULUM[branch].prefix}-042`;
                }

                // If user changed branch/semester, clear previous selection or auto-select first subject
                if (pickerElem.options.length > 1) {
                    pickerElem.selectedIndex = 1;
                    onSubjectSelected(pickerElem, nameElem, codeElem);
                }
            }

            function onSubjectPickerChange(prefix) {
                const pickerElem = document.getElementById(prefix + '_subject_picker');
                const nameElem = document.getElementById(prefix + '_subject_name');
                const codeElem = document.getElementById(prefix + '_subject_code');
                onSubjectSelected(pickerElem, nameElem, codeElem);
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof updateSubjectOptions === 'function') {
                    onCurriculumFilterChange('single');
                    onCurriculumFilterChange('bulk');
                }
                if (window.lucide) lucide.createIcons();
            });
            </script>

        <?php elseif ($tab === 'manage'): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0;">Manage Uploaded Papers</h2>
                <span style="font-size: 0.88rem; color: #6B7280;">Showing <strong><?php echo count($papers); ?></strong> result(s)</span>
            </div>

            <!-- Search & Filter Bar -->
            <form method="GET" action="admin-dashboard.php" style="background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px;">
                <input type="hidden" name="tab" value="manage">
                <div style="flex: 2; min-width: 200px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Search</label>
                    <input type="text" name="search" placeholder="PIN, paper code, subject..." value="<?php echo htmlspecialchars($search); ?>" style="width:100%; padding:8px 12px; border:1px solid #D1D5DB; border-radius:8px;">
                </div>
                <div style="flex: 1; min-width: 140px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Status</label>
                    <select name="filter_status" style="width:100%; padding:8px 12px; border:1px solid #D1D5DB; border-radius:8px;">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding: 8px 18px;">Apply</button>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Candidate PIN</th>
                        <th>Masked Token</th>
                        <th>Paper Code</th>
                        <th>Semester</th>
                        <th>Subject</th>
                        <th>Assigned Evaluator</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($papers) > 0): ?>
                        <?php foreach ($papers as $paper): ?>
                            <tr>
                                <td><span class="pin-tag"><?php echo htmlspecialchars($paper['student_pin'] ?? 'N/A'); ?></span></td>
                                <td><code style="font-size:0.85rem; color:#6366F1;"><?php echo htmlspecialchars($paper['dummy_token'] ?? '-'); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($paper['paper_code']); ?></strong></td>
                                <td><span class="badge" style="background: rgba(99, 102, 241, 0.12); color: #4F46E5; font-weight: 700;"><?php echo htmlspecialchars($paper['semester'] ?? 'Sem-1'); ?></span></td>
                                <td><?php echo htmlspecialchars($paper['subject_name'] ?? ''); ?> (<?php echo htmlspecialchars($paper['subject_code']); ?>)</td>
                                <td><?php echo htmlspecialchars($paper['teacher_name'] ?? 'Unassigned'); ?></td>
                                <td><span class="badge <?php echo htmlspecialchars($paper['status']); ?>"><?php echo ucfirst(htmlspecialchars($paper['status'])); ?></span></td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" target="_blank" class="btn-sm btn-view"><i data-lucide="eye" class="icon-xs"></i> PDF</a>
                                        <?php if ($paper['status'] === 'completed'): ?>
                                            <a href="view-marksheet.php?paper_id=<?php echo $paper['id']; ?>" class="btn-sm btn-marksheet"><i data-lucide="file-text" class="icon-xs"></i> Marks</a>
                                        <?php endif; ?>
                                        <a href="delete_paper.php?id=<?php echo $paper['id']; ?>" onclick="return confirm('Delete paper <?php echo htmlspecialchars($paper['paper_code']); ?>?');" class="btn-sm btn-delete"><i data-lucide="trash-2" class="icon-xs"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">No papers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'status'): ?>
            <h2>Paper Status & Grading Tracker</h2>
            <table class="data-table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Candidate PIN</th>
                        <th>Paper Code</th>
                        <th>Subject</th>
                        <th>Evaluator</th>
                        <th>Status</th>
                        <th>External (80)</th>
                        <th>Internal (20)</th>
                        <th>Total (100)</th>
                        <th>Date</th>
                        <th>Marksheet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($papers) > 0): ?>
                        <?php foreach ($papers as $paper): ?>
                            <tr>
                                <td><span class="pin-tag"><?php echo htmlspecialchars($paper['student_pin'] ?? 'N/A'); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($paper['paper_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($paper['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($paper['teacher_name'] ?? 'Unassigned'); ?></td>
                                <td><span class="badge <?php echo htmlspecialchars($paper['status']); ?>"><?php echo ucfirst(htmlspecialchars($paper['status'])); ?></span></td>
                                <td><?php echo isset($paper['external_score']) ? number_format((float)$paper['external_score'], 1) : '-'; ?></td>
                                <td><?php echo isset($paper['internal_marks']) ? number_format((float)$paper['internal_marks'], 1) : '-'; ?></td>
                                <td>
                                    <?php
                                        if (isset($paper['total_score'])) {
                                            $ts = (float)$paper['total_score'];
                                            echo '<strong>' . number_format($ts, 1) . '</strong> ';
                                            echo ($ts >= 35) ? '<span style="color:#166534; font-weight:bold;">[PASS]</span>' : '<span style="color:#991B1B; font-weight:bold;">[FAIL]</span>';
                                        } else { echo '-'; }
                                    ?>
                                </td>
                                <td><?php echo !empty($paper['evaluated_at']) ? date('d M Y', strtotime($paper['evaluated_at'])) : '-'; ?></td>
                                <td>
                                    <?php if ($paper['status'] === 'completed'): ?>
                                        <a href="view-marksheet.php?paper_id=<?php echo $paper['id']; ?>" class="btn-sm btn-marksheet">View</a>
                                    <?php else: ?>
                                        <span style="color:#9CA3AF;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align: center; padding: 20px;">No papers available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'assign'): ?>
            <h2>Assign or Reassign Papers to Evaluators</h2>
            <div class="form-card" style="margin-top: 20px;">
                <form action="assign_handler.php" method="POST">
                    <input type="hidden" name="redirect_tab" value="assign">
                    <div class="form-group">
                        <label>Select Paper</label>
                        <select name="paper_id" required>
                            <?php foreach ($papers as $paper): ?>
                                <option value="<?php echo $paper['id']; ?>">
                                    <?php echo htmlspecialchars($paper['paper_code'] . ' - ' . ($paper['subject_name'] ?? '') . ' (' . $paper['subject_code'] . ') ' . ($paper['teacher_name'] ? ' [Currently: ' . $paper['teacher_name'] . ']' : ' [Unassigned]')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Evaluator</label>
                        <select name="teacher_id" required>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Assign / Reassign Evaluator</button>
                </form>
            </div>

        <?php elseif ($tab === 'reevals'): ?>
            <!-- Re-Evaluation Management Tab -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0;">Student Re-Evaluation Grievance Requests</h2>
                    <p style="color: #6B7280; font-size: 0.85rem; margin: 4px 0 0 0;">Review student applications, assign secondary evaluators, or mark resolved.</p>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Student PIN</th>
                        <th>Paper Code</th>
                        <th>Subject</th>
                        <th>Reason for Grievance</th>
                        <th>Status</th>
                        <th>Filed On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reevaluationsList) > 0): ?>
                        <?php foreach ($reevaluationsList as $rv): ?>
                            <tr>
                                <td><strong>#REEV-<?php echo $rv['id']; ?></strong></td>
                                <td><span class="pin-tag"><?php echo htmlspecialchars($rv['student_pin']); ?></span></td>
                                <td><?php echo htmlspecialchars($rv['paper_code']); ?></td>
                                <td><?php echo htmlspecialchars($rv['subject_code']); ?></td>
                                <td style="max-width: 250px; font-size: 0.82rem; color: #374151;"><em>"<?php echo htmlspecialchars($rv['reason']); ?>"</em></td>
                                <td>
                                    <?php if ($rv['status'] === 'pending'): ?>
                                        <span class="badge uploaded">Pending</span>
                                    <?php elseif ($rv['status'] === 'approved'): ?>
                                        <span class="badge assigned">Re-Evaluating</span>
                                    <?php elseif ($rv['status'] === 'completed'): ?>
                                        <span class="badge completed">Completed</span>
                                    <?php else: ?>
                                        <span style="color:#DC2626; font-weight:bold; font-size:0.8rem;">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($rv['created_at'])); ?></td>
                                <td>
                                    <?php if ($rv['status'] === 'pending'): ?>
                                        <button type="button" onclick="openReevalActionModal(<?php echo $rv['id']; ?>, '<?php echo htmlspecialchars($rv['student_pin']); ?>', '<?php echo htmlspecialchars($rv['subject_code']); ?>')" class="btn-sm btn-reassign">
                                            <i data-lucide="sliders" class="icon-xs"></i> Review Request
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#64748B; font-size:0.8rem;">Reviewed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; padding: 20px;">No re-evaluation requests submitted.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'moderation'): ?>
            <!-- Moderation / Borderline Queue Tab -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0;">Chief Examiner Moderation Queue</h2>
                    <p style="color: #6B7280; font-size: 0.85rem; margin: 4px 0 0 0;">Papers scoring in the borderline pass/fail range (30–37 marks) flagged for second-tier quality validation.</p>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paper Code</th>
                        <th>Student PIN</th>
                        <th>Subject</th>
                        <th>Evaluated By</th>
                        <th>External Score</th>
                        <th>Internal Marks</th>
                        <th>Total Score</th>
                        <th>Moderation Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($moderationList) > 0): ?>
                        <?php foreach ($moderationList as $mod): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($mod['paper_code']); ?></strong></td>
                                <td><span class="pin-tag"><?php echo htmlspecialchars($mod['student_pin']); ?></span></td>
                                <td><?php echo htmlspecialchars($mod['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($mod['teacher_name'] ?? 'Evaluator'); ?></td>
                                <td><?php echo number_format((float)$mod['external_score'], 1); ?> / 80</td>
                                <td><?php echo number_format((float)$mod['internal_marks'], 1); ?> / 20</td>
                                <td><strong style="color: #D97706;"><?php echo number_format((float)$mod['total_score'], 1); ?> / 100</strong></td>
                                <td>
                                    <?php if ($mod['is_moderated']): ?>
                                        <span class="badge completed">Moderated</span>
                                    <?php else: ?>
                                        <span style="color:#D97706; font-weight:bold; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="alert-triangle" class="icon-xs"></i> Borderline Review</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="view-marksheet.php?paper_id=<?php echo $mod['id']; ?>" class="btn-sm btn-view">Review Marksheet</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align: center; padding: 20px;">No papers currently require borderline moderation.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($tab === 'users'): ?>
            <!-- Enrolled Users Directory Tab (Admin Only) -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="users" class="icon-md" style="color: var(--accent);"></i> Enrolled Personnel Directory
                        <span style="background: #EEF2FF; color: #4F46E5; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 12px;">ADMINISTRATOR ACCESS ONLY</span>
                    </h2>
                    <p style="color: #6B7280; font-size: 0.85rem; margin: 4px 0 0 0;">Complete directory of all registered teachers, evaluators, and system administrators.</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="register.php" target="_blank" class="btn-sm btn-view" style="padding: 8px 14px; font-weight: 600; text-decoration: none;">
                        + Register New Account
                    </a>
                </div>
            </div>

            <!-- Enrollment KPI Summary Cards -->
            <div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div class="card" style="border-left: 4px solid #6366F1;">
                    <h3>Total Enrolled Staff</h3>
                    <div class="number" style="color: #4F46E5;"><?php echo $totalUsersCount; ?></div>
                    <span style="font-size: 0.78rem; color: #64748B;">Combined Teachers &amp; Admins</span>
                </div>
                <div class="card" style="border-left: 4px solid #10B981;">
                    <h3>Enrolled Teachers / Evaluators</h3>
                    <div class="number" style="color: #059669;"><?php echo $totalTeachersCount; ?></div>
                    <span style="font-size: 0.78rem; color: #64748B;">Faculty authorized for evaluation</span>
                </div>
                <div class="card" style="border-left: 4px solid #8B5CF6;">
                    <h3>Enrolled Administrators</h3>
                    <div class="number" style="color: #7C3AED;"><?php echo $totalAdminsCount; ?></div>
                    <span style="font-size: 0.78rem; color: #64748B;">Full administrative root authority</span>
                </div>
            </div>

            <!-- Filter and Search Toolbar -->
            <form method="GET" action="admin-dashboard.php" style="background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px;">
                <input type="hidden" name="tab" value="users">
                <div style="flex: 2; min-width: 220px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Search Staff</label>
                    <input type="text" name="user_search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($userSearch); ?>" style="width:100%; padding:8px 12px; border:1px solid #D1D5DB; border-radius:8px;">
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:#374151; margin-bottom:4px;">Filter by Role</label>
                    <select name="user_role" style="width:100%; padding:8px 12px; border:1px solid #D1D5DB; border-radius:8px;">
                        <option value="">All Roles (<?php echo $totalUsersCount; ?>)</option>
                        <option value="teacher" <?php echo $userRoleFilter === 'teacher' ? 'selected' : ''; ?>>Teachers / Evaluators (<?php echo $totalTeachersCount; ?>)</option>
                        <option value="admin" <?php echo $userRoleFilter === 'admin' ? 'selected' : ''; ?>>Administrators (<?php echo $totalAdminsCount; ?>)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding: 8px 18px;">Apply Filter</button>
                    <?php if ($userSearch !== '' || $userRoleFilter !== ''): ?>
                        <a href="admin-dashboard.php?tab=users" class="btn-sm" style="background: #F1F5F9; color: #475569; padding: 8px 12px; text-decoration: none; border: 1px solid #CBD5E1; margin-left: 6px;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Users Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>User / Full Name</th>
                        <th>Email Address</th>
                        <th>Role &amp; Privileges</th>
                        <th>Workload / Activity</th>
                        <th>Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($enrolledUsersList) > 0): ?>
                        <?php foreach ($enrolledUsersList as $u): ?>
                            <tr>
                                <td><span style="color:#64748B; font-weight:600; font-size:0.85rem;">#<?php echo $u['id']; ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo $u['role'] === 'admin' ? '#EDE9FE' : '#DCFCE7'; ?>; color: <?php echo $u['role'] === 'admin' ? '#6D28D9' : '#15803D'; ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.88rem; flex-shrink: 0;">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong style="color: #0F172A; font-size: 0.92rem;"><?php echo htmlspecialchars($u['name']); ?></strong>
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                <span style="background: #FEF3C7; color: #92400E; font-size: 0.7rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; margin-left: 4px;">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #475569; font-size: 0.88rem;"><?php echo htmlspecialchars($u['email']); ?></span>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span style="background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="shield-check" class="icon-xs"></i> Administrator
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="graduation-cap" class="icon-xs"></i> Teacher / Evaluator
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'teacher'): ?>
                                        <span style="font-size: 0.85rem; color: #334155;">
                                            <strong><?php echo intval($u['assigned_papers_count']); ?></strong> assigned &bull; 
                                            <strong style="color:#059669;"><?php echo intval($u['completed_evaluations_count']); ?></strong> completed
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 0.82rem; color: #64748B;">Root Governance &amp; Config</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge completed" style="font-size: 0.75rem; padding: 3px 8px;">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #94A3B8;">
                                No enrolled personnel found matching your filter criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Admin Re-eval Action Modal -->
    <div class="modal-backdrop" id="reeval-admin-modal">
        <div class="modal-box">
            <h3 style="font-size: 1.15rem; margin-top: 0; color: var(--text-main, #0F172A);">Action on Re-Evaluation Request</h3>
            <p id="admin-reeval-label" style="font-size: 0.85rem; color: var(--text-muted, #64748B); margin-bottom: 16px;"></p>

            <form id="admin-reeval-form" onsubmit="handleAdminReevalSubmit(event)">
                <input type="hidden" name="reeval_id" id="admin_modal_reeval_id">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Decision Action</label>
                    <select name="action" id="admin_reeval_action_select" onchange="toggleTeacherSelect(this.value)" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 6px;">
                        <option value="approve_reassign">Approve & Reassign to Secondary Evaluator</option>
                        <option value="reject">Reject Grievance Request</option>
                    </select>
                </div>

                <div class="form-group" id="teacher-select-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Assign To Evaluator</label>
                    <select name="new_teacher_id" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 6px;">
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="font-size: 0.85rem; font-weight: 700; display: block; margin-bottom: 4px;">Admin Remarks / Note</label>
                    <textarea name="admin_notes" rows="2" placeholder="Optional notes regarding decision..." style="width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; font-family: inherit; font-size: 0.85rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeAdminReevalModal()" class="btn-secondary" style="padding: 6px 12px; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 6px 14px; border-radius: 6px; cursor: pointer;">Apply Decision</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification and Dark Mode Scripts -->
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

        function openReevalActionModal(id, pin, subj) {
            document.getElementById('admin_modal_reeval_id').value = id;
            document.getElementById('admin-reeval-label').innerText = `Grievance #${id} for Roll No: ${pin} (${subj})`;
            document.getElementById('reeval-admin-modal').style.display = 'flex';
        }

        function closeAdminReevalModal() {
            document.getElementById('reeval-admin-modal').style.display = 'none';
        }

        function toggleTeacherSelect(val) {
            document.getElementById('teacher-select-group').style.display = (val === 'approve_reassign') ? 'block' : 'none';
        }

        async function handleAdminReevalSubmit(e) {
            e.preventDefault();
            const form = document.getElementById('admin-reeval-form');
            const formData = new FormData(form);
            try {
                const res = await fetch('reeval_action.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Connection error');
            }
        }

        // Check notifications on interval
        setInterval(fetchNotifications, 30000);
        window.addEventListener('DOMContentLoaded', () => {
            fetchNotifications();
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>