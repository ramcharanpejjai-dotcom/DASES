<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paper_id   = intval($_POST['paper_id'] ?? 0);
    $teacher_id = intval($_POST['teacher_id'] ?? 0);
    $redirect_tab = ($_POST['redirect_tab'] ?? '') === 'assign' ? 'assign' : 'manage';

    if ($paper_id > 0 && $teacher_id > 0) {
        try {
            // Check if teacher exists
            $tStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'teacher'");
            $tStmt->execute([$teacher_id]);
            $teacher = $tStmt->fetch();

            if (!$teacher) {
                die("Error: Selected teacher does not exist.");
            }

            // Fetch paper details
            $pStmt = $pdo->prepare("SELECT paper_code, subject_name, subject_code, student_pin, dummy_token FROM papers WHERE id = ?");
            $pStmt->execute([$paper_id]);
            $paper = $pStmt->fetch();
            $paperCode = $paper['paper_code'] ?? "Paper #$paper_id";

            // Assign or reassign paper
            $stmt = $pdo->prepare("UPDATE papers SET assigned_teacher_id = ?, status = 'pending' WHERE id = ?");
            $stmt->execute([$teacher_id, $paper_id]);

            require_once 'audit.php';
            logAudit("PAPER_ASSIGNED", "Assigned $paperCode to {$teacher['name']}");

            // Send dedicated HTML assignment email to teacher
            sendTeacherPaperAssignedEmail($teacher['email'], $teacher['name'], [
                'paper_id'     => $paper_id,
                'paper_code'   => $paperCode,
                'subject_name' => $paper['subject_name'] ?? 'Subject',
                'subject_code' => $paper['subject_code'] ?? '',
                'candidate_id' => $paper['dummy_token'] ?: ($paper['student_pin'] ?: 'Anonymized Candidate')
            ]);

            // In-system portal notification
            sendNotification(
                $teacher_id,
                "New Paper Assigned: $paperCode",
                "You have been assigned to evaluate answer script ($paperCode - " . ($paper['subject_name'] ?? '') . "). Please log in to your portal to begin question-by-question evaluation.",
                "teacher-eval.php?paper_id=" . $paper_id
            );

            header("Location: admin-dashboard.php?tab={$redirect_tab}&msg=assigned");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Error: Invalid paper or teacher selected.");
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>