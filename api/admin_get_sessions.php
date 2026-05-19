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

try {
    $sessions = [];
    $sql = "SELECT s.id, s.user_id, u.username, u.email, s.ip_address, s.user_agent, s.last_seen as created_at, s.last_seen
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.last_seen DESC
            LIMIT 500";

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }

    echo json_encode(['success' => true, 'sessions' => $sessions]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>