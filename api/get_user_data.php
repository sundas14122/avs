<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/db_connect.php';

if (!validate_session($conn)) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid session.']);
    exit;
}

function normalize_plan_name($plan_name) {
    $plan = strtolower(trim((string)$plan_name));
    if ($plan === '' || $plan === 'free') {
        return 'Free';
    }
    if ($plan === 'starter') {
        return 'Starter';
    }
    if ($plan === 'pro_monthly') {
        return 'Pro Monthly';
    }
    if ($plan === 'pro_yearly') {
        return 'Pro Yearly';
    }

    return ucwords(str_replace('_', ' ', $plan));
}

try {
    $subscription_status = 'free';

    $stmt_user = $conn->prepare("SELECT subscription_status FROM users WHERE id = ? LIMIT 1");
    if (!$stmt_user) {
        throw new Exception('Failed to prepare user query.');
    }

    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($row_user = $result_user->fetch_assoc()) {
        $subscription_status = strtolower(trim((string)($row_user['subscription_status'] ?? 'free')));
        if ($subscription_status === '') {
            $subscription_status = 'free';
        }
    }
    $stmt_user->close();

    $plan_type = $subscription_status === 'active' ? 'Pro' : 'Free';

    $check_table = $conn->query("SHOW TABLES LIKE 'payment_history'");
    if ($check_table && $check_table->num_rows > 0) {
        $stmt_plan = $conn->prepare("SELECT plan_name FROM payment_history WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        if ($stmt_plan) {
            $stmt_plan->bind_param("i", $user_id);
            $stmt_plan->execute();
            $result_plan = $stmt_plan->get_result();
            if ($row_plan = $result_plan->fetch_assoc()) {
                $plan_type = normalize_plan_name($row_plan['plan_name'] ?? '');
            }
            $stmt_plan->close();
        }
    }

    echo json_encode([
        'subscription_status' => $subscription_status,
        'plan_type' => $plan_type
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
