<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function handleAuthRequest($method) {
    $conn = getConnection();

    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
             error_log("Raw input data: " . file_get_contents("php://input"));  // θα είναι κενό εδώ γιατί το διάβασες ήδη παραπάνω, καλύτερα αποθήκευσέ το πριν
             error_log("Decoded JSON data: " . print_r($data, true));

            if (!$data || !isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing email or password"]);
                error_log("Missing email or password");
                break;
            }

            $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $data['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(401);
                echo json_encode(["error" => "Invalid credentials"]);
                error_log("No user found with email: " . $data['email']);
                break;
            }

            $user = $result->fetch_assoc();
             error_log("User data fetched: " . print_r($user, true));

            // Κάνουμε sha256 hash στο password που έστειλε ο χρήστης
            $input_password_hash = hash('sha256', $data['password']);
            error_log("Input password hash: " . $input_password_hash);
            error_log("Stored password hash: " . $user['password_hash']);

            if ($input_password_hash !== $user['password_hash']) {
                http_response_code(401);
                echo json_encode(["error" => "Invalid credentials"]);
                error_log("Password mismatch for user " . $data['email']);
                break;
            }

            $token = createJWT($user['user_id'], $user['role_id']);
            error_log("JWT token created: " . $token);

           // logAction($conn, $user['user_id'], 'LOGIN', 'auth', $user['user_id'], 'User login');

            echo json_encode([
                "token" => $token,
                "user" => [
                    "user_id" => $user['user_id'],
                    "full_name" => $user['full_name'],
                    "role_id" => $user['role_id']
                ]
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }

    $conn->close();
}
?>