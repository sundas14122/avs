<?php
// 1. Enable Error Reporting (for debugging)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 2. Load dependencies
// Use manual TOTP class instead of Composer
require_once __DIR__ . '/TOTP.php'; 
require_once __DIR__ . '/db_connect.php';

// 3. Session Check
if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
header("Content-Type: application/json; charset=UTF-8");

// 4. Get Data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->code)) {
    echo json_encode(['success' => false, 'error' => 'Code is required.']);
    exit;
}

try {
    // 5. Get the stored temporary secret
    $stmt = $conn->prepare("SELECT tfa_secret FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !$user['tfa_secret']) {
        echo json_encode(['success' => false, 'error' => 'No secret found. Please regenerate QR code.']);
        exit;
    }

    // 6. Verify Code using our manual TOTP class
    // We use a discrepancy of 1 (allows +/- 30 seconds for clock drift)
    $isValid = TOTP::verifyCode($user['tfa_secret'], $data->code, 1);

    if ($isValid) {
        // 7. Success! Generate Recovery Codes
        $recoveryCodes = [];
        for($i=0; $i<5; $i++) {
            // Generate secure 8-character hex codes
            $recoveryCodes[] = bin2hex(random_bytes(4)); 
        }
        $recoveryJson = json_encode($recoveryCodes);

        // 8. Enable 2FA in Database
        $stmt_update = $conn->prepare("UPDATE users SET tfa_enabled = 1, tfa_recovery_codes = ? WHERE id = ?");
        $stmt_update->bind_param("si", $recoveryJson, $user_id);
        
        if($stmt_update->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => '2FA Enabled Successfully.',
                'recovery_codes' => $recoveryCodes
            ]);
        } else {
            throw new Exception("Database error while enabling 2FA.");
        }
        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid code. Please try again.']);
    }

    $conn->close();

} catch (Throwable $e) {
    // Catch any crash and send a clean JSON error
    http_response_code(200); 
    echo json_encode([
        'success' => false, 
        'error' => 'Server error while enabling 2FA.'
    ]);
}
?>