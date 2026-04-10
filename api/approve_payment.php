<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'Access Denied.']);
    exit;
}

if (!current_user_is_admin($conn)) {
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
    exit;
}

// 2. GET DATA (We need the Payment ID to approve)
// In a real admin panel, you would send this via POST.
// For testing, you can pass it via URL: api/approve_payment.php?payment_id=1
$json = json_decode(file_get_contents("php://input"), true);
$payment_id = $_POST['payment_id'] ?? ($json['payment_id'] ?? ($_GET['payment_id'] ?? null));

if (!$payment_id) {
    echo json_encode(['success' => false, 'error' => 'Payment ID required.']);
    exit;
}

try {
    // 3. GET PAYMENT DETAILS
    $stmt = $conn->prepare("SELECT user_id, plan_name FROM payment_history WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Payment not found or already processed.");
    }

    $payment = $result->fetch_assoc();
    $user_id = $payment['user_id'];
    $plan_name = strtolower($payment['plan_name']); // e.g., "pro_monthly"

    // 4. CALCULATE EXPIRY DATE & SCANS
    $days_to_add = 0;
    $scans = 5; // Default free limit

    if (strpos($plan_name, 'monthly') !== false) {
        // If plan name has "monthly", add 30 days
        $days_to_add = 30;
        $scans = 100; // Example limit for monthly
    } elseif (strpos($plan_name, 'yearly') !== false) {
        // If plan name has "yearly", add 365 days
        $days_to_add = 365;
        $scans = 10000; // Unlimited/High limit for yearly
    } else {
        // Basic/Starter plan
        $days_to_add = 30;
        $scans = 20;
    }

    // Calculate exact date: NOW + Days
    $new_expiry = date('Y-m-d H:i:s', strtotime("+$days_to_add days"));

    // 5. UPDATE USER TABLE (Set active, expiry, and scans)
    $stmtUpdateUser = $conn->prepare("UPDATE users SET subscription_status = 'active', expiry_date = ?, scans_remaining = ? WHERE id = ?");
    $stmtUpdateUser->bind_param("sii", $new_expiry, $scans, $user_id);
    
    if (!$stmtUpdateUser->execute()) {
        throw new Exception("Failed to update user account.");
    }

    // 6. UPDATE PAYMENT HISTORY (Mark as Approved)
    $stmtUpdatePay = $conn->prepare("UPDATE payment_history SET status = 'approved' WHERE id = ?");
    $stmtUpdatePay->bind_param("i", $payment_id);
    $stmtUpdatePay->execute();

    echo json_encode([
        'success' => true, 
        'message' => "Plan approved! User ID $user_id is now Active until $new_expiry"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>