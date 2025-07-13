<?php

require_once __DIR__ . '/users.php';
  // Adjust the path relative to this file

function handleVaccinationRequest($method, $id) {
    $conn = getConnection();
    
    switch ($method) {
        case 'GET':
            $userData = authenticate(); // get logged in user info
            
            if ($id) {
                // Get specific vaccination record
                $stmt = $conn->prepare(
                    "SELECT v.*, u.full_name 
                    FROM vaccination_records v 
                    JOIN users u ON v.user_id = u.user_id 
                    WHERE v.record_id = ?"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo json_encode($result->fetch_assoc());
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Record not found"]);
                }
            } else {
                // If query param user_id provided, filter by that user
                $query_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
                
                // Admins can see all, or users can see their own or specific user's records (if allowed)
                if ($query_user_id && ($userData['role_id'] >= 3 || $userData['user_id'] == $query_user_id)) {
                    $stmt = $conn->prepare(
                      "SELECT v.*, u.full_name 
                      FROM vaccination_records v 
                      JOIN users u ON v.user_id = u.user_id 
                      WHERE v.user_id = ?"
                    );
                    $stmt->bind_param("i", $query_user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
                } else if ($userData['role_id'] >= 3) {
                    // Admins can get all records
                    $result = $conn->query(
                       "SELECT v.*, u.full_name 
                       FROM vaccination_records v 
                       JOIN users u ON v.user_id = u.user_id"
                    );
                    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
                } else {
                    // Regular users get their own records only
                    $stmt = $conn->prepare(
                        "SELECT v.*, u.full_name 
                        FROM vaccination_records v 
                        JOIN users u ON v.user_id = u.user_id 
                        WHERE v.user_id = ?"
                    );
                    $stmt->bind_param("i", $userData['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
                }
            }
            break;
        
        case 'POST':
            // Create vaccination record
            $userData = checkPermission(2); // Healthcare provider or higher
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!$data || !isset($data['user_id']) || !isset($data['vaccine_name']) || 
                !isset($data['dose_number']) || !isset($data['date_administered'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required fields"]);
                break;
            }
            
            $stmt = $conn->prepare("INSERT INTO vaccination_records 
                (user_id, vaccine_name, dose_number, date_administered, provider, lot_number, expiration_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $provider = $data['provider'] ?? null;
            $lot_number = $data['lot_number'] ?? null;
            $expiration_date = $data['expiration_date'] ?? null;
            
            $stmt->bind_param(
                "isissss", 
                $data['user_id'], 
                $data['vaccine_name'], 
                $data['dose_number'], 
                $data['date_administered'], 
                $provider,
                $lot_number,
                $expiration_date
            );
            
            if ($stmt->execute()) {
                $record_id = $conn->insert_id;
                
                // Log the action
                logAction($conn, $userData['user_id'], 'CREATE', 'vaccination_records', $record_id, 'Created vaccination record');
                
                http_response_code(201);
                echo json_encode(["status" => "success", "record_id" => $record_id]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to create record"]);
            }
            break;
        
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "Record ID required"]);
                break;
            }
            
            $userData = checkPermission(2); // Healthcare provider or higher
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid data"]);
                break;
            }
            
            $updates = [];
            $types = "";
            $values = [];
            
            if (isset($data['vaccine_name'])) {
                $updates[] = "vaccine_name = ?";
                $types .= "s";
                $values[] = $data['vaccine_name'];
            }
            
            if (isset($data['dose_number'])) {
                $updates[] = "dose_number = ?";
                $types .= "i";
                $values[] = $data['dose_number'];
            }
            
            if (isset($data['date_administered'])) {
                $updates[] = "date_administered = ?";
                $types .= "s";
                $values[] = $data['date_administered'];
            }
            
            if (isset($data['provider'])) {
                $updates[] = "provider = ?";
                $types .= "s";
                $values[] = $data['provider'];
            }
            
            if (isset($data['lot_number'])) {
                $updates[] = "lot_number = ?";
                $types .= "s";
                $values[] = $data['lot_number'];
            }
            
            if (isset($data['expiration_date'])) {
                $updates[] = "expiration_date = ?";
                $types .= "s";
                $values[] = $data['expiration_date'];
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(["error" => "No fields to update"]);
                break;
            }
            
            $query = "UPDATE vaccination_records SET " . implode(", ", $updates) . " WHERE record_id = ?";
            $types .= "i";
            $values[] = $id;
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$values);
            
            if ($stmt->execute()) {
                // Log the action
                logAction($conn, $userData['user_id'], 'UPDATE', 'vaccination_records', $id, 'Updated vaccination record');
                
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update record"]);
            }
            break;
        
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "Record ID required"]);
                break;
            }
            
            $userData = checkPermission(3); // Admin only
            
            $stmt = $conn->prepare("DELETE FROM vaccination_records WHERE record_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Log the action
                logAction($conn, $userData['user_id'], 'DELETE', 'vaccination_records', $id, 'Deleted vaccination record');
                
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete record"]);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
    
    $conn->close();
}
?>