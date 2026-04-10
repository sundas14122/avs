<?php
// 1. Include DB Connection & Start Session
require_once 'db_connect.php';

// 2. Set JSON Headers
header("Content-Type: application/json; charset=UTF-8");

// 3. Check for logged in user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 4. Get data from JavaScript
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->theme) || ($data->theme != 'light' && $data->theme != 'dark')) {
    echo json_encode(['success' => false, 'error' => 'Invalid theme specified.']);
    exit;
}

$theme = $data->theme;

// 5. Update the database
try {
    $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->bind_param("si", $theme, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Theme updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: Could not save theme.']);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>