<?php
require_once 'db_connect.php';

// 1. Session Check
if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}
$user_id = $_SESSION['user_id'];
header("Content-Type: application/json; charset=UTF-8");

// 2. Input Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$plan = $_POST['plan'] ?? '';
$trx_id = $_POST['trx_id'] ?? '';

if (empty($plan) || empty($trx_id) || empty($_FILES['payment_proof'])) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

// 3. Handle File Upload
$target_dir = "../uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$file_extension = pathinfo($_FILES["payment_proof"]["name"], PATHINFO_EXTENSION);
// Clean filename to prevent issues
$new_filename = "payment_" . $user_id . "_" . time() . "." . $file_extension;
$target_file = $target_dir . $new_filename;

$allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
if (!in_array(strtolower($file_extension), $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF allowed.']);
    exit;
}

if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
    
    // --- FIXED PRICE LOGIC (Case Insensitive) ---
    $amount = 0.00;
    // We check strictly against the values set in data-plan in subscription.php
    if ($plan == 'starter') {
        $amount = 5.00;
    } elseif ($plan == 'pro_monthly') {
        $amount = 14.99;
    } elseif ($plan == 'pro_yearly') {
        $amount = 150.00;
    } else {
        // Fallback check
        if (stripos($plan, 'basic') !== false) $amount = 5.00;
        elseif (stripos($plan, 'monthly') !== false) $amount = 14.99;
        elseif (stripos($plan, 'yearly') !== false) $amount = 150.00;
    }

    // 5. Insert into Payment History Table
    try {
        $stmt = $conn->prepare("INSERT INTO payment_history (user_id, plan_name, amount, trx_id, proof_image, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $proof_path = "uploads/" . $new_filename; 
        $stmt->bind_param("isdss", $user_id, $plan, $amount, $trx_id, $proof_path);
        
        if ($stmt->execute()) {
            // 6. Update User Status to 'pending'
            $stmt_user = $conn->prepare("UPDATE users SET subscription_status = 'pending' WHERE id = ?");
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $stmt_user->close();

            echo json_encode(['success' => true, 'message' => 'Payment submitted for verification.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error saving payment.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database Exception: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file. Check folder permissions.']);
}

$conn->close();
?>