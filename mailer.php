<?php
/**
 * DASES Unified Mailer & Notification Service
 * Supports professional HTML emails, RFC-compliant MIME headers,
 * audit logging to email_logs, and seamless local fallback.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

/**
 * Get base URL for absolute links in emails
 */
function getDasesBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '' || $dir === '.') {
        $dir = '/DASES';
    }
    return $protocol . $host . $dir;
}

/**
 * Send an email via PHP mail() with proper HTML MIME headers and log to database
 */
function sendDasesEmail($toEmail, $toName, $subject, $htmlContent, $plainText = '') {
    global $pdo;

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (empty($plainText)) {
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $htmlContent));
        $plainText = trim(preg_replace("/[\r\n]+/", "\n", $plainText));
    }

    $systemName = "DASES Examination System";
    $fromEmail  = "no-reply@dases.edu";

    // Build standard MIME headers for HTML email
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $systemName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $headerStr = implode("\r\n", $headers);

    $sent = false;
    $errorMsg = null;

    try {
        // Attempt PHP mail()
        $sent = @mail($toEmail, $subject, $htmlContent, $headerStr);
        if (!$sent) {
            $lastErr = error_get_last();
            $errorMsg = $lastErr['message'] ?? 'Mail server offline on localhost (XAMPP default)';
        }
    } catch (Throwable $t) {
        $errorMsg = $t->getMessage();
    }

    // Always log to email_logs table for traceability & local testing
    try {
        if ($pdo) {
            $status = $sent ? 'sent' : 'logged';
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (recipient_email, recipient_name, subject, body_text, body_html, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([$toEmail, $toName, $subject, $plainText, $htmlContent, $status, $errorMsg]);
        }
    } catch (Throwable $e) {
        // Suppress logging error to prevent breaking core execution
    }

    return $sent;
}

/**
 * Build a modern responsive HTML email matching DASES design aesthetics
 */
