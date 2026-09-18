<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_pin  = trim($_POST['student_pin'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    
    if (empty($student_pin)) {
        die("Error: Student PIN / Roll Number is required.");
    }
    if (empty($subject_name) || empty($subject_code)) {
        die("Error: Subject Name and Subject Code are required.");
    }

    if (!isset($_FILES['pdf_script']) || $_FILES['pdf_script']['error'] !== UPLOAD_ERR_OK) {
        die("Error: Please select a valid PDF file to upload.");
    }

    // Check file size (Max 25MB)
    if ($_FILES['pdf_script']['size'] > 25 * 1024 * 1024) {
        die("Error: Uploaded file exceeds the 25MB size limit.");
    }

    $fileTmpPath   = $_FILES['pdf_script']['tmp_name'];
    $fileName      = $_FILES['pdf_script']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'pdf') {
        die("Error: Only PDF files are permitted.");
    }

    // Verify MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf' && $mimeType !== 'application/x-pdf') {
        die("Error: File is not a valid PDF document.");
    }

    // Generate unique collision-resistant paper code
    do {
        $paper_code = 'P-' . rand(10000, 99999);
        $checkStmt = $pdo->prepare("SELECT id FROM papers WHERE paper_code = ?");
        $checkStmt->execute([$paper_code]);
    } while ($checkStmt->fetch());

    $newFileName = $paper_code . '_' . time() . '.pdf';
    $uploadDir   = './uploads/';

    // Ensure uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $dest_path = $uploadDir . $newFileName;

    $semester     = trim($_POST['semester'] ?? 'Sem-1');
    if (!in_array($semester, ['Sem-1', 'Sem-3', 'Sem-4', 'Sem-5'])) {
        $semester = 'Sem-1';
    }

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        require_once 'audit.php';
        $dummy_token = 'ANON-' . strtoupper(substr(md5($paper_code . 'dases_salt'), 0, 6));
        $stmt = $pdo->prepare("
            INSERT INTO papers (paper_code, student_pin, dummy_token, subject_name, subject_code, semester, file_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$paper_code, $student_pin, $dummy_token, $subject_name, $subject_code, $semester, $dest_path]);

        logAudit("PAPER_UPLOAD", "Uploaded paper $paper_code ($subject_code - $semester) for PIN: $student_pin");
        header("Location: admin-dashboard.php?tab=manage&msg=uploaded");
        exit();
    } else {
        die("Error: Failed to save uploaded file.");
    }
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>