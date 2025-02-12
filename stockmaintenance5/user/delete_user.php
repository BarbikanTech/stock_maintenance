<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error_log.txt');

// Allow CORS for all origins
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->unique_id)) {
        $unique_id = htmlspecialchars(strip_tags($data->unique_id));

        // Check if user exists and is not already deleted
        $query = "SELECT * FROM users WHERE unique_id = :unique_id AND deleted_at = 0";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':unique_id', $unique_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Soft delete user by setting deleted_at to current timestamp
            $deleteQuery = "UPDATE users SET deleted_at = 1 WHERE unique_id = :unique_id";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->bindParam(':unique_id', $unique_id);

            if ($deleteStmt->execute()) {
                echo json_encode(["success" => 200, "message" => "User deleted successfully."]);  // User deleted successfully
            } else {
                echo json_encode(["success" => 400, "message" => "Failed to delete user."]);  // Failed to delete user
            }
        } else {
            echo json_encode(["success" => 400, "message" => "User not found or already deleted."]);  // User not found or already deleted 
        }
    } else {
        echo json_encode(["success" => 400, "message" => "Invalid input data."]); // Data is incomplete or invalid (missing unique_id)
    }
} else {
    echo json_encode(["success" => 400, "message" => "Invalid request method."]);  // Invalid request method used (not DELETE)
}
?>
