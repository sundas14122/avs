<?php
require_once "db_connect.php";

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    http_response_code(401);
    echo json_encode(["message" => "Not logged in"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON payload"]);
    exit;
}

// Update profile info (fullName, bio)
if (isset($data->fullName) && isset($data->bio)) {
    $fullName = trim((string)$data->fullName);
    $bio = trim((string)$data->bio);

    $stmt = $conn->prepare("UPDATE users SET fullName = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullName, $bio, $user_id);
}
// Update email
elseif (isset($data->email)) {
    $email = trim((string)$data->email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid email format"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $user_id);
}
// Update password
elseif (isset($data->currentPassword) && isset($data->newPassword)) {
    $stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $row = $result->fetch_assoc();
    $stmt_check->close();

    if ($row && password_verify((string)$data->currentPassword, (string)$row['password'])) {
        if (strlen((string)$data->newPassword) < 8) {
            http_response_code(400);
            echo json_encode(["message" => "New password must be at least 8 characters."]);
            exit;
        }

        $hashed_password = password_hash($data->newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
    } else {
        http_response_code(403);
        echo json_encode(["message" => "Incorrect current password"]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Invalid request"]);
    exit;
}

if ($stmt->execute() === TRUE) {
    http_response_code(200);
    echo json_encode(["message" => "User data updated successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error updating record."]);
}

$stmt->close();

$conn->close();
?>