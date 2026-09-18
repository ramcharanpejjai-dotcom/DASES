<?php
require_once 'db.php';

$results = [];
$error   = '';
$searched = false;
$pin = '';
$reevaluations = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['student_pin'])) {
    $pin = trim($_POST['student_pin']);
    $searched = true;

    $stmt = $pdo->prepare("
        SELECT 
            p.id as paper_id, p.paper_code, p.subject_name, p.subject_code, p.student_pin, p.status as paper_status,
            e.id as evaluation_id, e.external_score, e.internal_marks, e.total_score, e.feedback,
            e.question_marks, e.evaluated_at,
            u.name as teacher_name
        FROM papers p
        LEFT JOIN evaluations e ON p.id = e.paper_id
        LEFT JOIN users u ON p.assigned_teacher_id = u.id
        WHERE p.student_pin = ?
        ORDER BY p.id ASC
    ");
    $stmt->execute([$pin]);
    $results = $stmt->fetchAll();

    if (empty($results)) {
        $error = 'No records found for PIN <strong>' . htmlspecialchars($pin) . '</strong>. Please verify your Roll Number and try again.';
    } else {
        // Fetch reevaluations for this PIN
        $rStmt = $pdo->prepare("SELECT * FROM reevaluations WHERE student_pin = ? ORDER BY created_at DESC");
        $rStmt->execute([$pin]);
        $reevaluations = $rStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result & Grade Card - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.1) 0%, transparent 60%),
                        linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #0F172A;
        }

        .landing-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226,232,240,0.8);
        }

        .search-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px 30px;
        }

        .search-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 40px 36px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
        }

        .result-container {
            max-width: 960px;
            margin: 30px auto;
            padding: 0 20px 40px;
            width: 100%;
        }

        .gradecard-header {
            background: white;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .stat-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0F172A;
            margin-top: 4px;
        }

        .subject-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .badge-pass { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; font-weight: 800; padding: 4px 10px; border-radius: 20px; font-size: 0.82rem; }
        .badge-fail { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; font-weight: 800; padding: 4px 10px; border-radius: 20px; font-size: 0.82rem; }
        .badge-pending { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; font-weight: 800; padding: 4px 10px; border-radius: 20px; font-size: 0.82rem; }

        .marks-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 6px;
            margin: 10px 0 16px;
        }

        .marks-grid-b {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            margin: 10px 0 16px;
        }

        .marks-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 6px 2px;
            text-align: center;
        }

        /* Re-evaluation Modal */
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        /* Dark Mode & Theme Support */
        body.dark-mode, body.theme-midnight, body.theme-cyberpunk {
            background: var(--bg-main, #0F172A) !important;
            color: var(--text-main, #F1F5F9) !important;
        }
        body.dark-mode .landing-navbar, body.theme-midnight .landing-navbar {
            background: rgba(30, 41, 59, 0.9) !important;
            border-bottom-color: #334155 !important;
        }
        body.dark-mode .search-card, body.theme-midnight .search-card,
        body.dark-mode .gradecard-header, body.theme-midnight .gradecard-header,
        body.dark-mode .stat-card, body.theme-midnight .stat-card,
        body.dark-mode .subject-card, body.theme-midnight .subject-card,
        body.dark-mode .modal-box, body.theme-midnight .modal-box {
            background: var(--bg-card, #1E293B) !important;
            border-color: var(--border, #334155) !important;
            color: var(--text-main, #F1F5F9) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25) !important;
        }
        body.dark-mode .stat-val, body.theme-midnight .stat-val {
            color: #F8FAFC !important;
        }
        body.dark-mode .marks-box, body.theme-midnight .marks-box {
            background: #0F172A !important;
            border-color: #334155 !important;
        }
        body.dark-mode .marks-box div:first-child, body.theme-midnight .marks-box div:first-child {
            color: #94A3B8 !important;
        }
        body.dark-mode input[type="text"], body.theme-midnight input[type="text"],
        body.dark-mode select, body.theme-midnight select,
        body.dark-mode textarea, body.theme-midnight textarea {
            background: #0F172A !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .stats-strip {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .marks-grid {
                grid-template-columns: repeat(5, 1fr) !important;
            }
            .marks-grid-b {
                grid-template-columns: repeat(4, 1fr) !important;
            }
            .gradecard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .landing-navbar {
                padding: 14px 20px;
            }
        }
        @media (max-width: 480px) {
            .stats-strip {
                grid-template-columns: 1fr !important;
            }
            .marks-grid, .marks-grid-b {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .subject-card { break-inside: avoid; border: 1px solid #ccc; box-shadow: none; }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header class="landing-navbar no-print">
        <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <div style="width: 38px; height: 38px; background: linear-gradient(135deg, #6366F1, #4F46E5); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                <i data-lucide="graduation-cap" class="icon-md"></i>
            </div>
            <span style="font-size: 1.1rem; font-weight: 800; color: #0F172A;">DASES Portal</span>
        </a>
        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="btn-secondary" style="padding: 6px 10px; font-size: 0.9rem; border-radius: 8px; cursor: pointer; border: 1px solid #CBD5E1; background: #F1F5F9;" title="Toggle Theme" aria-label="Toggle Theme">
                <i data-lucide="moon" class="icon-sm"></i>
            </button>
            <a href="index.php" style="font-size: 0.9rem; font-weight: 600; color: #64748B; text-decoration: none;">← Home</a>
        </div>
    </header>

    <?php if (!$searched || empty($results)): ?>
    <!-- PIN Entry Form -->
    <div class="search-wrapper">
        <div class="search-card">
            <div style="text-align: center; margin-bottom: 26px;">
                <div style="display: flex; justify-content: center; margin-bottom: 12px; color: var(--accent);">
                    <i data-lucide="clipboard-list" class="icon-hero"></i>
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Student Result & Grade Card</h1>
                <p style="color: #64748B; font-size: 0.9rem;">Enter your Roll Number / PIN to check your evaluated semester marksheets.</p>
            </div>

            <?php if ($error): ?>
                <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 16px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" class="icon-sm" style="flex-shrink:0;"></i> <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="student-result.php">
                <div style="margin-bottom: 18px;">
                    <label style="font-weight: 700; font-size: 0.88rem; display: block; margin-bottom: 6px; color: #374151;">Student Roll Number / PIN</label>
                    <input type="text" name="student_pin" placeholder="e.g. 21001-CM-042" value="<?php echo htmlspecialchars($_POST['student_pin'] ?? ''); ?>" style="width: 100%; box-sizing: border-box; padding: 12px 16px; border: 1.5px solid #CBD5E1; border-radius: 10px; font-size: 1rem; outline: none;" required autofocus>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 13px; font-size: 1rem; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="search" class="icon-sm"></i> Access Semester Marksheet →
                </button>
            </form>
        </div>
    </div>

    <?php else: 
        // Compute overall student metrics
        $totalSubjects = count($results);
        $completedSubjects = 0;
        $passedSubjects = 0;
        $totalMarksScored = 0;
        $maxPossibleMarks = $totalSubjects * 100;

        foreach ($results as $r) {
            if ($r['paper_status'] === 'completed') {
                $completedSubjects++;
                $score = (float)($r['total_score'] ?? 0);
                $totalMarksScored += $score;
                if ($score >= 35) $passedSubjects++;
            }
        }
        $aggregatePct = ($completedSubjects > 0) ? round(($totalMarksScored / ($completedSubjects * 100)) * 100, 1) : 0;
    ?>
    <!-- Full Semester Grade Card -->
    <div class="result-container">
        <!-- Gradecard Header -->
        <div class="gradecard-header">
            <div>
                <span style="font-size: 0.8rem; font-weight: 800; color: #6366F1; text-transform: uppercase; letter-spacing: 0.5px;">Official Grade Report</span>
                <h1 style="font-size: 1.45rem; font-weight: 800; color: #0F172A; margin: 4px 0;">Student Result Statement</h1>
                <div style="font-size: 0.95rem; color: #475569;">Candidate Roll Number: <strong style="color: #1D4ED8; font-family: monospace; font-size: 1.05rem;"><?php echo htmlspecialchars($pin); ?></strong></div>
            </div>
            <div class="no-print" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="student-dashboard.php?pin=<?php echo urlencode($pin); ?>" class="btn-primary" style="padding: 8px 14px; font-size: 0.88rem; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #4F46E5 0%, #06B6D4 100%);">
                    <i data-lucide="award" class="icon-xs"></i> Open Full Student Dashboard
                </a>
                <button onclick="window.print()" class="btn-secondary" style="padding: 8px 14px; font-size: 0.88rem; font-weight: 700; cursor: pointer; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="printer" class="icon-xs"></i> Print
                </button>
                <a href="student-result.php" class="btn-secondary" style="padding: 8px 14px; font-size: 0.88rem; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="search" class="icon-xs"></i> Search Another
                </a>
            </div>
        </div>

        <!-- Metric Strips -->
        <div class="stats-strip">
            <div class="stat-card">
                <div style="font-size: 0.78rem; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Subjects</div>
                <div class="stat-val"><?php echo $totalSubjects; ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.78rem; font-weight: 700; color: #166534; text-transform: uppercase;">Passed</div>
                <div class="stat-val" style="color: #166534;"><?php echo $passedSubjects; ?> / <?php echo $completedSubjects; ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.78rem; font-weight: 700; color: #6366F1; text-transform: uppercase;">Total Score</div>
                <div class="stat-val" style="color: #4F46E5;"><?php echo $totalMarksScored; ?> <span style="font-size: 0.85rem; color: #64748B;">/ <?php echo ($completedSubjects * 100); ?></span></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.78rem; font-weight: 700; color: #0F172A; text-transform: uppercase;">Aggregate %</div>
                <div class="stat-val"><?php echo $aggregatePct; ?>%</div>
            </div>
        </div>

        <!-- Active Re-evaluations Tracker -->
        <?php if (!empty($reevaluations)): ?>
            <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px;">
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #1E40AF; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="shield-alert" class="icon-sm"></i> Re-Evaluation Grievance Tracker
                </h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($reevaluations as $rev): ?>
                        <div style="background: white; border: 1px solid #DBEAFE; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                            <div>
                                <strong>Subject: <?php echo htmlspecialchars($rev['subject_code']); ?></strong> — Reason: <em>"<?php echo htmlspecialchars($rev['reason']); ?>"</em>
                                <div style="font-size: 0.75rem; color: #64748B; margin-top: 2px;">Filed on <?php echo date('d M Y, h:i A', strtotime($rev['created_at'])); ?></div>
                            </div>
                            <div>
                                <?php if ($rev['status'] === 'pending'): ?>
                                    <span class="badge-pending" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="clock" class="icon-xs"></i> Pending Review</span>
                                <?php elseif ($rev['status'] === 'approved'): ?>
                                    <span class="badge-pass" style="background:#DBEAFE; color:#1E40AF; border-color:#93C5FD; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="refresh-cw" class="icon-xs"></i> Under Re-evaluation</span>
                                <?php elseif ($rev['status'] === 'completed'): ?>
                                    <span class="badge-pass" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="check-circle" class="icon-xs"></i> Re-evaluation Completed</span>
                                <?php else: ?>
                                    <span class="badge-fail" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="x-circle" class="icon-xs"></i> Request Declined</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Subject List -->
        <h2 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 14px; color: #1E293B;">Course-Wise Subject Marksheets</h2>
        <?php foreach ($results as $idx => $subj): 
            $isDone = ($subj['paper_status'] === 'completed');
            $qMarks = !empty($subj['question_marks']) ? json_decode($subj['question_marks'], true) : [];
            $secA = $qMarks['q_3m'] ?? [];
            $secB = $qMarks['q_10m'] ?? [];
            $total = (float)($subj['total_score'] ?? 0);
            $isPass = ($total >= 35);
        ?>
            <div class="subject-card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F1F5F9; padding-bottom: 12px; margin-bottom: 14px;">
                    <div>
                        <span style="font-size: 0.78rem; font-weight: 700; color: #6366F1; text-transform: uppercase;">Subject <?php echo ($idx + 1); ?></span>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0F172A; margin: 2px 0;"><?php echo htmlspecialchars($subj['subject_name'] ?? 'Subject'); ?> (<?php echo htmlspecialchars($subj['subject_code']); ?>)</h3>
                        <span style="font-size: 0.8rem; color: #64748B;">Paper Code: <?php echo htmlspecialchars($subj['paper_code']); ?></span>
                    </div>
                    <div>
                        <?php if (!$isDone): ?>
                            <span class="badge-pending" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="clock" class="icon-xs"></i> <?php echo ucfirst(htmlspecialchars($subj['paper_status'])); ?></span>
                        <?php elseif ($isPass): ?>
                            <span class="badge-pass" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="check-circle-2" class="icon-xs"></i> PASS (<?php echo $total; ?>/100)</span>
                        <?php else: ?>
                            <span class="badge-fail" style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="x-circle" class="icon-xs"></i> FAIL (<?php echo $total; ?>/100)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isDone): ?>
                    <!-- Section A Summary -->
                    <div style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Section A Questions (3M each):</div>
                    <div class="marks-grid">
                        <?php for ($q = 1; $q <= 10; $q++): ?>
                            <div class="marks-box">
                                <div style="font-size:0.68rem; color:#64748B;">Q<?php echo $q; ?></div>
                                <div style="font-size:0.92rem; font-weight:800; color:#4F46E5;"><?php echo number_format((float)($secA[$q-1] ?? 0), 1); ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Section B Summary -->
                    <?php
                        $secBWithKeys = [];
                        for ($q = 1; $q <= 8; $q++) {
                            $secBWithKeys[$q] = (float)($secB[$q - 1] ?? 0);
                        }
                        arsort($secBWithKeys, SORT_NUMERIC);
                        $top5BKeys = array_slice(array_keys($secBWithKeys), 0, 5, true);
                    ?>
                    <div style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Section B Questions (10M each | Best 5 Scored: Max 50M):</div>
                    <div class="marks-grid-b">
                        <?php for ($q = 1; $q <= 8; $q++): 
                            $score = (float)($secB[$q - 1] ?? 0);
                            $isCounted = in_array($q, $top5BKeys) && $score > 0;
                            $isAttempted = $score > 0;
                        ?>
                            <div class="marks-box" style="<?php echo $isCounted ? 'border-color: #10B981; background: #F0FDF4;' : ($isAttempted ? 'background: #FFFBEB;' : ''); ?>">
                                <div style="font-size:0.68rem; color:#64748B;">
                                    Q<?php echo $q; ?>
                                    <?php if ($isCounted): ?>
                                        <span style="color:#059669; font-weight:700;">(Best 5)</span>
                                    <?php elseif ($isAttempted): ?>
                                        <span style="color:#F59E0B; font-weight:600;">(Extra)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.92rem; font-weight:800; color:<?php echo $isCounted ? '#059669' : '#4F46E5'; ?>;"><?php echo number_format($score, 1); ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Score Breakdown Strip -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 14px;">
                        <div style="font-size: 0.85rem;">
                            External Score: <strong><?php echo number_format((float)($subj['external_score'] ?? 0), 1); ?> / 80</strong> &nbsp;|&nbsp;
                            Internal Marks: <strong><?php echo number_format((float)($subj['internal_marks'] ?? 0), 1); ?> / 20</strong>
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #1E1B4B;">
                            Total: <?php echo number_format($total, 1); ?> / 100
                        </div>
                        <div class="no-print">
                            <button type="button" onclick="openReevalModal(<?php echo $subj['paper_id']; ?>, '<?php echo htmlspecialchars($subj['subject_code']); ?>', '<?php echo htmlspecialchars($subj['subject_name'] ?? ''); ?>')" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                <i data-lucide="shield" class="icon-xs"></i> Apply for Re-Evaluation
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($subj['feedback'])): ?>
                        <div style="margin-top: 10px; font-size: 0.82rem; color: #64748B; background: #FFFBEB; border: 1px solid #FCD34D; padding: 8px 12px; border-radius: 6px;">
                            <strong>Evaluator Remarks:</strong> <?php echo htmlspecialchars($subj['feedback']); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 8px 0;">This paper has been uploaded and is currently under evaluation by the designated faculty. Please check back shortly.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Re-Evaluation Request Modal -->
    <div class="modal-backdrop no-print" id="reeval-modal">
        <div class="modal-box">
            <h3 style="font-size: 1.2rem; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">Apply for Re-Evaluation / Re-Counting</h3>
            <p style="font-size: 0.85rem; color: #64748B; margin-bottom: 16px;" id="modal-subj-label">Subject Details</p>

            <form id="reeval-form" onsubmit="submitReeval(event)">
                <input type="hidden" name="paper_id" id="modal_paper_id">
                <input type="hidden" name="student_pin" value="<?php echo htmlspecialchars($pin); ?>">

                <div style="margin-bottom: 14px;">
                    <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 6px;">Reason for Grievance / Re-Evaluation:</label>
                    <textarea name="reason" rows="3" placeholder="Please specify details (e.g., Section B Question 4 marks mismatch, recount requested)..." style="width: 100%; box-sizing: border-box; padding: 10px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-family: inherit; font-size: 0.88rem; resize: vertical;" required></textarea>
                </div>

                <div id="reeval-msg" style="margin-bottom: 12px; font-size: 0.85rem; display: none;"></div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeReevalModal()" class="btn-secondary" style="padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReevalModal(paperId, code, name) {
            document.getElementById('modal_paper_id').value = paperId;
            document.getElementById('modal-subj-label').innerText = `${name} (${code}) — Roll No: ${<?php echo json_encode($pin); ?>}`;
            document.getElementById('reeval-msg').style.display = 'none';
            document.getElementById('reeval-modal').style.display = 'flex';
        }

        function closeReevalModal() {
            document.getElementById('reeval-modal').style.display = 'none';
        }

        async function submitReeval(e) {
            e.preventDefault();
            const form = document.getElementById('reeval-form');
            const formData = new FormData(form);
            const msgBox = document.getElementById('reeval-msg');
            msgBox.style.display = 'block';
            msgBox.innerHTML = '⏳ Submitting grievance request...';

            try {
                const res = await fetch('apply_reeval.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    msgBox.innerHTML = `<span style="color:#166534; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="check" class="icon-xs"></i> ${data.message}</span>`;
                    if (window.lucide) lucide.createIcons();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    msgBox.innerHTML = `<span style="color:#991B1B; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="alert-triangle" class="icon-xs"></i> ${data.error}</span>`;
                    if (window.lucide) lucide.createIcons();
                }
            } catch (err) {
                msgBox.innerHTML = `<span style="color:#991B1B; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="alert-triangle" class="icon-xs"></i> Failed to connect to server.</span>`;
                if (window.lucide) lucide.createIcons();
            }
        }
    </script>
    <?php endif; ?>

    <footer style="padding: 20px; text-align: center; font-size: 0.82rem; color: #94A3B8; border-top: 1px solid rgba(226,232,240,0.6);" class="no-print">
        © <?php echo date('Y'); ?> DASES. Academic Answer Script Evaluation & Assessment Platform.
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>
