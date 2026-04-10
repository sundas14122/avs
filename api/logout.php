<?php
// 1. Include DB Connection & Start Session
require_once 'db_connect.php'; 

// 2. Get the user's current auth token from the cookie
$token = $_COOKIE['auth_token'] ?? '';

if (!empty($token)) {
    // 3. Delete this specific session from the database
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
}
$conn->close();

// 4. Unset all of the session variables
$_SESSION = array();

// 5. Expire the cookie
setcookie('auth_token', '', time() - 3600, '/'); 

// 6. Destroy the session.
session_destroy();

// 7. Send JSON response (for main.js)
header("Content-Type: application/json; charset=UTF-8");
echo json_encode(['message' => 'Logged out successfully.']);
exit;
?>