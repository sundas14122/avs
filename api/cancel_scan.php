<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$scan_id = isset($_GET['scan_id']) ? (int)$_GET['scan_id'] : 0;

if ($scan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid scan id.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, task_id FROM django_scans WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $scan_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo json_encode(['success' => false, 'error' => 'Scan not found or access denied.']);
        exit;
    }

    $row = $result->fetch_assoc();
    $task_id = trim((string)($row['task_id'] ?? ''));

    if ($task_id === '') {
        // Legacy/local-only rows with no mapped Django task: mark cancelled locally.
        $stmt_local = $conn->prepare("UPDATE django_scans SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
        $stmt_local->bind_param("ii", $scan_id, $user_id);
        $stmt_local->execute();
        $stmt_local->close();

        echo json_encode(['success' => true, 'message' => 'Scan cancelled locally.']);
        exit;
    }

    $django_base = rtrim(getenv('DJANGO_API_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
    $cancel_url = $django_base . '/scan/cancel/' . urlencode($task_id) . '/';

    $ch = curl_init($cancel_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        echo json_encode(['success' => false, 'error' => 'Failed to connect to scan engine: ' . $curl_error]);
        exit;
    }

    $response_data = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300 && !empty($response_data['success'])) {
        $stmt_update = $conn->prepare("UPDATE django_scans SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $scan_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();

        echo json_encode(['success' => true, 'message' => $response_data['message'] ?? 'Scan cancelled successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => $response_data['error'] ?? 'Failed to cancel scan.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>