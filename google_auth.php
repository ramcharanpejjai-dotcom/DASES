<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

define('GOOGLE_CLIENT_ID', '149222864112-rik3fb29ti6mec0nlpd2os1p9uthbdgd.apps.googleusercontent.com');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['credential'])) {
    echo json_encode(['success' => false, 'message' => 'No token provided.']);
    exit();
}

$id_token = $input['credential'];

// Verify JWT token with Google API endpoint
$google_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
$response = @file_get_contents($google_url);

if ($response === FALSE) {
    echo json_encode(['success' => false, 'message' => 'Invalid Google token.']);
    exit();
}

$userData = json_decode($response, true);

// Verify audience matches configured client ID
if (isset($userData['aud']) && $userData['aud'] !== GOOGLE_CLIENT_ID) {
    echo json_encode(['success' => false, 'message' => 'Token audience mismatch.']);
    exit();
}

if (isset($userData['email'])) {
    $email = $userData['email'];
    $name = $userData['name'] ?? 'Google User';

    // Check if user exists in MySQL
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Auto-register new user strictly as a Teacher
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
        $stmt->execute([$name, $email, password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT)]);
        
        $userId = $pdo->lastInsertId();
        $userRole = 'teacher';
    } else {
        $userId = $user['id'];
        $name = $user['name'];
        $userRole = $user['role'];
    }

    $requestedRole = trim($input['role'] ?? '');

    // Strict Role Authorization: Enforce that user role matches the intended portal
    if (!empty($requestedRole) && $userRole !== $requestedRole) {
        if ($requestedRole === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Access Denied: This Google account does not have Administrator privileges. Please sign in via the Teacher Portal.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Access Denied: This account is an Administrator. Please sign in via the Admin Portal.']);
        }
        exit();
    }

    // Prevent session fixation
    session_regenerate_id(true);

    // Set PHP Session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $userRole;

    $redirect = ($userRole === 'admin') ? 'admin-dashboard.php' : 'teacher-dashboard.php';

    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'message' => 'Google authentication failed.']);
}
?>