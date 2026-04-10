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
    $overview = [
        'users_total' => 0,
        'users_active' => 0,
        'users_pending' => 0,
        'users_free' => 0,
        'payments_pending' => 0,
        'payments_approved' => 0,
        'payments_rejected' => 0,
        'scans_total' => 0,
        'scans_pending' => 0,
        'scans_running' => 0,
        'scans_completed' => 0,
        'scans_failed' => 0,
        'sessions_total' => 0
    ];

    $q = $conn->query("SELECT COUNT(*) c FROM users");
    $overview['users_total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT LOWER(subscription_status) s, COUNT(*) c FROM users GROUP BY LOWER(subscription_status)");
    while ($r = $q->fetch_assoc()) {
        if ($r['s'] === 'active') $overview['users_active'] = (int)$r['c'];
        if ($r['s'] === 'pending') $overview['users_pending'] = (int)$r['c'];
        if ($r['s'] === 'free') $overview['users_free'] = (int)$r['c'];
    }

    $q = $conn->query("SELECT status, COUNT(*) c FROM payment_history GROUP BY status");
    while ($r = $q->fetch_assoc()) {
        if ($r['status'] === 'pending') $overview['payments_pending'] = (int)$r['c'];
        if ($r['status'] === 'approved') $overview['payments_approved'] = (int)$r['c'];
        if ($r['status'] === 'rejected') $overview['payments_rejected'] = (int)$r['c'];
    }

    $q = $conn->query("SELECT LOWER(status) s, COUNT(*) c FROM django_scans GROUP BY LOWER(status)");
    while ($r = $q->fetch_assoc()) {
        if ($r['s'] === 'pending') $overview['scans_pending'] = (int)$r['c'];
        if ($r['s'] === 'running') $overview['scans_running'] = (int)$r['c'];
        if ($r['s'] === 'completed') $overview['scans_completed'] = (int)$r['c'];
        if ($r['s'] === 'failed') $overview['scans_failed'] = (int)$r['c'];
    }
    $overview['scans_total'] = $overview['scans_pending'] + $overview['scans_running'] + $overview['scans_completed'] + $overview['scans_failed'];

    $q = $conn->query("SELECT COUNT(*) c FROM user_sessions");
    $overview['sessions_total'] = (int)$q->fetch_assoc()['c'];

    echo json_encode(['success' => true, 'overview' => $overview]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>