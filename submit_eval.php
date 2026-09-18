<?php
require_once 'auth.php';
checkAccess('teacher');
require_once 'db.php';
require_once 'audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paper_id   = intval($_POST['paper_id'] ?? 0);
    $teacher_id = $_SESSION['user_id'] ?? 0;
    $feedback   = trim($_POST['feedback'] ?? '');
    $annotations = $_POST['annotations_data'] ?? null;
    $is_draft   = isset($_POST['is_draft']) && $_POST['is_draft'] === '1';

    if ($paper_id <= 0 || $teacher_id <= 0) {
        if ($is_draft) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
        die("Error: Invalid submission parameters.");
    }

    // 1. Authorization & IDOR Check: Ensure paper is assigned to this teacher
    $stmt = $pdo->prepare("SELECT id, paper_code, status, student_pin, subject_name, subject_code FROM papers WHERE id = ? AND assigned_teacher_id = ?");
    $stmt->execute([$paper_id, $teacher_id]);
    $paper = $stmt->fetch();

    if (!$paper) {
        if ($is_draft) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        die("Error: Unauthorized. Paper is not assigned to you.");
    }

    // 2. Calculate Section A (10 questions, Max 3M each, Max 30M total)
    $raw_3m = $_POST['q_3m'] ?? [];
    $secA_sanitized = [];
    $totalA = 0.0;

    for ($i = 0; $i < 10; $i++) {
        $val = isset($raw_3m[$i]) ? floatval($raw_3m[$i]) : 0.0;
        $val = max(0.0, min(3.0, $val));
        $secA_sanitized[] = $val;
        $totalA += $val;
    }
    if ($totalA > 30.0) {
        $totalA = 30.0;
    }

    // 3. Calculate Section B (8 questions, Max 10M each | Best 5 of 8 counted | Max 50M total)
    $raw_10m = $_POST['q_10m'] ?? [];
    $secB_sanitized = [];

    for ($i = 0; $i < 8; $i++) {
        $val = isset($raw_10m[$i]) ? floatval($raw_10m[$i]) : 0.0;
        $val = max(0.0, min(10.0, $val));
        $secB_sanitized[] = $val;
    }

    // Best 5 calculation: pick top 5 highest marks among all 8 questions
    $secB_sorted = $secB_sanitized;
    rsort($secB_sorted, SORT_NUMERIC);
    $best5_secB = array_slice($secB_sorted, 0, 5);
    $totalB = min(50.0, (float)array_sum($best5_secB));

    // 4. Calculate Scores
    $external_score = min(80.0, $totalA + $totalB);
    $internal_marks = max(0.0, min(20.0, floatval($_POST['internal_marks'] ?? 0.0)));
    $total_score    = min(100, round($external_score + $internal_marks));

    $question_marks_json = json_encode([
        'q_3m' => $secA_sanitized,
        'q_10m' => $secB_sanitized,
        'total_sec_a' => $totalA,
        'total_sec_b' => $totalB
    ]);

    try {
        $pdo->beginTransaction();

        // Check if an evaluation already exists for this paper
        $checkEval = $pdo->prepare("SELECT id FROM evaluations WHERE paper_id = ?");
        $checkEval->execute([$paper_id]);
        $existingEval = $checkEval->fetch();

        if ($existingEval) {
            $updateEval = $pdo->prepare("
                UPDATE evaluations SET 
                    teacher_id = ?,
                    external_score = ?,
                    internal_marks = ?,
                    total_score = ?,
                    question_marks = ?,
                    annotations_data = ?,
                    feedback = ?,
                    evaluated_at = NOW()
                WHERE id = ?
            ");
            $updateEval->execute([
                $teacher_id,
                $external_score,
                $internal_marks,
                $total_score,
                $question_marks_json,
                $annotations,
                $feedback,
                $existingEval['id']
            ]);
        } else {
            $insertEval = $pdo->prepare("
                INSERT INTO evaluations (
                    paper_id, teacher_id, external_score, internal_marks, 
                    total_score, question_marks, annotations_data, feedback, evaluated_at
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertEval->execute([
                $paper_id,
                $teacher_id,
                $external_score,
                $internal_marks,
                $total_score,
                $question_marks_json,
                $annotations,
                $feedback
            ]);
        }

        if (!$is_draft) {
            $stmt2 = $pdo->prepare("UPDATE papers SET status = 'completed' WHERE id = ?");
            $stmt2->execute([$paper_id]);

            // If there's an active re-evaluation request for this paper, mark completed
            $reevalStmt = $pdo->prepare("UPDATE reevaluations SET status = 'completed' WHERE paper_id = ? AND status IN ('pending', 'approved')");
            $reevalStmt->execute([$paper_id]);

            // Notify Admin
            $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
            while ($admin = $adminStmt->fetch()) {
                sendNotification(
                    $admin['id'],
                    "Evaluation Completed",
                    "Teacher " . ($_SESSION['user_name'] ?? '') . " completed evaluation for Paper #{$paper_id} ({$paper['subject_name']}) with Score: {$total_score}/100.",
                    "admin-dashboard.php?tab=papers"
                );
            }

            // Send Acceptance Email & Notification to Teacher
            $tStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $tStmt->execute([$teacher_id]);
            $evalTeacher = $tStmt->fetch();
            if ($evalTeacher) {
                sendTeacherEvaluationAcceptedEmail($evalTeacher['email'], $evalTeacher['name'], [
                    'paper_id'       => $paper_id,
                    'paper_code'     => $paper['paper_code'] ?? "Paper #$paper_id",
                    'subject_name'   => $paper['subject_name'] ?? 'Subject',
                    'subject_code'   => $paper['subject_code'] ?? '',
                    'total_sec_a'    => $totalA,
                    'total_sec_b'    => $totalB,
                    'internal_marks' => $internal_marks,
                    'total_score'    => $total_score
                ]);

                sendNotification(
                    $teacher_id,
                    "Paper Question Evaluation Accepted",
                    "Your question evaluation for {$paper['subject_name']} (" . ($paper['paper_code'] ?? "Paper #$paper_id") . ") has been accepted. Total Score: {$total_score}/100.",
                    "view-marksheet.php?paper_id={$paper_id}"
                );
            }

            logAudit("EVALUATION_COMPLETED", "Evaluated Paper #{$paper_id} ({$paper['subject_code']}) with Total Score: {$total_score}/100");
        }

        $pdo->commit();

        if ($is_draft) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Draft saved automatically.']);
            exit;
        }

        header("Location: teacher-dashboard.php?tab=completed&msg=evaluated");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($is_draft) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        die("Failed to submit evaluation: " . $e->getMessage());
    }
} else {
    header("Location: teacher-dashboard.php");
    exit();
}