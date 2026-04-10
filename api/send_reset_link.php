<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required.']);
    exit;
}

$email = $data->email;

try {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'message' => 'If that email exists, we have sent a reset link.']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $stmt->close();

    // Generate Token
    $token = bin2hex(random_bytes(32)); 
    $token_hash = hash('sha256', $token);

    // --- TIMEZONE FIX ---
    // We use MySQL's DATE_ADD(NOW()...) so the time always matches the database clock.
    $stmt_update = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $stmt_update->bind_param("si", $token_hash, $user_id);
    // --------------------
    
    if ($stmt_update->execute()) {
        $appBaseUrl = rtrim(getenv('APP_BASE_URL') ?: 'https://avscanner.tech', '/');
        $resetLink = $appBaseUrl . "/reset-password.php?token=" . $token;

        $response = [
            'success' => true,
            'message' => 'If that email exists, we have sent a reset link.'
        ];

        // Keep optional debug mode for environments without SMTP integration.
        if ((getenv('PASSWORD_RESET_DEBUG') ?: 'false') === 'true') {
            $response['debug_link'] = $resetLink;
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>