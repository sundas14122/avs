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

if (!isset($data->action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified.']);
    exit;
}

// --- Re-usable function to verify password ---
function verifyPassword($conn, $user_id, $password) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            return true;
        }
    }
    return false;
}

// 5. Handle the specific action
try {
    // --- ACTION: UPDATE EMAIL ---
    if ($data->action === 'update_email') {
        if (!isset($data->email) || !isset($data->password)) {
            echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
            exit;
        }
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
            exit;
        }

        // 1. Verify password first
        if (!verifyPassword($conn, $user_id, $data->password)) {
            echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
            exit;
        }

        // 2. Check if new email is already in use
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $data->email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'This email is already in use by another account.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // 3. All checks passed, update the email
        $stmt_update = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt_update->bind_param("si", $data->email, $user_id);
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Email updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: Could not update email.']);
        }
        $stmt_update->close();
    }

    // --- ACTION: UPDATE PASSWORD ---
    elseif ($data->action === 'update_password') {
        if (!isset($data->currentPassword) || !isset($data->newPassword)) {
            echo json_encode(['success' => false, 'error' => 'All password fields are required.']);
            exit;
        }

        // 1. Verify current password
        if (!verifyPassword($conn, $user_id, $data->currentPassword)) {
            echo json_encode(['success' => false, 'error' => 'Incorrect current password.']);
            exit;
        }

        // 2. Hash the new password
        $new_password_hash = password_hash($data->newPassword, PASSWORD_BCRYPT);

        // 3. All checks passed, update the password
        $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_password_hash, $user_id);
        if ($stmt_update->execute()) {
            // After changing password, destroy their session for security
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Password updated. Please log in again.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: Could not update password.']);
        }
        $stmt_update->close();
    }
    
    // --- ACTION: UNKNOWN ---
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>