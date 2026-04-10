<?php
require_once 'db_connect.php';
// Load our manual class instead of Composer
require_once 'TOTP.php'; 

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

$user_id = $_SESSION['user_id'];
header("Content-Type: application/json; charset=UTF-8");

try {
    // 1. Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) { throw new Exception("User not found."); }

    // 2. Generate Secret using our simple class
    $secret = TOTP::createSecret();

    // 3. Store secret in DB
    $stmt_save = $conn->prepare("UPDATE users SET tfa_secret = ? WHERE id = ?");
    $stmt_save->bind_param("si", $secret, $user_id);
    if (!$stmt_save->execute()) { throw new Exception("Failed to save secret."); }
    $stmt_save->close();

    // 4. Generate QR Code Image
    // We use a reliable public API for the QR image to avoid local library headaches
    $otpAuthUrl = TOTP::getQRText($user['email'], $secret, 'AVScanner');
    $qrCodeImage = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpAuthUrl);

    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code_image' => $qrCodeImage
    ]);

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>