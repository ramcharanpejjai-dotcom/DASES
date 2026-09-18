<?php
require_once 'auth.php';
checkAccess(); // User must be logged in (admin or teacher)
require_once 'db.php';
require_once 'curriculum_data.php';

$paper_id = intval($_GET['paper_id'] ?? 0);
$user_id  = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch paper and evaluation details
if ($user_role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT p.*, e.*, u.name as teacher_name 
        FROM papers p 
        JOIN evaluations e ON p.id = e.paper_id 
        LEFT JOIN users u ON p.assigned_teacher_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$paper_id]);
} else {
    // Teacher can only view their own assigned papers (and PIN will be hidden)
    $stmt = $pdo->prepare("
        SELECT p.*, e.*, u.name as teacher_name 
        FROM papers p 
        JOIN evaluations e ON p.id = e.paper_id 
        LEFT JOIN users u ON p.assigned_teacher_id = u.id 
        WHERE p.id = ? AND p.assigned_teacher_id = ?
    ");
    $stmt->execute([$paper_id, $user_id]);
}

$eval = $stmt->fetch();

if (!$eval) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h3>Evaluation record not found or access denied.</h3><a href='index.php'>Return to Portal</a></div>");
}

// Decode question-wise breakdown if stored as JSON
$q_marks = !empty($eval['question_marks']) ? json_decode($eval['question_marks'], true) : [];
$q_3m = $q_marks['q_3m'] ?? [];
$q_10m = $q_marks['q_10m'] ?? [];

