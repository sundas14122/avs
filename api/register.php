<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input: All fields are required.']);
    exit;
}

$username = $data->username;
$email = $data->email;
$password = $data->password;

// --- NEW PASSWORD STRENGTH VALIDATION ---

// 1. Check Length
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
    exit;
}
// 2. Check Uppercase
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one uppercase letter.']);
    exit;
}
// 3. Check Lowercase
if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one lowercase letter.']);
    exit;
}
// 4. Check Number
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one number.']);
    exit;
}
// 5. Check Symbol
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one special character.']);
    exit;
}

// --- END VALIDATION ---

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
    exit;
}

try {
    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Email is already in use.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Check for duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Username is already taken.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Hash the password securely
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user
    $sql = "INSERT INTO users (username, email, password, bio, subscription_status) 
            VALUES (?, ?, ?, '', 'free')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: Could not register user.']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An exception occurred: ' . $e->getMessage()]);
}
?>