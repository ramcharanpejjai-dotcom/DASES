<?php
session_start();
require_once 'db.php';
require_once 'curriculum_data.php';

// Handle Logout / Switch PIN
if (isset($_GET['action']) && $_GET['action'] === 'switch') {
    unset($_SESSION['student_pin']);
    header("Location: student-dashboard.php");
    exit();
}

$pin = '';
$results = [];
$reevaluations = [];
$error = '';
$searched = false;

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Check POST or GET or SESSION for PIN
if ($requestMethod === 'POST' && !empty($_POST['student_pin'])) {
    $pin = trim($_POST['student_pin']);
    $_SESSION['student_pin'] = $pin;
    $searched = true;
} elseif (!empty($_GET['pin'])) {
    $pin = trim($_GET['pin']);
    $_SESSION['student_pin'] = $pin;
    $searched = true;
} elseif (!empty($_SESSION['student_pin'])) {
    $pin = $_SESSION['student_pin'];
    $searched = true;
}

// Defined Semesters List (Sem-1 encompasses 1st & 2nd semesters combined)
$standardSemesters = ['Sem-1', 'Sem-3', 'Sem-4', 'Sem-5'];
$semesterData = [];
foreach ($standardSemesters as $s) {
    $semesterData[$s] = [];
}

$overallTotalEarnedPoints = 0;
$overallTotalCredits = 0;
$totalPassedOverall = 0;
$totalBacklogsOverall = 0;
$totalCompletedOverall = 0;

