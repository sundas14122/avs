<?php
// 1. Include DB Connection & Start Session
require_once 'db_connect.php';

// 2. Set JSON Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 3. Check for logged in user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 4. Get data from JavaScript
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->setting) || !isset($data->enabled)) {
    echo json_encode(['success' => false, 'error' => 'Invalid setting.']);
    exit;
}

// 5. Sanitize and validate the inputs
$setting_name = $data->setting;
$is_enabled = (bool)$data->enabled; // Cast to boolean (true/false)

// Whitelist the setting name to prevent SQL injection
$allowed_settings = ['notify_scan_complete', 'notify_premium_approval'];

if (!in_array($setting_name, $allowed_settings)) {
    echo json_encode(['success' => false, 'error' => 'Invalid setting name.']);
    exit;
}

// 6. Update the database
// We can safely use $setting_name in the SQL because we just whitelisted it
try {
    $stmt = $conn->prepare("UPDATE users SET $setting_name = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_enabled, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Preference updated.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>