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

// 3. Get data from JavaScript
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified.']);
    exit;
}

try {
    // --- ACTION: LOGOUT A SINGLE SESSION ---
    if ($data->action === 'revoke_session' && isset($data->session_id)) {
        $session_id = (int)$data->session_id;

        // Delete the session, but ONLY if it belongs to this user
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $session_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Session revoked.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to revoke session.']);
        }
        $stmt->close();
    }
    
    // --- ACTION: LOGOUT ALL OTHER SESSIONS ---
    elseif ($data->action === 'logout_all_other') {
        $current_token = $_COOKIE['auth_token'] ?? '';
        
        // Delete all sessions for this user EXCEPT the current one
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token != ?");
        $stmt->bind_param("is", $user_id, $current_token);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'All other sessions have been logged out.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to log out other sessions.']);
        }
        $stmt->close();
    }
    
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action or missing session ID.']);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>