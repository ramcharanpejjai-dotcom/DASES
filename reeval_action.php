<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';
require_once 'audit.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$reevalId  = intval($_POST['reeval_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$adminNote = trim($_POST['admin_notes'] ?? '');
$newTeacherId = intval($_POST['new_teacher_id'] ?? 0);

if ($reevalId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid re-evaluation ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.*, p.paper_code, p.dummy_token, p.student_pin, p.subject_name, p.file_path 
    FROM reevaluations r 
    JOIN papers p ON r.paper_id = p.id 
    WHERE r.id = ?
");
$stmt->execute([$reevalId]);
$reeval = $stmt->fetch();

if (!$reeval) {
    echo json_encode(['success' => false, 'error' => 'Re-evaluation record not found']);
    exit;
}

try {
    if ($action === 'approve_reassign') {
        if ($newTeacherId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Please select a new evaluator to reassign this paper.']);
            exit;
        }

        // Fetch new teacher details
        $tStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'teacher'");
        $tStmt->execute([$newTeacherId]);
        $teacher = $tStmt->fetch();
        if (!$teacher) {
            echo json_encode(['success' => false, 'error' => 'Selected teacher was not found.']);
            exit;
        }

        // Update paper status back to pending and assign to new teacher
        $pStmt = $pdo->prepare("UPDATE papers SET assigned_teacher_id = ?, status = 'pending' WHERE id = ?");
        $pStmt->execute([$newTeacherId, $reeval['paper_id']]);

        // Update reevaluation status to approved
        $finalNote = $adminNote ?: 'Approved for re-evaluation by secondary evaluator.';
        $rStmt = $pdo->prepare("UPDATE reevaluations SET status = 'approved', admin_notes = ? WHERE id = ?");
        $rStmt->execute([$finalNote, $reevalId]);

        // Check anonymous evaluation mode
        $anonSettingStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'anonymous_evaluation'");
        $isAnonymous = ($anonSettingStmt->fetchColumn() === '1');
        $displayStudent = $isAnonymous 
            ? ($reeval['dummy_token'] ?: ('ANON-' . strtoupper(substr(md5($reeval['paper_id']), 0, 6)))) 
            : $reeval['student_pin'];

        // 1. Send dedicated HTML email to teacher for acceptance of paper question grievance
        sendTeacherPaperQuestionAcceptedEmail($teacher['email'], $teacher['name'], [
            'reeval_id'       => $reevalId,
            'paper_id'        => $reeval['paper_id'],
            'paper_code'      => $reeval['paper_code'] ?? ('Paper #' . $reeval['paper_id']),
            'subject_name'    => $reeval['subject_name'],
            'subject_code'    => $reeval['subject_code'],
            'display_student' => $displayStudent,
            'reason'          => $reeval['reason'],
            'admin_notes'     => $finalNote
        ]);

        // 2. In-system portal notification
        sendNotification(
            $newTeacherId,
            "Paper Question Grievance Accepted (#REEV-{$reevalId})",
            "A student paper question grievance for {$reeval['subject_name']} ({$reeval['subject_code']}) has been accepted by admin and assigned to you for re-evaluation. Reason: " . $reeval['reason'],
            "teacher-eval.php?paper_id=" . $reeval['paper_id']
        );

        logAudit("REEVAL_APPROVED", "Admin approved paper question grievance #{$reevalId} and reassigned paper #{$reeval['paper_id']} to {$teacher['name']}");

        echo json_encode(['success' => true, 'message' => 'Paper question grievance approved and email notification dispatched to ' . htmlspecialchars($teacher['name']) . '!']);
        exit;
    }

    if ($action === 'reject') {
        $rStmt = $pdo->prepare("UPDATE reevaluations SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $rStmt->execute([$adminNote ?: 'Re-evaluation request rejected after initial marks verification.', $reevalId]);

        logAudit("REEVAL_REJECTED", "Admin rejected re-evaluation #{$reevalId}");

        echo json_encode(['success' => true, 'message' => 'Re-evaluation request marked as rejected.']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Action failed: ' . $e->getMessage()]);
}
