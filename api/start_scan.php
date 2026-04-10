<?php
// 1. Include DB & Session
require_once 'db_connect.php';

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. Set JSON Headers
header("Content-Type: application/json; charset=UTF-8");

// 3. Get Input Data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->target) || !isset($data->scan_type)) {
    echo json_encode(['success' => false, 'error' => 'Missing target or scan type.']);
    exit;
}

$target = $data->target;
$scan_type = $data->scan_type;

// 4. Call Django API
$django_url = getenv('DJANGO_API_URL') ?: 'http://127.0.0.1:8000/api/scan/start/';

$payload = json_encode([
    'target' => $target,
    'scan_type' => $scan_type,
    'user_id' => $user_id
]);

$ch = curl_init($django_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 5. Handle Response
if ($curl_error) {
    echo json_encode(['success' => false, 'error' => 'Failed to connect to scan engine (' . $django_url . '): ' . $curl_error]);
    exit;
}

$response_data = json_decode($response, true);

if ($http_code >= 200 && $http_code < 300) {
    // Success! Django started the scan.
    // Insert into local MySQL history table so scan-history.php can display it.
    $django_scan_id = isset($response_data['scan_id']) ? (string)$response_data['scan_id'] : null;

    $stmt_local = $conn->prepare("INSERT INTO django_scans (user_id, target_url, scan_type, status, result_data, created_at, task_id) VALUES (?, ?, ?, 'Pending', NULL, NOW(6), ?)");
    if ($stmt_local) {
        $stmt_local->bind_param("isss", $user_id, $target, $scan_type, $django_scan_id);
        $stmt_local->execute();
        $stmt_local->close();
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Scan started successfully.',
        'scan_id' => $response_data['scan_id'] ?? 'N/A'
    ]);
} else {
    // Django returned an error
    $error_msg = $response_data['error'] ?? 'Unknown error from scan engine.';
    echo json_encode(['success' => false, 'error' => $error_msg]);
}

$conn->close();
?>