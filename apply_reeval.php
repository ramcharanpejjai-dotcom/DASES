<?php
require_once 'db.php';
require_once 'audit.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$paperId    = intval($_POST['paper_id'] ?? 0);
$studentPin = trim($_POST['student_pin'] ?? '');
$reason     = trim($_POST['reason'] ?? '');

if ($paperId <= 0 || empty($studentPin) || empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'All fields (Paper, PIN, and Reason) are required.']);
    exit;
}

// Verify paper exists and belongs to the PIN
$stmt = $pdo->prepare("SELECT id, subject_code, subject_name FROM papers WHERE id = ? AND student_pin = ? AND status = 'completed'");
$stmt->execute([$paperId, $studentPin]);
$paper = $stmt->fetch();

if (!$paper) {
    echo json_encode(['success' => false, 'error' => 'Completed paper record not found for this Roll Number.']);
    exit;
}

// Check if an active re-evaluation request already exists
$checkStmt = $pdo->prepare("SELECT id, status FROM reevaluations WHERE paper_id = ? AND student_pin = ? AND status IN ('pending', 'approved')");
$checkStmt->execute([$paperId, $studentPin]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'A re-evaluation request for this subject is already pending review.']);
    exit;
}

try {
    $insertStmt = $pdo->prepare("
        INSERT INTO reevaluations (paper_id, student_pin, subject_code, reason, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $insertStmt->execute([$paperId, $studentPin, $paper['subject_code'], $reason]);
    $reevalId = $pdo->lastInsertId();

    // Notify admins
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    while ($admin = $adminStmt->fetch()) {
        sendNotification(
            $admin['id'],
            "New Re-evaluation Request",
            "Student PIN {$studentPin} submitted a grievance for {$paper['subject_name']} ({$paper['subject_code']}).",
            "admin-dashboard.php?tab=reevals"
        );
    }

    logAudit("REEVAL_APPLIED", "Student {$studentPin} applied for re-evaluation of paper #{$paperId} ({$paper['subject_code']})");

    echo json_encode([
        'success' => true,
        'message' => 'Your re-evaluation request has been submitted successfully! Tracking ID: REEV-' . $reevalId
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to submit request: ' . $e->getMessage()]);
}
