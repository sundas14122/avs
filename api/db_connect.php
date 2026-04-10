<?php
// Start the session to store variables, but we won't trust it for authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
// Defaults target local XAMPP. Override with environment variables in production.
define('DB_SERVER', getenv('DB_SERVER') ?: '127.0.0.1');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'avs_db');

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set the character set to utf8 (good practice)
$conn->set_charset("utf8");


// --- NEW SECURE SESSION VALIDATION ---
// This function will be called from the top of all your .php pages
function validate_session($conn) {
    // Check if our secure cookie is set
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];

        // Find this token in the database
        $stmt = $conn->prepare("SELECT user_id FROM user_sessions WHERE session_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Valid token! Update the 'last_seen' timestamp
            $stmt_update = $conn->prepare("UPDATE user_sessions SET last_seen = CURRENT_TIMESTAMP WHERE session_token = ?");
            $stmt_update->bind_param("s", $token);
            $stmt_update->execute();
            
            // Set the session variable for the user
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            return true;
        }
        
        // Invalid or expired token, unset it
        unset($_SESSION['user_id']);
        setcookie('auth_token', '', time() - 3600, '/'); // Expire the cookie
        return false;
    }
    
    // No cookie, not logged in
    unset($_SESSION['user_id']);
    return false;
}

function get_current_user_email($conn) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $email = null;
    if ($row = $result->fetch_assoc()) {
        $email = strtolower(trim((string)$row['email']));
    }
    $stmt->close();

    return $email ?: null;
}

function current_user_is_admin($conn) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user_id = (int)$_SESSION['user_id'];

    $admin_ids_raw = getenv('ADMIN_USER_IDS') ?: '';
    if ($admin_ids_raw !== '') {
        $admin_ids = array_filter(array_map('trim', explode(',', $admin_ids_raw)));
        if (in_array((string)$user_id, $admin_ids, true)) {
            return true;
        }
    }

    $admin_emails_raw = getenv('ADMIN_EMAILS') ?: '';
    if ($admin_emails_raw === '') {
        return false;
    }

    $admin_emails = array_filter(array_map(
        function ($v) { return strtolower(trim($v)); },
        explode(',', $admin_emails_raw)
    ));

    if (empty($admin_emails)) {
        return false;
    }

    $current_email = get_current_user_email($conn);
    if (!$current_email) {
        return false;
    }

    return in_array($current_email, $admin_emails, true);
}
// --- END OF NEW SESSION VALIDATION ---
?>