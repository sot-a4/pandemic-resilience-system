<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jwt_handler.php';
require_once __DIR__ . '/../security.php';

function handleDocumentRequest($method, $id) {
    $conn = getConnection();

    switch ($method) {
        case 'GET':
            $userData = authenticate();

            if ($id) {
                // Get specific document
                $stmt = $conn->prepare("SELECT d.*, u.full_name as uploaded_by_name 
                                        FROM documents d
                                        JOIN users u ON d.uploaded_by = u.user_id
                                        WHERE d.document_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $doc = $result->fetch_assoc();

                    // Check if user has access to this document
                    if ($userData['role_id'] >= 3 || $userData['user_id'] == $doc['uploaded_by']) {
                        echo json_encode($doc);
                    } else {
                        http_response_code(403);
                        echo json_encode(["error" => "Access denied"]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Document not found"]);
                }
            } else {
                // Check for user_id in query parameters
                $query_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

                if ($query_user_id && ($userData['role_id'] >= 3 || $userData['user_id'] == $query_user_id)) {
                    // Get documents for specific user
                    $stmt = $conn->prepare("SELECT d.*, u.full_name as uploaded_by_name 
                                           FROM documents d
                                           JOIN users u ON d.uploaded_by = u.user_id
                                           WHERE d.uploaded_by = ?");
                    $stmt->bind_param("i", $query_user_id);
                } else if ($userData['role_id'] >= 3) {
                    // Admins see all documents
                    $stmt = $conn->prepare("SELECT d.*, u.full_name as uploaded_by_name 
                                           FROM documents d
                                           JOIN users u ON d.uploaded_by = u.user_id");
                } else {
                    // Regular users see their own documents
                    $stmt = $conn->prepare("SELECT d.*, u.full_name as uploaded_by_name 
                                           FROM documents d
                                           JOIN users u ON d.uploaded_by = u.user_id
                                           WHERE d.uploaded_by = ?");
                    $stmt->bind_param("i", $userData['user_id']);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            }
            break;

        case 'POST':
            // Upload document
            $userData = authenticate();

            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(["error" => "No file uploaded"]);
                exit;
            }

            $file = $_FILES['file'];

            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : $userData['user_id'];
            $document_type = $_POST['document_type'] ?? 'other';

            // Regular users can only upload for themselves
            if ($userData['role_id'] < 3 && $user_id != $userData['user_id']) {
                http_response_code(403);
                echo json_encode(["error" => "Permission denied"]);
                exit;
            }

            // Validate file type and size
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid file type. Only PDF, JPEG, and PNG are allowed."]);
                exit;
            }

            if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                http_response_code(400);
                echo json_encode(["error" => "File too large. Maximum size is 10MB."]);
                exit;
            }

            // Ensure uploads directory exists
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            // Generate unique filename
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid() . '.' . $fileExt;
            $targetPath = UPLOAD_DIR . $newFilename;

            // Move file to upload directory
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Insert document info in database (without file_hash)
                $stmt = $conn->prepare("INSERT INTO documents (file_path, file_type, uploaded_by, upload_date) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("ssi", $newFilename, $file['type'], $userData['user_id']);

                if ($stmt->execute()) {
                    $document_id = $conn->insert_id;

                    http_response_code(201);
                    echo json_encode([
                        "status" => "success",
                        "document_id" => $document_id,
                        "filename" => $newFilename
                    ]);
                    exit;
                } else {
                    // Remove file if DB insert fails
                    unlink($targetPath);
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to record document upload"]);
                    exit;
                }
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to save uploaded file"]);
                exit;
            }
            break;

case 'DELETE':
    if (!$id) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(["error" => "Document ID required"]);
        return; // <- Important: stop execution
    }

    $userData = checkPermission(2); // Healthcare provider or higher

    $stmt = $conn->prepare("SELECT file_path, uploaded_by FROM documents WHERE document_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Document not found"]);
        return;
    }

    $doc = $result->fetch_assoc();

    if ($userData['role_id'] < 3 && $userData['user_id'] != $doc['uploaded_by']) {
        http_response_code(403);
        echo json_encode(["error" => "Permission denied"]);
        return;
    }

    $filePath = UPLOAD_DIR . $doc['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $conn->prepare("DELETE FROM documents WHERE document_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Document deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete document"]);
    }

    return;


        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
}

?>
