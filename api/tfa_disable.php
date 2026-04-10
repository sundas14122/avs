<?php
require_once __DIR__ . '/db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare("UPDATE users SET tfa_enabled = 0, tfa_secret = NULL, tfa_recovery_codes = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Failed to disable 2FA.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '2FA disabled successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error while disabling 2FA.']);
}

$conn->close();
?>