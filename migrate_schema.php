<?php
require_once 'db.php';

try {
    // 1. Create reevaluations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reevaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            paper_id INT NOT NULL,
            student_pin VARCHAR(50) NOT NULL,
            subject_code VARCHAR(50) NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
            admin_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (student_pin),
            INDEX (paper_id),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✓ reevaluations table verified/created.\n";

    // 2. Add annotations_data to evaluations table if not exists
    $cols = $pdo->query("DESCRIBE evaluations")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('annotations_data', $cols)) {
        $pdo->exec("ALTER TABLE evaluations ADD COLUMN annotations_data LONGTEXT NULL AFTER internal_marks;");
        echo "✓ annotations_data column added to evaluations.\n";
    } else {
        echo "✓ annotations_data column already exists in evaluations.\n";
    }

    // 3. Add dummy_token and is_moderated to papers table if not exists
    $paperCols = $pdo->query("DESCRIBE papers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('dummy_token', $paperCols)) {
        $pdo->exec("ALTER TABLE papers ADD COLUMN dummy_token VARCHAR(50) NULL AFTER student_pin;");
        echo "✓ dummy_token column added to papers.\n";
    }
    if (!in_array('is_moderated', $paperCols)) {
        $pdo->exec("ALTER TABLE papers ADD COLUMN is_moderated TINYINT(1) DEFAULT 0 AFTER status;");
        echo "✓ is_moderated column added to papers.\n";
    }
    if (!in_array('semester', $paperCols)) {
        $pdo->exec("ALTER TABLE papers ADD COLUMN semester VARCHAR(10) NOT NULL DEFAULT 'Sem-1' AFTER subject_code, ADD INDEX idx_papers_semester (semester);");
        echo "✓ semester column added to papers.\n";
    }

    // Populate dummy_token for existing papers if empty
    $papersWithoutToken = $pdo->query("SELECT id FROM papers WHERE dummy_token IS NULL OR dummy_token = ''")->fetchAll();
    $updateStmt = $pdo->prepare("UPDATE papers SET dummy_token = ? WHERE id = ?");
    foreach ($papersWithoutToken as $p) {
        $token = 'ANON-' . strtoupper(substr(md5($p['id'] . 'dases_salt'), 0, 6));
        $updateStmt->execute([$token, $p['id']]);
    }
    if (count($papersWithoutToken) > 0) {
        echo "✓ Generated anonymous dummy tokens for " . count($papersWithoutToken) . " papers.\n";
    }

    // 4. Create system_settings table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // 5. Create email_logs table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(255) NOT NULL,
            recipient_name VARCHAR(100) NULL,
            subject VARCHAR(255) NOT NULL,
            body_text TEXT NULL,
            body_html LONGTEXT NULL,
            status ENUM('sent', 'failed', 'logged') DEFAULT 'logged',
            error_message TEXT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (recipient_email),
            INDEX (status),
            INDEX (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "✓ email_logs table verified/created.\n";

    echo "\n=== ALL DATABASE MIGRATIONS COMPLETED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