$backUrl = ($user_role === 'admin') ? 'admin-dashboard.php?tab=manage' : 'teacher-dashboard.php?tab=completed';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet - Paper <?php echo htmlspecialchars($eval['paper_code']); ?> - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .marksheet-wrapper {
            max-width: 850px;
            margin: 30px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            background: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 25px;
        }
        .meta-item { font-size: 0.95rem; }
        .meta-item strong { color: #374151; }
        .marks-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .marks-box {
            background: #F3F4F6;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 8px 4px;
        }
        .marks-box .q-no { font-size: 0.75rem; font-weight: bold; color: #4B5563; }
        .marks-box .q-score { font-size: 1rem; font-weight: 700; color: var(--primary); margin-top: 4px; }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1F2937;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        .score-summary-box {
            background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
        }
        .summary-flex {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 1rem;
        }
        .grand-total-row {
            border-top: 1px solid rgba(255,255,255,0.3);
            margin-top: 10px;
            padding-top: 10px;
            font-size: 1.3rem;
            font-weight: 800;
        }
        /* Dark mode & Theme Support */
        body.dark-mode, body.theme-midnight, body.theme-cyberpunk {
            background: var(--bg-main, #0F172A) !important;
            color: var(--text-main, #F1F5F9);
        }
        body.dark-mode .marksheet-wrapper, body.theme-midnight .marksheet-wrapper {
            background: var(--bg-card, #1E293B);
            border-color: var(--border, #334155);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        body.dark-mode .header-bar p, body.theme-midnight .header-bar p {
            color: #94A3B8 !important;
        }
        body.dark-mode .meta-grid, body.theme-midnight .meta-grid {
            background: #0F172A;
            border-color: #334155;
        }
        body.dark-mode .meta-item strong, body.theme-midnight .meta-item strong {
            color: #94A3B8;
        }
        body.dark-mode .meta-item, body.theme-midnight .meta-item {
            color: #CBD5E1;
        }
        body.dark-mode .section-title, body.theme-midnight .section-title {
            color: #F8FAFC;
        }
        body.dark-mode .marks-box, body.theme-midnight .marks-box {
            background: #0F172A !important;
            border-color: #334155 !important;
        }
        body.dark-mode .marks-box .q-no, body.theme-midnight .marks-box .q-no {
            color: #94A3B8;
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .marksheet-wrapper { padding: 20px 15px; margin: 15px auto; }
            .meta-grid { grid-template-columns: 1fr; gap: 10px; padding: 15px; }
            .marks-grid { grid-template-columns: repeat(5, 1fr) !important; gap: 6px; }
            .header-bar { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
        @media (max-width: 480px) {
            .marks-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media print {
            .no-print { display: none !important; }
            .marksheet-wrapper { box-shadow: none; border: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body style="padding: 20px;">
    <div class="marksheet-wrapper">
        <div class="header-bar">
            <div>
                <h1 style="font-size: 1.6rem; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="award" class="icon-lg"></i> DASES Evaluation Marksheet
                </h1>
                <p style="color: #6B7280; font-size: 0.85rem;">Digital Answer Script Evaluation System</p>
            </div>
            <div class="no-print" style="display: flex; align-items: center;">
                <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="btn-secondary" style="padding: 8px 12px; margin-right: 8px;" title="Toggle Theme" aria-label="Toggle Theme">
                    <i data-lucide="moon" class="icon-sm"></i>
                </button>
                <button onclick="window.print()" class="btn-secondary" style="padding: 8px 16px; margin-right: 8px; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="printer" class="icon-xs"></i> Print
                </button>
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn-primary" style="padding: 8px 16px; text-decoration: none;">← Back</a>
            </div>
        </div>

        <div class="meta-grid">
            <?php if ($user_role === 'admin'): ?>
                <div class="meta-item">
                    <strong>Student PIN / Roll No:</strong> 
                    <span style="color: #B91C1C; font-weight: 700; font-size: 1.05rem;"><?php echo htmlspecialchars($eval['student_pin'] ?? 'N/A'); ?></span>
                </div>
            <?php endif; ?>
            <div class="meta-item"><strong>Paper Code:</strong> <?php echo htmlspecialchars($eval['paper_code']); ?></div>
            <div class="meta-item"><strong>Subject Name:</strong> <?php echo htmlspecialchars($eval['subject_name'] ?? 'N/A'); ?></div>
            <div class="meta-item"><strong>Subject Code:</strong> <?php echo htmlspecialchars($eval['subject_code']); ?></div>
            <div class="meta-item"><strong>Evaluator:</strong> <?php echo htmlspecialchars($eval['teacher_name'] ?? 'Assigned Evaluator'); ?></div>
            <div class="meta-item"><strong>Evaluated On:</strong> <?php echo date('d M Y, h:i A', strtotime($eval['evaluated_at'])); ?></div>
        </div>

        <!-- Section A Marks -->
        <div class="section-title">Section A: Short Questions (Max 3 Marks each | Total: 30 Marks)</div>
        <div class="marks-grid">
            <?php for ($i = 1; $i <= 10; $i++): 
                $score = $q_3m[$i - 1] ?? ($eval["q{$i}_marks"] ?? 0);
            ?>
                <div class="marks-box">
                    <div class="q-no">Q<?php echo $i; ?></div>
                    <div class="q-score"><?php echo number_format((float)$score, 1); ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Section B Marks -->
        <?php
            $secBWithKeys = [];
            for ($i = 1; $i <= 8; $i++) {
                $secBWithKeys[$i] = (float)($q_10m[$i - 1] ?? 0);
            }
            arsort($secBWithKeys, SORT_NUMERIC);
            $top5BKeys = array_slice(array_keys($secBWithKeys), 0, 5, true);
        ?>
        <div class="section-title">Section B: Long Questions (8 Questions | Max 10M each | Best 5 Scored: Max 50 Marks)</div>
        <div class="marks-grid" style="grid-template-columns: repeat(8, 1fr);">
            <?php for ($i = 1; $i <= 8; $i++): 
                $score = (float)($q_10m[$i - 1] ?? 0);
                $isCounted = in_array($i, $top5BKeys) && $score > 0;
                $isAttempted = $score > 0;
            ?>
                <div class="marks-box" style="<?php echo $isCounted ? 'border-color: #10B981; background: #F0FDF4;' : ($isAttempted ? 'background: #FFFBEB;' : ''); ?>">
                    <div class="q-no">
                        Q<?php echo $i; ?> 
                        <?php if ($isCounted): ?>
                            <span style="font-size:0.62rem; color:#059669; font-weight:700; display:block;">⭐ Best 5</span>
                        <?php elseif ($isAttempted): ?>
                            <span style="font-size:0.62rem; color:#F59E0B; font-weight:600; display:block;">Extra</span>
                        <?php else: ?>
                            <span style="font-size:0.62rem; color:#CBD5E1; display:block;">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="q-score" style="<?php echo $isCounted ? 'color: #059669;' : ''; ?>"><?php echo number_format($score, 1); ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <?php if (!empty($eval['feedback'])): ?>
            <div style="margin-top: 20px; background: #FEF3C7; padding: 15px; border-radius: 8px; border: 1px solid #FCD34D;">
                <strong style="color: #92400E; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                    <i data-lucide="message-square" class="icon-xs"></i> Evaluator Remarks / Feedback:
                </strong>
                <p style="color: #78350F; margin: 0; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($eval['feedback'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Summary & Grand Total -->
        <?php
            $grand_total = (float)($eval['total_score'] ?? 0);
            $gradeDetails = getSBTETGradeDetails($grand_total);
            $passed = $gradeDetails['passed'];
            $courseCredits = getSubjectCredits($eval['subject_code'] ?? '');
        ?>
        <div class="score-summary-box">
            <div class="summary-flex">
                <span>Section A &amp; B External Total (Max 80):</span>
                <strong><?php echo number_format((float)($eval['external_score'] ?? 0), 1); ?> / 80</strong>
            </div>
            <div class="summary-flex">
                <span>Internal Marks (Max 20):</span>
                <strong><?php echo number_format((float)($eval['internal_marks'] ?? 0), 1); ?> / 20</strong>
            </div>
            <div class="summary-flex grand-total-row">
                <span>Grand Total Score:</span>
                <span><?php echo number_format($grand_total, 1); ?> / 100</span>
            </div>
        </div>

        <!-- Pass / Fail Result Banner with SBTET Grade -->
        <?php if ($passed): ?>
            <div style="
                margin-top: 20px;
                padding: 18px 24px;
                background: linear-gradient(135deg, #DCFCE7 0%, #BBF7D0 100%);
                border: 2px solid #22C55E;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            ">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        Evaluation Result &bull; <?php echo $courseCredits; ?> Course Credits
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: #166534; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check-circle-2" class="icon-md"></i> PASS &bull; Grade <?php echo $gradeDetails['grade']; ?> (<?php echo $gradeDetails['points']; ?> GP)
                    </div>
                    <div style="font-size: 0.88rem; color: #166534; margin-top: 2px;">
                        Score <?php echo number_format($grand_total, 1); ?>/100 &bull; <?php echo $gradeDetails['desc']; ?>
                    </div>
                </div>
                <div style="color: #166534; opacity: 0.25;"><i data-lucide="award" class="icon-hero" style="width: 56px; height: 56px;"></i></div>
            </div>
        <?php else: ?>
            <div style="
                margin-top: 20px;
                padding: 18px 24px;
                background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
                border: 2px solid #EF4444;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            ">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 700; color: #B91C1C; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        Evaluation Result &bull; <?php echo $courseCredits; ?> Course Credits
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: #7F1D1D; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="x-circle" class="icon-md"></i> FAIL &bull; Grade <?php echo $gradeDetails['grade']; ?> (<?php echo $gradeDetails['points']; ?> GP)
                    </div>
                    <div style="font-size: 0.88rem; color: #7F1D1D; margin-top: 2px;">
                        Score <?php echo number_format($grand_total, 1); ?>/100 &bull; <?php echo $gradeDetails['desc']; ?>
                    </div>
                </div>
                <div style="color: #7F1D1D; opacity: 0.25;"><i data-lucide="file-x" class="icon-hero" style="width: 56px; height: 56px;"></i></div>
            </div>
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
