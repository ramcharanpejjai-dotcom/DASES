<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';
require_once 'audit.php';

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$successCount = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_files'])) {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $semester     = trim($_POST['semester'] ?? 'Sem-1');
    if (!in_array($semester, ['Sem-1', 'Sem-3', 'Sem-4', 'Sem-5'])) {
        $semester = 'Sem-1';
    }
    $pins_text    = trim($_POST['pins_text'] ?? '');

    // Parse list of PINs (separated by newline or commas)
    $pins = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $pins_text)));

    if (empty($subject_name) || empty($subject_code)) {
        header("Location: admin-dashboard.php?tab=upload&error=missing_subject");
        exit();
    }

    $files = $_FILES['pdf_files'];
    $fileCount = count($files['name']);

    if ($fileCount === 0 || empty($files['name'][0])) {
        header("Location: admin-dashboard.php?tab=upload&error=no_files");
        exit();
    }

    for ($i = 0; $i < $fileCount; $i++) {
        $originalName = $files['name'][$i];
        $tmpName      = $files['tmp_name'][$i];
        $fileSize     = $files['size'][$i];
        $fileError    = $files['error'][$i];

        // Determine Student PIN for this item
        $studentPin = !empty($pins[$i]) ? $pins[$i] : pathinfo($originalName, PATHINFO_FILENAME);

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file '$originalName'.";
            continue;
        }

        // Validate size (max 25MB)
        if ($fileSize > 25 * 1024 * 1024) {
            $errors[] = "File '$originalName' exceeds 25MB limit.";
            continue;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            $errors[] = "File '$originalName' is not a valid PDF document.";
            continue;
        }

        // Unique paper code
        do {
            $paperCode = 'P-' . strtoupper(bin2hex(random_bytes(3)));
            $chk = $pdo->prepare("SELECT id FROM papers WHERE paper_code = ?");
            $chk->execute([$paperCode]);
        } while ($chk->fetch());

        $newFileName = time() . '_' . $paperCode . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.pdf';
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $relativeFilePath = 'uploads/' . $newFileName;
            $dummy_token = 'ANON-' . strtoupper(substr(md5($paperCode . 'dases_salt'), 0, 6));
            $ins = $pdo->prepare("
                INSERT INTO papers (paper_code, student_pin, dummy_token, subject_name, subject_code, semester, file_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $ins->execute([$paperCode, $studentPin, $dummy_token, $subject_name, $subject_code, $semester, $relativeFilePath]);
            $successCount++;
        } else {
            $errors[] = "Failed to save file '$originalName'.";
        }
    }

    logAudit("BULK_UPLOAD", "Bulk uploaded $successCount paper(s) for subject $subject_code");
    header("Location: admin-dashboard.php?tab=manage&msg=bulk_uploaded&count=$successCount");
    exit();
}
