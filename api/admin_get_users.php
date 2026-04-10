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
    $users = [];
    $sql = "SELECT id, username, email, subscription_status, tfa_enabled, scans_remaining, expiry_date, scan_count FROM users ORDER BY id DESC LIMIT 400";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $row['tfa_enabled'] = (int)$row['tfa_enabled'];
        $row['scans_remaining'] = isset($row['scans_remaining']) ? (int)$row['scans_remaining'] : 0;
        $row['scan_count'] = isset($row['scan_count']) ? (int)$row['scan_count'] : 0;
        $users[] = $row;
    }

    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>