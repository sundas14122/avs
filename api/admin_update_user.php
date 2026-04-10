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
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$action = strtolower(trim((string)($input['action'] ?? '')));

if ($user_id <= 0 || $action === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

try {
    if ($action === 'set_subscription') {
        $status = strtolower(trim((string)($input['status'] ?? 'free')));
        if (!in_array($status, ['free', 'pending', 'active'], true)) {
            throw new Exception('Invalid subscription status.');
        }

        $stmt = $conn->prepare("UPDATE users SET subscription_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Subscription status updated.']);
        $conn->close();
        exit;
    }

    if ($action === 'set_scans_remaining') {
        $value = isset($input['value']) ? (int)$input['value'] : 0;
        if ($value < 0) $value = 0;

        $stmt = $conn->prepare("UPDATE users SET scans_remaining = ? WHERE id = ?");
        $stmt->bind_param("ii", $value, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Scans remaining updated.']);
        $conn->close();
        exit;
    }

    if ($action === 'disable_tfa') {
        $empty = '';
        $stmt = $conn->prepare("UPDATE users SET tfa_enabled = 0, tfa_secret = NULL, tfa_recovery_codes = ? WHERE id = ?");
        $stmt->bind_param("si", $empty, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => '2FA disabled for user.']);
        $conn->close();
        exit;
    }

    if ($action === 'force_logout') {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'All user sessions revoked.']);
        $conn->close();
        exit;
    }

    throw new Exception('Unsupported action.');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>