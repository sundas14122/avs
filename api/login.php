<?php
// 1. Include the database connection
// db_connect.php already starts the session
require_once 'db_connect.php';

// 2. Set the correct headers for a JSON API
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 3. Get the data from the JavaScript fetch request
$data = json_decode(file_get_contents("php://input"));

// 4. Validate the data
if (!isset($data->credential) || !isset($data->password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
    exit;
}

$credential = $data->credential;
$password = $data->password;

try {
    // 5. Find the user by username OR email
    $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $credential, $credential);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 6. Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct!
            
            // --- THIS IS THE NEW SESSION LOGIC ---
            
            // 7. Generate a secure, random token
            $token = bin2hex(random_bytes(32));
            $user_id = $user['id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            // 8. Save the new session to the database
            $stmt_insert = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("isss", $user_id, $token, $ip_address, $user_agent);
            $stmt_insert->execute();

            // 9. Set the token in a secure, HttpOnly cookie
            // This cookie will be sent with every request
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

            setcookie('auth_token', $token, [
                'expires' => time() + (86400 * 30), // 30 days
                'path' => '/',
                'httponly' => true, // <-- Makes it secure from JavaScript
                'secure' => $is_https,
                'samesite' => 'Lax'
            ]);
            
            // 10. Set session variables (optional, but good for username)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            $is_admin = current_user_is_admin($conn);
            $redirect_to = $is_admin ? 'admin-panel.php' : 'dashboard.php';
            
            // Send success response
            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'is_admin' => $is_admin,
                'redirect_to' => $redirect_to
            ]);
            // --- END OF NEW SESSION LOGIC ---

        } else {
            // Invalid password
            echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
        }
    } else {
        // User not found
        echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>