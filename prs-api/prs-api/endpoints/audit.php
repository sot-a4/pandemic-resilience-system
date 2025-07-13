<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jwt_handler.php';
require_once __DIR__ . '/../security.php';

// ✅ Export function should be defined OUTSIDE
function exportAuditLogs($conn) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['log_id', 'user_id', 'full_name', 'action', 'entity_affected', 'record_id', 'timestamp']);

    $query = "SELECT a.*, u.full_name 
              FROM audit_logs a
              JOIN users u ON a.user_id = u.user_id
              ORDER BY a.timestamp DESC";

    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['log_id'],
            $row['user_id'],
            $row['full_name'],
            $row['action'],
            $row['entity_affected'],
            $row['record_id'],
            $row['timestamp']
        ]);
    }

    fclose($output);
}

function handleAuditRequest($method, $id) {
    $conn = getConnection();

    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        $conn->close();
        return;
    }

    $userData = checkPermission(3);  // Admin check

    if ($id) {
        $stmt = $conn->prepare("SELECT a.*, u.full_name 
                                FROM audit_logs a
                                JOIN users u ON a.user_id = u.user_id
                                WHERE a.log_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Audit log entry not found"]);
        }

        $conn->close();
        return;
    }

    // 🔥 Handle export early
    if (isset($_GET['action']) && $_GET['action'] === 'export') {
        exportAuditLogs($conn);
        $conn->close();
        return;
    }

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $whereClause = "";
    $params = [];
    $types = "";

    if (isset($_GET['user_id'])) {
        $whereClause .= " AND a.user_id = ?";
        $params[] = intval($_GET['user_id']);
        $types .= "i";
    }

    if (isset($_GET['action'])) {
        $whereClause .= " AND a.action = ?";
        $params[] = $_GET['action'];
        $types .= "s";
    }

    if (isset($_GET['resource'])) {  // consider renaming this to `entity_affected`
        $whereClause .= " AND a.resource = ?";
        $params[] = $_GET['resource'];
        $types .= "s";
    }

    if (isset($_GET['from_date'])) {
        $whereClause .= " AND a.timestamp >= ?";
        $params[] = $_GET['from_date'];
        $types .= "s";
    }

    if (isset($_GET['to_date'])) {
        $whereClause .= " AND a.timestamp <= ?";
        $params[] = $_GET['to_date'];
        $types .= "s";
    }

    $countQuery = "SELECT COUNT(*) AS total FROM audit_logs a WHERE 1=1" . $whereClause;
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];

    $query = "SELECT a.*, u.full_name 
              FROM audit_logs a
              JOIN users u ON a.user_id = u.user_id
              WHERE 1=1" . $whereClause . "
              ORDER BY a.timestamp DESC
              LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        "total" => $total,
        "logs" => $result->fetch_all(MYSQLI_ASSOC)
    ]);

    $conn->close();
}