function buildDasesEmailTemplate($options) {
    $title         = htmlspecialchars($options['title'] ?? 'DASES Notification');
    $badgeText     = htmlspecialchars($options['badge_text'] ?? 'Notification');
    $badgeBg       = htmlspecialchars($options['badge_bg'] ?? '#4F46E5');
    $recipientName = htmlspecialchars($options['recipient_name'] ?? 'Faculty Member');
    $heading       = htmlspecialchars($options['heading'] ?? $title);
    $intro         = $options['intro'] ?? '';
    $details       = $options['details'] ?? []; // Key-value pairs
    $notes         = $options['notes'] ?? '';
    $buttonText    = htmlspecialchars($options['button_text'] ?? '');
    $buttonUrl     = htmlspecialchars($options['button_url'] ?? '');
    $footerNote    = htmlspecialchars($options['footer_note'] ?? 'This is an automated system dispatch from DASES. Please do not reply directly to this email.');

    $detailsHtml = '';
    if (!empty($details)) {
        $detailsHtml .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #F8FAFC; border-radius: 10px; border: 1px solid #E2E8F0; overflow: hidden;">';
        foreach ($details as $k => $v) {
            $detailsHtml .= '<tr>';
            $detailsHtml .= '<td style="padding: 10px 16px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #E2E8F0; width: 38%;">' . htmlspecialchars($k) . '</td>';
            $detailsHtml .= '<td style="padding: 10px 16px; font-size: 13px; color: #0F172A; border-bottom: 1px solid #E2E8F0; font-weight: 600;">' . htmlspecialchars($v) . '</td>';
            $detailsHtml .= '</tr>';
        }
        $detailsHtml .= '</table>';
    }

    $buttonHtml = '';
    if ($buttonText && $buttonUrl) {
        $buttonHtml = '
            <div style="text-align: center; margin: 28px 0 16px 0;">
                <a href="' . $buttonUrl . '" target="_blank" style="display: inline-block; padding: 13px 28px; background: linear-gradient(135deg, #4F46E5, #6366F1); color: #FFFFFF; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 10px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);">
                    ' . $buttonText . ' &rarr;
                </a>
            </div>
            <div style="text-align: center; font-size: 11px; color: #94A3B8; margin-bottom: 16px;">
                Direct Link: <a href="' . $buttonUrl . '" style="color: #6366F1; word-break: break-all;">' . $buttonUrl . '</a>
            </div>';
    }

    $notesHtml = '';
    if ($notes) {
        $notesHtml = '
            <div style="margin: 18px 0; padding: 14px 16px; background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 6px; font-size: 13px; color: #92400E;">
                ' . $notes . '
            </div>';
    }

    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #F1F5F9; padding: 35px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 600px; width: 100%; border-collapse: collapse; background: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); border: 1px solid #E2E8F0;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="padding: 26px 32px; background: linear-gradient(135deg, #1E1B4B 0%, #0F172A 100%); color: #FFFFFF;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td>
                                        <div style="display: inline-block; width: 34px; height: 34px; line-height: 34px; text-align: center; border-radius: 8px; background: linear-gradient(135deg, #6366F1, #4F46E5); font-weight: 800; font-size: 16px; color: #FFF; vertical-align: middle;">
                                            D
                                        </div>
                                        <span style="font-size: 18px; font-weight: 800; letter-spacing: -0.5px; vertical-align: middle; margin-left: 10px; color: #FFFFFF;">
                                            DASES
                                        </span>
                                        <span style="font-size: 12px; color: #94A3B8; vertical-align: middle; margin-left: 6px;">
                                            Examination Evaluation System
                                        </span>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #FFFFFF; background-color: ' . $badgeBg . ';">
                                            ' . $badgeText . '
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 32px 24px 32px;">
                            <h2 style="margin: 0 0 12px 0; font-size: 19px; font-weight: 800; color: #0F172A; letter-spacing: -0.3px;">
                                ' . $heading . '
                            </h2>
                            <p style="margin: 0 0 16px 0; font-size: 14px; color: #475569; line-height: 1.5;">
                                Dear <strong>' . $recipientName . '</strong>,
                            </p>
                            <div style="font-size: 14px; color: #334155; line-height: 1.6;">
                                ' . $intro . '
                            </div>

                            ' . $detailsHtml . '
                            ' . $notesHtml . '
                            ' . $buttonHtml . '

                            <div style="margin-top: 26px; padding-top: 18px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #64748B; line-height: 1.5;">
                                If you have questions or encounter any issues, please reach out to the university examination branch or your system administrator.
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px; background: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94A3B8; line-height: 1.4;">
                                ' . $footerNote . '<br>
                                &copy; ' . date('Y') . ' DASES - Digital Answer Sheet Evaluation System. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Send email to Teacher when a student's Paper Question Grievance is Accepted & Approved by Admin
 */
function sendTeacherPaperQuestionAcceptedEmail($teacherEmail, $teacherName, $data) {
    $baseUrl = getDasesBaseUrl();
    $evalUrl = $baseUrl . '/teacher-eval.php?paper_id=' . intval($data['paper_id']);

    $subject = "[DASES] Paper Question Grievance Accepted - Assigned for Re-Evaluation (#REEV-{$data['reeval_id']})";

    $intro = "<p>The examination administration has <strong>accepted and approved</strong> a student's paper question grievance request. This paper has been assigned to you for secondary evaluation and verification of the questioned marks.</p>";

    $details = [
        'Grievance Request ID' => '#REEV-' . $data['reeval_id'],
        'Paper Code'           => $data['paper_code'] ?? 'Paper #' . $data['paper_id'],
        'Subject'              => ($data['subject_name'] ?? '') . ' (' . ($data['subject_code'] ?? '') . ')',
        'Student PIN / Token'  => $data['display_student'] ?? 'Anonymous Student',
        'Questioned Reason'    => $data['reason'] ?? 'Recounting / Question marks verification requested',
        'Decision Status'      => 'Approved for Re-Evaluation'
    ];

    $notes = "<strong>Admin Review Remarks:</strong> " . htmlspecialchars($data['admin_notes'] ?? 'Approved for secondary evaluator review.');

    $html = buildDasesEmailTemplate([
        'title'          => 'Paper Question Grievance Accepted',
        'badge_text'     => 'Question Accepted',
        'badge_bg'       => '#059669', // Emerald
        'recipient_name' => $teacherName,
        'heading'        => 'Paper Question Grievance Accepted & Reassigned',
        'intro'          => $intro,
        'details'        => $details,
        'notes'          => $notes,
        'button_text'    => 'Open Digital Evaluation Studio',
        'button_url'     => $evalUrl,
        'footer_note'    => 'Please conduct an unbiased double-blind re-evaluation of the student script and submit updated scores.'
    ]);

    return sendDasesEmail($teacherEmail, $teacherName, $subject, $html);
}

/**
 * Send email to Teacher when a paper is newly assigned for question evaluation
 */
function sendTeacherPaperAssignedEmail($teacherEmail, $teacherName, $data) {
    $baseUrl = getDasesBaseUrl();
    $evalUrl = $baseUrl . '/teacher-eval.php?paper_id=' . intval($data['paper_id']);

    $paperCode = $data['paper_code'] ?? 'Paper #' . $data['paper_id'];
    $subject = "[DASES] New Paper Assigned for Question Evaluation - {$paperCode}";

    $intro = "<p>You have been assigned a new digital answer script for question-by-question evaluation in the DASES portal.</p>";

    $details = [
        'Paper Code'   => $paperCode,
        'Subject'      => ($data['subject_name'] ?? '') . ' (' . ($data['subject_code'] ?? '') . ')',
        'Candidate ID' => $data['candidate_id'] ?? 'Double-Blind Anonymized',
        'Status'       => 'Pending Evaluation'
    ];

    $html = buildDasesEmailTemplate([
        'title'          => 'New Paper Assigned for Evaluation',
        'badge_text'     => 'Paper Assigned',
        'badge_bg'       => '#7C3AED', // Purple
        'recipient_name' => $teacherName,
        'heading'        => 'New Script Assigned for Question Marking',
        'intro'          => $intro,
        'details'        => $details,
        'button_text'    => 'Start Evaluating Paper',
        'button_url'     => $evalUrl,
        'footer_note'    => 'Mark Section A and Section B questions in the Digital Studio. Best 5 questions of Section B will be calculated automatically.'
    ]);

    return sendDasesEmail($teacherEmail, $teacherName, $subject, $html);
}

/**
 * Send email to Teacher confirming their submitted paper question evaluation has been accepted & saved
 */
function sendTeacherEvaluationAcceptedEmail($teacherEmail, $teacherName, $data) {
    $baseUrl = getDasesBaseUrl();
    $marksheetUrl = $baseUrl . '/view-marksheet.php?paper_id=' . intval($data['paper_id']);

    $paperCode = $data['paper_code'] ?? 'Paper #' . $data['paper_id'];
    $subject = "[DASES] Paper Questions Evaluation Accepted - {$paperCode}";

    $intro = "<p>Your question-wise marks evaluation for paper <strong>" . htmlspecialchars($paperCode) . "</strong> has been successfully accepted and committed to the examination database.</p>";

    $details = [
        'Paper Code'     => $paperCode,
        'Subject'        => ($data['subject_name'] ?? '') . ' (' . ($data['subject_code'] ?? '') . ')',
        'Section A (30)' => number_format((float)($data['total_sec_a'] ?? 0), 1) . ' / 30',
        'Section B (50)' => number_format((float)($data['total_sec_b'] ?? 0), 1) . ' / 50 (Best 5 Questions)',
        'Internal (20)'  => number_format((float)($data['internal_marks'] ?? 0), 1) . ' / 20',
        'Grand Total'    => number_format((float)($data['total_score'] ?? 0), 1) . ' / 100',
        'Result Status'  => (float)($data['total_score'] ?? 0) >= 35 ? 'PASS' : 'FAIL',
        'Submission Time'=> date('d M Y, h:i A')
    ];

    $html = buildDasesEmailTemplate([
        'title'          => 'Paper Question Evaluation Accepted',
        'badge_text'     => 'Evaluation Accepted',
        'badge_bg'       => '#059669', // Emerald
        'recipient_name' => $teacherName,
        'heading'        => 'Question Evaluation Successfully Accepted',
        'intro'          => $intro,
        'details'        => $details,
        'button_text'    => 'View Final Marksheet',
        'button_url'     => $marksheetUrl,
        'footer_note'    => 'This evaluation is now locked and submitted for administrative moderation and result tabulation.'
    ]);

    return sendDasesEmail($teacherEmail, $teacherName, $subject, $html);
}

/**
 * Send password reset email with secure 1-hour token link
 */
function sendPasswordResetEmail($userEmail, $userName, $resetUrl) {
    $subject = "DASES - Password Reset Request";

    $intro = "<p>We received a request to reset the password for your DASES examination portal account. Click the button below to set a new password. This security link will remain active for <strong>1 hour</strong>.</p>";

    $notes = "<strong>Security Warning:</strong> If you did not initiate this password reset request, no action is required. Your password remains safe.";

    $details = [
        'Account Email'  => $userEmail,
        'Token Validity' => '1 Hour from generation',
        'Request Origin' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ];

    $html = buildDasesEmailTemplate([
        'title'          => 'DASES Password Reset Request',
        'badge_text'     => 'Security Alert',
        'badge_bg'       => '#2563EB', // Blue
        'recipient_name' => $userName,
        'heading'        => 'Reset Your DASES Password',
        'intro'          => $intro,
        'details'        => $details,
        'notes'          => $notes,
        'button_text'    => 'Set New Password',
        'button_url'     => $resetUrl,
        'footer_note'    => 'Never share this reset link with anyone. DASES administrators will never ask for your password.'
    ]);

    return sendDasesEmail($userEmail, $userName, $subject, $html);
}
