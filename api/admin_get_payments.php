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
    $payments = [];
    $sql = "SELECT ph.id, ph.user_id, ph.plan_name, ph.amount, ph.trx_id, ph.proof_image, ph.status, ph.created_at, u.username, u.email
            FROM payment_history ph
            JOIN users u ON ph.user_id = u.id
            ORDER BY (ph.status = 'pending') DESC, ph.created_at DESC
            LIMIT 300";

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $row['amount'] = (float)$row['amount'];
        $payments[] = $row;
    }

    echo json_encode(['success' => true, 'payments' => $payments]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>