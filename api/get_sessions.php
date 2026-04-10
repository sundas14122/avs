<?php
// 1. Include DB Connection & Validate Session
require_once 'db_connect.php';
if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. Set JSON Headers
header("Content-Type: application/json; charset=UTF-8");

// 3. Get the current user's token from their cookie
$current_token = $_COOKIE['auth_token'] ?? '';

try {
    // 4. Fetch all sessions for this user
    $sql = "SELECT id, session_token, ip_address, user_agent, last_seen 
            FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_seen DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        // Add an 'is_current_session' flag so the UI can highlight it
        $row['is_current_session'] = ($row['session_token'] === $current_token);
        $sessions[] = $row;
    }

    echo json_encode(['success' => true, 'sessions' => $sessions]);
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>