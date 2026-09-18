<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'mailer.php';

/**
 * Log an action to the audit_logs table
 */
function logAudit($action, $details = '') {
    global $pdo;
    try {
        $userId   = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? 'System';
        $userRole = $_SESSION['user_role'] ?? 'guest';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, user_name, user_role, action, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $userName, $userRole, $action, $details, $ip]);
    } catch (Exception $e) {
        // Suppress logging error to avoid breaking main workflow
    }
}

/**
 * Send an in-system notification and deliver formatted HTML email
 */
function sendNotification($userId, $title, $message, $link = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, link)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $link]);

        // Attempt formatted email delivery
        $userStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        if ($user && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $fullLink = !empty($link) ? (getDasesBaseUrl() . '/' . ltrim($link, '/')) : '';
            $html = buildDasesEmailTemplate([
                'title'          => $title,
                'badge_text'     => 'Portal Notification',
                'badge_bg'       => '#4F46E5',
                'recipient_name' => $user['name'] ?? 'Faculty/User',
                'heading'        => $title,
                'intro'          => '<p>' . htmlspecialchars($message) . '</p>',
                'button_text'    => !empty($fullLink) ? 'View in Portal' : '',
                'button_url'     => $fullLink
            ]);
            sendDasesEmail($user['email'], $user['name'] ?? 'User', "DASES Notification: $title", $html, $message);
        }
    } catch (Exception $e) {
        // Ignore notification errors
    }
}
