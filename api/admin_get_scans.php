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
    $scans = [];
    $sql = "SELECT s.id, s.user_id, u.username, u.email, s.target_url, s.scan_type, s.status, s.created_at, s.task_id
            FROM django_scans s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.created_at DESC
            LIMIT 500";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $scans[] = $row;
    }

    echo json_encode(['success' => true, 'scans' => $scans]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>