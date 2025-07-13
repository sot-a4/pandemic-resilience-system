<?php
function handleUserRequest($method, $id) {
    $conn = getConnection();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific user
                $userData = checkPermission(2); // Role level 2 or higher
                
                $stmt = $conn->prepare("SELECT user_id, full_name, email, role_id FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo json_encode($result->fetch_assoc());
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "User not found"]);
                }
            } else {
                // Get all users
                $userData = checkPermission(3); // Admin only
                
                $result = $conn->query("SELECT user_id, full_name, email, role_id FROM users");
                echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            }
            break;
        
        case 'POST':
            // Create user - NO AUTHENTICATION REQUIRED FOR REGISTRATION
            $data = json_decode(file_get_contents("php://input"), true);
            
            error_log("User registration - Raw input: " . file_get_contents("php://input"));
            error_log("User registration - Decoded data: " . print_r($data, true));
            
            if (
                !$data ||
                !isset($data['full_name']) ||
                !isset($data['email']) ||
                !isset($data['password']) ||
                !isset($data['national_id']) ||
                !isset($data['prs_id'])
            ) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "error" => "Missing required fields"
                ]);
                break;
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "error" => "Invalid email format"
                ]);
                break;
            }
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $data['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                http_response_code(409);
                echo json_encode([
                    "status" => "error",
                    "error" => "Email already exists"
                ]);
                break;
            }
            
            // Check if national_id already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE national_id = ?");
            $stmt->bind_param("s", $data['national_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                http_response_code(409);
                echo json_encode([
                    "status" => "error",
                    "error" => "National ID already exists"
                ]);
                break;
            }
            
            // Check if prs_id already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE prs_id = ?");
            $stmt->bind_param("s", $data['prs_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                http_response_code(409);
                echo json_encode([
                    "status" => "error",
                    "error" => "PRS ID already exists"
                ]);
                break;
            }
            
            // For registration, default role_id = 2 (public user)
            // Only allow role_id from request if it's explicitly set to 2
            $role_id = isset($data['role_id']) ? intval($data['role_id']) : 2;
            if ($role_id !== 2) {
                $role_id = 2; // Force public user role for registration
            }
            
            // Hash password using SHA256 (to match auth.php)
            $password_hash = hash('sha256', $data['password']);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role_id, national_id, prs_id, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $phone = isset($data['phone']) ? $data['phone'] : null;
            $stmt->bind_param("sssisss", $data['full_name'], $data['email'], $password_hash, $role_id, $data['national_id'], $data['prs_id'], $phone);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // No audit log for registration since user is not authenticated yet
                
                http_response_code(201);
                echo json_encode([
                    "status" => "success", 
                    "message" => "User registered successfully",
                    "user_id" => $user_id
                ]);
            } else {
                error_log("Database error: " . $conn->error);
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "error" => "Failed to create user"
                ]);
            }
            break;
        
        case 'PUT':
            // Update user
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "User ID required"]);
                break;
            }
            
            $userData = checkPermission(2); // Role level 2 or higher
            
            // Only allow updating own user unless admin
            if ($userData['role_id'] < 3 && $userData['user_id'] != $id) {
                http_response_code(403);
                echo json_encode(["error" => "Permission denied"]);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid data"]);
                break;
            }
            
            // Build update query
            $updates = [];
            $types = "";
            $values = [];
            
            if (isset($data['full_name'])) {
                $updates[] = "full_name = ?";
                $types .= "s";
                $values[] = $data['full_name'];
            }
            
            if (isset($data['email'])) {
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(["error" => "Invalid email format"]);
                    break;
                }
                
                $updates[] = "email = ?";
                $types .= "s";
                $values[] = $data['email'];
            }
            
            if (isset($data['password'])) {
                $updates[] = "password_hash = ?";
                $types .= "s";
                $values[] = hash('sha256', $data['password']); // Use SHA256 to match auth
            }
            
            // Only admins can update role
            if (isset($data['role_id']) && $userData['role_id'] >= 3) {
                $updates[] = "role_id = ?";
                $types .= "i";
                $values[] = $data['role_id'];
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(["error" => "No fields to update"]);
                break;
            }
            
            $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
            $types .= "i";
            $values[] = $id;
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$values);
            
            if ($stmt->execute()) {
                // Log the action
                logAction($conn, $userData['user_id'], 'UPDATE', 'users', $id, 'Updated user');
                
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update user"]);
            }
            break;
        
        case 'DELETE':
            // Delete user
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "User ID required"]);
                break;
            }
            
            $userData = checkPermission(3); // Admin only
            
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Log the action
                logAction($conn, $userData['user_id'], 'DELETE', 'users', $id, 'Deleted user');
                
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete user"]);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
    
    $conn->close();
}

// Helper function for audit logging
// Helper function for audit logging
//function logAction($conn, $user_id, $action, $resource, $resource_id) {
//    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_affected, record_id, timestamp) VALUES (?, ?, ?, ?, NOW())");
//    $stmt->bind_param("issi", $user_id, $action, $resource, $resource_id);
//    $stmt->execute();
//}

?>