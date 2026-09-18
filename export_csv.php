<?php
require_once 'auth.php';
checkAccess('admin');
require_once 'db.php';

$filename = "DASES_Evaluation_Results_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, [
    'Sl. No',
    'Student PIN',
    'Paper Code',
    'Subject Code',
    'Subject Name',
    'Evaluator Name',
    'External Score (Max 80)',
    'Internal Marks (Max 20)',
    'Grand Total (Max 100)',
    'Evaluation Status',
    'Evaluation Date'
]);

$stmt = $pdo->query("
    SELECT 
        p.id,
        p.student_pin,
        p.paper_code,
        p.subject_code,
        p.subject_name,
        p.status,
        u.name AS teacher_name,
        e.external_score,
        e.internal_marks,
        e.total_score,
        e.evaluated_at
    FROM papers p
    LEFT JOIN users u ON p.assigned_teacher_id = u.id
    LEFT JOIN evaluations e ON p.id = e.paper_id
    ORDER BY p.id DESC
");

$sl = 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $sl++,
        $row['student_pin'] ?? 'N/A',
        $row['paper_code'],
        $row['subject_code'],
        $row['subject_name'] ?? 'N/A',
        $row['teacher_name'] ?? 'Unassigned',
        isset($row['external_score']) ? number_format((float)$row['external_score'], 1) : '-',
        isset($row['internal_marks']) ? number_format((float)$row['internal_marks'], 1) : '-',
        isset($row['total_score']) ? $row['total_score'] : '-',
        ucfirst($row['status']),
        !empty($row['evaluated_at']) ? date('d-m-Y H:i', strtotime($row['evaluated_at'])) : '-'
    ]);
}

fclose($output);
exit();
?>