if (!empty($pin)) {
    // STRICT CONFIDENTIALITY: Never select examiner name, ID, or raw script file_path
    $stmt = $pdo->prepare("
        SELECT 
            p.id as paper_id, 
            p.paper_code, 
            p.subject_name, 
            p.subject_code, 
            p.student_pin, 
            p.semester, 
            p.status as paper_status, 
            p.uploaded_at,
            e.id as evaluation_id, 
            e.external_score, 
            e.internal_marks, 
            e.total_score, 
            e.feedback,
            e.question_marks, 
            e.evaluated_at
        FROM papers p
        LEFT JOIN evaluations e ON p.id = e.paper_id
        WHERE p.student_pin = ?
        ORDER BY p.id ASC
    ");
    $stmt->execute([$pin]);
    $results = $stmt->fetchAll();

    if (empty($results)) {
        $error = 'No records found for Roll / PIN <strong>' . htmlspecialchars($pin) . '</strong>. Please verify your PIN and try again.';
    } else {
        // Fetch Re-evaluations
        $rStmt = $pdo->prepare("SELECT * FROM reevaluations WHERE student_pin = ? ORDER BY created_at DESC");
        $rStmt->execute([$pin]);
        $reevaluations = $rStmt->fetchAll();

        // Organize papers by semester
        foreach ($results as $paper) {
            $sem = $paper['semester'] ?: 'Sem-1';
            if (!in_array($sem, $standardSemesters)) {
                $sem = 'Sem-1';
            }
            $semesterData[$sem][] = $paper;
        }

        // Calculate Overall CGPA and counts using official SBTET AP Grade Point Scale & Course Credits
        foreach ($results as $p) {
            if ($p['paper_status'] === 'completed' && $p['total_score'] !== null) {
                $score = (float)$p['total_score'];
                $totalCompletedOverall++;

                $gradeInfo = getSBTETGradeDetails($score);
                if ($gradeInfo['passed']) {
                    $totalPassedOverall++;
                } else {
                    $totalBacklogsOverall++;
                }

                // Official SBTET Course Credits (lookup by subject code, fallback to 4.0)
                $credits = getSubjectCredits($p['subject_code'] ?? '');
                $overallTotalEarnedPoints += ($gradeInfo['points'] * $credits);
                $overallTotalCredits += $credits;
            }
        }
    }
}

// Active Semester Tab (Default to latest semester with papers or Sem-1)
$activeSem = $_GET['sem'] ?? '';
if (empty($activeSem) || !in_array($activeSem, $standardSemesters)) {
    $activeSem = 'Sem-1';
    // If student has papers in another semester, default to the one with papers
    foreach (array_reverse($standardSemesters) as $s) {
        if (!empty($semesterData[$s])) {
            $activeSem = $s;
            break;
        }
    }
}

// Calculate Active Semester SGPA using official formula:
// SGPA = Sum(Course Credits * Grade Points Earned) / Sum(Total Credits of the Semester)
$activeSemPapers = $semesterData[$activeSem] ?? [];
$activeSemCredits = 0;
$activeSemEarnedPoints = 0;
$activeSemPassed = 0;
$activeSemBacklogs = 0;
$activeSemCompleted = 0;

foreach ($activeSemPapers as $p) {
    if ($p['paper_status'] === 'completed' && $p['total_score'] !== null) {
        $score = (float)$p['total_score'];
        $activeSemCompleted++;

        $gradeInfo = getSBTETGradeDetails($score);
        if ($gradeInfo['passed']) {
            $activeSemPassed++;
        } else {
            $activeSemBacklogs++;
        }

        $c = getSubjectCredits($p['subject_code'] ?? '');
        $activeSemEarnedPoints += ($gradeInfo['points'] * $c);
        $activeSemCredits += $c;
    }
}

// Format SGPA & CGPA
$activeSemSGPA = $activeSemCredits > 0 ? number_format($activeSemEarnedPoints / $activeSemCredits, 2) : 'N/A';
$overallCGPA = $overallTotalCredits > 0 ? number_format($overallTotalEarnedPoints / $overallTotalCredits, 2) : 'N/A';

// Official SBTET Conversion: Percentage (%) = (CGPA - 0.5) * 10
$equivalentPercentage = ($overallCGPA !== 'N/A') ? convertCGPAToPercentage($overallCGPA) : null;

// Helper for letter grades
function getLetterGrade($score) {
    return getSBTETGradeDetails($score);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Academic Dashboard - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.6">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --primary-dark: #3730A3;
            --accent: #06B6D4;
            --bg-page: #F8FAFC;
            --card-bg: #FFFFFF;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        body {
            background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                        linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
            min-height: 100vh;
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-nav {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.15rem;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4F46E5 0%, #06B6D4 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 20px 80px;
        }

        /* PIN Login Hero */
        .pin-login-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 40px;
            max-width: 540px;
            margin: 40px auto;
            box-shadow: 0 20px 45px -10px rgba(79, 70, 229, 0.12);
            text-align: center;
        }

        .pin-input-group {
            display: flex;
            gap: 10px;
            margin: 24px 0 16px;
        }

        .pin-input {
            flex: 1;
            padding: 14px 18px;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 12px;
            border: 2px solid var(--border);
            outline: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: border-color 0.2s;
        }
        .pin-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-val {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Semester Navigation Tabs */
        .semester-nav-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 8px 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            flex-wrap: wrap;
            gap: 12px;
        }

        .sem-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .sem-pill {
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--text-muted);
            background: transparent;
            border: 1px solid transparent;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .sem-pill:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .sem-pill.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .sem-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 0.72rem;
        }
        .sem-pill:not(.active) .sem-badge {
            background: #E2E8F0;
            color: #475569;
        }

        /* Confidentiality Notice */
        .confidential-banner {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-left: 4px solid #6366F1;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 0.85rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        /* Subject Cards */
        .subject-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 4px 18px -4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .subject-card:hover {
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.08);
        }

        .marks-grid-10 {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 6px;
            margin: 10px 0 16px;
        }

        .marks-grid-8 {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            margin: 10px 0 16px;
        }

        .marks-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 8px 4px;
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
            z-index: 99999;
            padding: 20px;
        }

        .modal-box {
            background: white;
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }

        /* Printable Grade Card Styling */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .student-nav, .sem-pills, .no-print, .dases-mascot-wrapper, .modal-backdrop, .confidential-banner {
                display: none !important;
            }
            .dashboard-container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-only-header {
                display: block !important;
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 14px;
                margin-bottom: 20px;
            }
            .subject-card {
                border: 1px solid #94A3B8 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                margin-bottom: 16px !important;
            }
        }

        .print-only-header {
            display: none;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .marks-grid-10 {
                grid-template-columns: repeat(5, 1fr);
            }
            .marks-grid-8 {
                grid-template-columns: repeat(4, 1fr);
            }
            .semester-nav-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- TOP NAVBAR -->
    <header class="student-nav">
        <a href="index.php" class="logo-box">
            <div class="logo-icon">
                <i data-lucide="graduation-cap" style="width:20px; height:20px;"></i>
            </div>
            <div>
                <span>DASES</span>
                <span style="font-size:0.75rem; font-weight:600; color:var(--primary); margin-left:6px; background:var(--primary-light); padding:2px 8px; border-radius:6px;">Student Portal</span>
            </div>
        </a>

        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if (!empty($pin)): ?>
                <div style="display: flex; align-items: center; gap: 8px; background: #F1F5F9; padding: 6px 14px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; color: #334155;">
                    <i data-lucide="user-check" style="width:16px; height:16px; color:#4F46E5;"></i>
                    <span>PIN: <?php echo htmlspecialchars($pin); ?></span>
                </div>
                <a href="student-dashboard.php?action=switch" class="btn-secondary" style="font-size: 0.82rem; padding: 7px 12px; border-radius: 8px; text-decoration: none;">
                    <i data-lucide="log-out" style="width:14px; height:14px;"></i> Switch PIN
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-secondary" style="font-size: 0.85rem; padding: 7px 14px; text-decoration: none;">Staff Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="dashboard-container">

        <!-- PRINT HEADER (ONLY VISIBLE ON EXPORT / PRINT) -->
        <div class="print-only-header">
            <h1 style="margin: 0; font-size: 1.5rem; letter-spacing: 0.5px;">STATE BOARD OF TECHNICAL EDUCATION & EXAMINATIONS</h1>
            <h3 style="margin: 4px 0; font-size: 1.1rem; color: #333;">DIGITAL ANSWER SCRIPT EVALUATION SYSTEM (DASES)</h3>
            <p style="margin: 4px 0; font-size: 0.95rem;">OFFICIAL SEMESTER GRADE CARD & ACADEMIC TRANSCRIPT</p>
            <div style="margin-top: 12px; font-size: 0.9rem; font-weight: 700;">
                Candidate Roll / PIN: <?php echo htmlspecialchars($pin); ?> &nbsp;|&nbsp; Academic Cycle: <?php echo htmlspecialchars($activeSem); ?> &nbsp;|&nbsp; Date Issued: <?php echo date('d-M-Y'); ?>
            </div>
        </div>

        <?php if (empty($pin)): ?>
            <!-- PIN LOGIN SCREEN -->
            <div class="pin-login-card">
                <div style="width: 60px; height: 60px; background: var(--primary-light); color: var(--primary); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i data-lucide="award" style="width: 32px; height: 32px;"></i>
                </div>
                <h1 style="font-size: 1.6rem; font-weight: 800; margin: 0 0 8px;">Student Academic Dashboard</h1>
                <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">Access your semester grades, SGPA/CGPA records, official marksheet, and re-evaluation requests.</p>

                <?php if (!empty($error)): ?>
                    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #B91C1C; padding: 12px; border-radius: 10px; font-size: 0.88rem; margin-top: 16px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student-dashboard.php">
                    <div class="pin-input-group">
                        <input type="text" name="student_pin" class="pin-input" placeholder="Enter Student PIN / Roll No." required autofocus>
                        <button type="submit" class="btn-primary" style="padding: 0 24px; border-radius: 12px; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                            <span>View Portal</span>
                            <i data-lucide="arrow-right" style="width:18px; height:18px;"></i>
                        </button>
                    </div>
                </form>

                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: left;">
                    <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Demo PINs:</span>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        <a href="student-dashboard.php?pin=24018-CM-055" style="background: #EEF2FF; color: #4F46E5; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-decoration: none;">24018-CM-055 (Multi-Sem)</a>
                        <a href="student-dashboard.php?pin=21001-PIN-101" style="background: #F1F5F9; color: #334155; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-decoration: none;">21001-PIN-101</a>
                        <a href="student-dashboard.php?pin=21001-PIN-105" style="background: #F1F5F9; color: #334155; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-decoration: none;">21001-PIN-105</a>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- ERROR ALERT IF PIN HAS NO RECORDS -->
            <?php if (!empty($error)): ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #B91C1C; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <div><?php echo $error; ?></div>
                    <a href="student-dashboard.php?action=switch" class="btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">Try Another PIN</a>
                </div>
            <?php endif; ?>

            <!-- OVERALL ACADEMIC SUMMARY CARDS -->
            <div class="stats-grid no-print">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #EEF2FF; color: #4F46E5;">
                        <i data-lucide="award" style="width:26px; height:26px;"></i>
                    </div>
                    <div>
                        <div class="stat-val" style="color: #4F46E5; display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                            <span><?php echo $overallCGPA; ?></span>
                            <?php if ($equivalentPercentage !== null): ?>
                                <span style="font-size: 0.8rem; font-weight: 700; color: #059669; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 2px 8px; border-radius: 6px;" title="Official SBTET Formula: Percentage (%) = (CGPA - 0.5) * 10">
                                    <?php echo $equivalentPercentage; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-label">Cumulative CGPA <?php if ($equivalentPercentage !== null): ?><span style="font-size:0.75rem; color:#64748B;">(<?php echo $equivalentPercentage; ?>% Eqv.)</span><?php endif; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #F0FDF4; color: #10B981;">
                        <i data-lucide="check-circle-2" style="width:26px; height:26px;"></i>
                    </div>
                    <div>
                        <div class="stat-val" style="color: #10B981;"><?php echo $totalPassedOverall; ?></div>
                        <div class="stat-label">Subjects Cleared</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: <?php echo $totalBacklogsOverall > 0 ? '#FEF2F2' : '#F8FAFC'; ?>; color: <?php echo $totalBacklogsOverall > 0 ? '#EF4444' : '#64748B'; ?>;">
                        <i data-lucide="<?php echo $totalBacklogsOverall > 0 ? 'alert-triangle' : 'shield-check'; ?>" style="width:26px; height:26px;"></i>
                    </div>
                    <div>
                        <div class="stat-val" style="color: <?php echo $totalBacklogsOverall > 0 ? '#EF4444' : '#0F172A'; ?>;"><?php echo $totalBacklogsOverall; ?></div>
                        <div class="stat-label">Active Backlogs</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #ECFEFF; color: #06B6D4;">
                        <i data-lucide="trending-up" style="width:26px; height:26px;"></i>
                    </div>
                    <div>
                        <div class="stat-val" style="color: #06B6D4;"><?php echo $activeSemSGPA; ?></div>
                        <div class="stat-label"><?php echo htmlspecialchars($activeSem); ?> SGPA</div>
                    </div>
                </div>
            </div>

            <!-- SEMESTER NAVIGATION SELECTOR -->
            <div class="semester-nav-bar no-print">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.85rem; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">Semester:</span>
                    <div class="sem-pills">
                        <?php foreach ($standardSemesters as $semKey): 
                            $count = count($semesterData[$semKey] ?? []);
                            $isActive = ($semKey === $activeSem);
                        ?>
                            <a href="student-dashboard.php?sem=<?php echo urlencode($semKey); ?>" class="sem-pill <?php echo $isActive ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($semKey); ?></span>
                                <span class="sem-badge"><?php echo $count; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="window.print()" class="btn-secondary" style="font-size: 0.85rem; font-weight: 700; padding: 8px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-lucide="printer" style="width:16px; height:16px;"></i> Print / Download Marksheet
                    </button>
                </div>
            </div>

            <!-- STRICT CONFIDENTIALITY NOTICE -->
            <div class="confidential-banner no-print">
                <i data-lucide="shield-check" style="width: 22px; height: 22px; color: #6366F1; flex-shrink: 0;"></i>
                <div>
                    <strong>Official Examination Confidentiality:</strong> Student scorecards are officially verified and authenticated. In accordance with examination regulations, physical/scanned answer scripts and evaluator identities remain confidential.
                </div>
            </div>

            <!-- RE-EVALUATIONS STATUS TRACKER (IF ANY) -->
            <?php if (!empty($reevaluations)): ?>
                <div class="no-print" style="background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 18px 24px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <i data-lucide="clock" style="width:18px; height:18px; color:#4F46E5;"></i>
                        <h3 style="margin:0; font-size:1rem; font-weight:800;">Re-Evaluation Applications Tracking</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($reevaluations as $rev): 
                            $statusColor = '#F59E0B';
                            if ($rev['status'] === 'completed') $statusColor = '#10B981';
                            if ($rev['status'] === 'rejected') $statusColor = '#EF4444';
                        ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px 16px; border-radius: 10px; font-size: 0.85rem;">
                                <div>
                                    <strong>Subject Code: <?php echo htmlspecialchars($rev['subject_code']); ?></strong>
                                    <span style="color: #64748B; margin-left: 8px;">Applied on: <?php echo date('d-M-Y', strtotime($rev['created_at'])); ?></span>
                                    <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">Reason: <?php echo htmlspecialchars($rev['reason']); ?></div>
                                </div>
                                <span class="badge" style="background: <?php echo $statusColor; ?>22; color: <?php echo $statusColor; ?>; font-weight: 800; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($rev['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SEMESTER SUBJECT CARDS -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #1E293B;"><?php echo htmlspecialchars($activeSem); ?> Subject Scorecards</h2>
                        <span style="font-size: 0.82rem; color: #64748B;">Showing all registered courses for <?php echo htmlspecialchars($activeSem); ?></span>
                    </div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: #4F46E5;">
                        Semester SGPA: <strong><?php echo $activeSemSGPA; ?></strong>
                    </div>
                </div>

                <?php if (empty($activeSemPapers)): ?>
                    <div style="background: white; border: 1px dashed #CBD5E1; border-radius: 16px; padding: 40px 20px; text-align: center; color: #64748B;">
                        <i data-lucide="book-open" style="width: 36px; height: 36px; margin-bottom: 12px; color: #94A3B8;"></i>
                        <h3 style="margin: 0 0 6px; font-size: 1.1rem; color: #1E293B;">No Papers Registered for <?php echo htmlspecialchars($activeSem); ?></h3>
                        <p style="margin: 0; font-size: 0.85rem;">Results for this semester have not been uploaded yet or you have not taken exams in this academic cycle.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeSemPapers as $idx => $subj): 
                        $isDone = ($subj['paper_status'] === 'completed');
                        $qMarks = !empty($subj['question_marks']) ? json_decode($subj['question_marks'], true) : [];
                        $secA = $qMarks['q_3m'] ?? [];
                        $secB = $qMarks['q_10m'] ?? [];
                        $total = (float)($subj['total_score'] ?? 0);
                        $letterGrade = getLetterGrade($isDone ? $total : null);
                        $isPass = $letterGrade['passed'];
                        $subjCredits = getSubjectCredits($subj['subject_code'] ?? '');
                    ?>
                        <div class="subject-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 0.75rem; font-weight: 800; color: #4F46E5; background: #EEF2FF; padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">
                                            Course <?php echo ($idx + 1); ?> &bull; <?php echo htmlspecialchars($activeSem); ?> &bull; <?php echo $subjCredits; ?> Credits
                                        </span>
                                        <span style="font-size: 0.8rem; color: #64748B;">Code: <strong><?php echo htmlspecialchars($subj['subject_code']); ?></strong></span>
                                        <span style="font-size: 0.8rem; color: #94A3B8;">Paper: <?php echo htmlspecialchars($subj['paper_code']); ?></span>
                                    </div>
                                    <h3 style="font-size: 1.25rem; font-weight: 800; color: #0F172A; margin: 4px 0 0;">
                                        <?php echo htmlspecialchars($subj['subject_name'] ?? 'Subject Course'); ?>
                                    </h3>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!$isDone): ?>
                                        <span class="badge" style="background: #FEF3C7; color: #D97706; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                                            <i data-lucide="clock" style="width:14px; height:14px;"></i> Evaluation in Progress
                                        </span>
                                    <?php else: ?>
                                        <div style="text-align: right; margin-right: 8px;">
                                            <div style="font-size: 1.25rem; font-weight: 800; color: <?php echo $letterGrade['color']; ?>;">
                                                Grade <?php echo $letterGrade['grade']; ?> <span style="font-size: 0.85rem; font-weight: 600; color: #64748B;">(<?php echo $letterGrade['points']; ?> GP)</span>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #64748B; font-weight: 600;">
                                                <?php echo $letterGrade['desc']; ?>
                                            </div>
                                        </div>
                                        <?php if ($isPass): ?>
                                            <span class="badge" style="background: #ECFDF5; color: #059669; font-weight: 800; padding: 8px 14px; border-radius: 8px; font-size: 0.9rem;">
                                                <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i> PASS
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #FEF2F2; color: #DC2626; font-weight: 800; padding: 8px 14px; border-radius: 8px; font-size: 0.9rem;">
                                                <i data-lucide="x-circle" style="width:16px; height:16px;"></i> FAIL (Backlog)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($isDone): ?>
                                <!-- Section A Summary -->
                                <div style="font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                    Section A Questions (10 Mandatory &bull; 3 Marks each):
                                </div>
                                <div class="marks-grid-10">
                                    <?php for ($q = 1; $q <= 10; $q++): ?>
                                        <div class="marks-box">
                                            <div style="font-size:0.68rem; color:#64748B;">Q<?php echo $q; ?></div>
                                            <div style="font-size:0.95rem; font-weight:800; color:#4F46E5;">
                                                <?php echo number_format((float)($secA[$q-1] ?? 0), 1); ?>
                                            </div>
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
                                <div style="font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                    Section B Questions (8 Available &bull; Best 5 Graded &bull; Max 50 Marks):
                                </div>
                                <div class="marks-grid-8">
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
                                            <div style="font-size:0.95rem; font-weight:800; color:<?php echo $isCounted ? '#059669' : '#4F46E5'; ?>;">
                                                <?php echo number_format($score, 1); ?>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <!-- Score Breakdown Strip -->
                                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-top: 14px;">
                                    <div style="font-size: 0.9rem; color: #334155;">
                                        External Score: <strong><?php echo number_format((float)($subj['external_score'] ?? 0), 1); ?> / 80</strong> &nbsp;&bull;&nbsp;
                                        Internal Marks: <strong><?php echo number_format((float)($subj['internal_marks'] ?? 0), 1); ?> / 20</strong>
                                    </div>
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #1E1B4B;">
                                        Total Score: <?php echo number_format($total, 1); ?> / 100
                                    </div>
                                    <div class="no-print">
                                        <button type="button" onclick="openReevalModal(<?php echo $subj['paper_id']; ?>, '<?php echo htmlspecialchars($subj['subject_code']); ?>', '<?php echo htmlspecialchars($subj['subject_name'] ?? ''); ?>')" class="btn-secondary" style="padding: 7px 14px; font-size: 0.82rem; font-weight: 700; cursor: pointer; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                            <i data-lucide="rotate-ccw" style="width:14px; height:14px;"></i> Request Re-Evaluation
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </main>

    <!-- RE-EVALUATION MODAL -->
    <div id="reevalModal" class="modal-backdrop">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="shield-alert" style="width:22px; height:22px; color:#4F46E5;"></i>
                    <h3 style="margin:0; font-size: 1.2rem; font-weight:800;">Apply for Re-Evaluation</h3>
                </div>
                <button type="button" onclick="closeReevalModal()" style="background:none; border:none; cursor:pointer; color:#64748B;">
                    <i data-lucide="x" style="width:20px; height:20px;"></i>
                </button>
            </div>

            <p style="font-size: 0.88rem; color: #64748B; margin-top: 0;">
                Your answer script will be reassigned to an independent second evaluator under blind re-evaluation protocols.
            </p>

            <form id="reevalForm" onsubmit="submitReeval(event)">
                <input type="hidden" id="modalPaperId" name="paper_id">
                <input type="hidden" id="modalStudentPin" name="student_pin" value="<?php echo htmlspecialchars($pin); ?>">

                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; margin-bottom:6px; color:#334155;">Subject Details</label>
                    <input type="text" id="modalSubjectInfo" readonly style="width:100%; padding:10px; background:#F1F5F9; border:1px solid #CBD5E1; border-radius:8px; font-weight:700; color:#1E293B;">
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; margin-bottom:6px; color:#334155;">Reason for Grievance / Re-Evaluation <span style="color:#EF4444;">*</span></label>
                    <textarea id="modalReason" name="reason" rows="4" required placeholder="Please describe specific questions or concerns regarding the evaluation..." style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:8px; font-family:inherit; font-size:0.88rem;"></textarea>
                </div>

                <div id="reevalMsg" style="display:none; padding:10px; border-radius:8px; font-size:0.85rem; margin-bottom:14px;"></div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeReevalModal()" class="btn-secondary" style="padding:8px 16px; border-radius:8px;">Cancel</button>
                    <button type="submit" id="btnSubmitReeval" class="btn-primary" style="padding:8px 18px; border-radius:8px; font-weight:700;">Submit Petition</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ORBY AI MASCOT & GUIDANCE SCRIPT -->
    <script src="animations.js"></script>

    <script>
        // Lucide Icons Initialization
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Re-evaluation Modal Logic
        function openReevalModal(paperId, subjectCode, subjectName) {
            document.getElementById('modalPaperId').value = paperId;
            document.getElementById('modalSubjectInfo').value = subjectName + ' (' + subjectCode + ')';
            document.getElementById('modalReason').value = '';
            document.getElementById('reevalMsg').style.display = 'none';
            document.getElementById('reevalModal').style.display = 'flex';
        }

        function closeReevalModal() {
            document.getElementById('reevalModal').style.display = 'none';
        }

        async function submitReeval(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReeval');
            const msgBox = document.getElementById('reevalMsg');
            const paperId = document.getElementById('modalPaperId').value;
            const studentPin = document.getElementById('modalStudentPin').value;
            const reason = document.getElementById('modalReason').value.trim();

            if (!reason) {
                alert('Please state a reason for re-evaluation.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Submitting...';

            try {
                const formData = new FormData();
                formData.append('paper_id', paperId);
                formData.append('student_pin', studentPin);
                formData.append('reason', reason);

                const res = await fetch('apply_reeval.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                msgBox.style.display = 'block';
                if (data.success) {
                    msgBox.style.background = '#ECFDF5';
                    msgBox.style.color = '#065F46';
                    msgBox.style.border = '1px solid #A7F3D0';
                    msgBox.innerText = data.message || 'Re-evaluation request submitted successfully!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1400);
                } else {
                    msgBox.style.background = '#FEF2F2';
                    msgBox.style.color = '#991B1B';
                    msgBox.style.border = '1px solid #FCA5A5';
                    msgBox.innerText = data.error || 'Failed to submit application.';
                    btn.disabled = false;
                    btn.innerText = 'Submit Petition';
                }
            } catch (err) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#FEF2F2';
                msgBox.style.color = '#991B1B';
                msgBox.innerText = 'Network error. Please try again.';
                btn.disabled = false;
                btn.innerText = 'Submit Petition';
            }
        }
    </script>
</body>
</html>
