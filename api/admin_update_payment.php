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
$payment_id = isset($input['payment_id']) ? (int)$input['payment_id'] : (isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0);
$action = strtolower(trim((string)($input['action'] ?? ($_POST['action'] ?? ''))));

if ($payment_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment_id or action.']);
    exit;
}

function plan_details($plan_name) {
    $plan = strtolower((string)$plan_name);
    if (strpos($plan, 'yearly') !== false) {
        return ['days' => 365, 'scans' => 10000];
    }
    if (strpos($plan, 'monthly') !== false) {
        return ['days' => 30, 'scans' => 100];
    }
    return ['days' => 30, 'scans' => 20];
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT user_id, plan_name, status FROM payment_history WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception('Payment not found.');
    }

    $payment = $result->fetch_assoc();
    $stmt->close();

    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment already processed.');
    }

    $user_id = (int)$payment['user_id'];

    if ($action === 'approve') {
        $plan = plan_details($payment['plan_name']);
        $new_expiry = date('Y-m-d H:i:s', strtotime('+' . $plan['days'] . ' days'));

        $stmt_user = $conn->prepare("UPDATE users SET subscription_status = 'active', expiry_date = ?, scans_remaining = ? WHERE id = ?");
        $stmt_user->bind_param("sii", $new_expiry, $plan['scans'], $user_id);
        if (!$stmt_user->execute()) {
            throw new Exception('Failed to update user subscription.');
        }
        $stmt_user->close();

        $new_status = 'approved';
    } else {
        $stmt_user = $conn->prepare("UPDATE users SET subscription_status = 'free' WHERE id = ? AND subscription_status = 'pending'");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $stmt_user->close();

        $new_status = 'rejected';
    }

    $stmt_pay = $conn->prepare("UPDATE payment_history SET status = ? WHERE id = ?");
    $stmt_pay->bind_param("si", $new_status, $payment_id);
    if (!$stmt_pay->execute()) {
        throw new Exception('Failed to update payment status.');
    }
    $stmt_pay->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Payment approved successfully.' : 'Payment rejected successfully.',
        'payment_id' => $payment_id,
        'new_status' => $new_status
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>