<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    require_once __DIR__ . '/db_connect.php';

    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) throw new Exception("User not authenticated.");
    
    $user_id = $_SESSION['user_id'];

    // 1. FETCH CURRENT SUBSCRIPTION STATUS
    // We get the expiry date and scans remaining from the users table
    $stmtUser = $conn->prepare("SELECT subscription_status, expiry_date, scans_remaining FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    // Default values if null
    $currentStatus = [
        'status' => $userResult['subscription_status'] ?? 'free',
        'expiry' => $userResult['expiry_date'],
        'scans'  => $userResult['scans_remaining'] ?? 0
    ];

    // 2. FETCH PAYMENT HISTORY
    $history = [];
    $checkTable = $conn->query("SHOW TABLES LIKE 'payment_history'");
    if ($checkTable->num_rows > 0) {
        $stmt = $conn->prepare("SELECT id, plan_name, amount, trx_id, status, created_at FROM payment_history WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['amount'] = (float)$row['amount'];
            $history[] = $row;
        }
        $stmt->close();
    }

    // 3. RETURN BOTH
    $response = [
        'success' => true, 
        'current_status' => $currentStatus, 
        'history' => $history
    ];
    
    $conn->close();

} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
exit;
?>