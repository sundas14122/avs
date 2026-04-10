<?php
require_once 'db_connect.php';

header("Content-Type: application/json; charset=UTF-8");

if (!validate_session($conn)) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $scans = [];

    $sql = "SELECT id, target_url, scan_type, status, result_data, created_at
            FROM django_scans
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 500";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vulnerability_count = 0;
        $finished_at = null;

        if (!empty($row['result_data'])) {
            $decoded = json_decode($row['result_data'], true);
            if (is_array($decoded)) {
                if (isset($decoded['summary']['total_vulnerabilities'])) {
                    $vulnerability_count = (int)$decoded['summary']['total_vulnerabilities'];
                } elseif (isset($decoded['scan_summary']['total_issues'])) {
                    $vulnerability_count = (int)$decoded['scan_summary']['total_issues'];
                }

                if (isset($decoded['scan_completed'])) {
                    $finished_at = $decoded['scan_completed'];
                } elseif (isset($decoded['scan_date']) && strtolower((string)$row['status']) === 'completed') {
                    $finished_at = $decoded['scan_date'];
                }
            }
        }

        $scans[] = [
            'id' => (int)$row['id'],
            'target' => (string)$row['target_url'],
            'scan_type' => (string)$row['scan_type'],
            'status' => ucfirst(strtolower((string)$row['status'])),
            'vulnerability_count' => $vulnerability_count,
            'started_at' => (string)$row['created_at'],
            'finished_at' => $finished_at,
        ];
    }

    $stmt->close();

    echo json_encode(['success' => true, 'scans' => $scans]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load scan history.']);
}

$conn->close();
?>