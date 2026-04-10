<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
if (!current_user_is_admin($conn)) {
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$session_id = isset($input['session_id']) ? (int)$input['session_id'] : 0;

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid session ID.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE id = ?");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Session revoked successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>