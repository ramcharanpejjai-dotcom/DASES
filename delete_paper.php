<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';

$paper_id = intval($_GET['id'] ?? ($_POST['paper_id'] ?? 0));

if ($paper_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM papers WHERE id = ?");
        $stmt->execute([$paper_id]);
        $paper = $stmt->fetch();

        if ($paper) {
            $pdo->beginTransaction();

            // Delete associated evaluations first
            $delEval = $pdo->prepare("DELETE FROM evaluations WHERE paper_id = ?");
            $delEval->execute([$paper_id]);

            // Delete paper record
            $delPaper = $pdo->prepare("DELETE FROM papers WHERE id = ?");
            $delPaper->execute([$paper_id]);

            $pdo->commit();

            // Delete physical file if exists
            if (!empty($paper['file_path']) && file_exists($paper['file_path'])) {
                @unlink($paper['file_path']);
            }

            header("Location: admin-dashboard.php?tab=manage&msg=deleted");
            exit();
        } else {
            die("Error: Paper not found.");
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: admin-dashboard.php?tab=manage");
    exit();
}
?>
