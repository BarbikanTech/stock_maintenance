<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error_log.txt');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

try {
    // Only allow PUT requests
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception("Invalid request method. Only PUT is allowed.");
    }

    // Read JSON input
    $data = json_decode(file_get_contents("php://input"));

    // Validate unique_id
    if (!isset($data->unique_id)) {
        throw new Exception("Missing required field: unique_id.");
    }

    $unique_id = htmlspecialchars(strip_tags($data->unique_id));

    // Fetch existing user details
    $query = "SELECT * FROM users WHERE unique_id = :unique_id AND deleted_at = 0";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':unique_id', $unique_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("User not found.");
    }

    // Fields to update
    $updates = [];
    $params = [':unique_id' => $unique_id];

    if (isset($data->name)) {
        $updates[] = "name = :name";
        $params[':name'] = htmlspecialchars(strip_tags($data->name));
    }

    if (isset($data->username)) {
        $new_username = htmlspecialchars(strip_tags($data->username));

        // Check if username already exists
        $query = "SELECT COUNT(*) FROM users WHERE username = :username AND unique_id != :unique_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $new_username);
        $stmt->bindParam(':unique_id', $unique_id);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists. Choose a different one.");
        }

        $updates[] = "username = :username";
        $params[':username'] = $new_username;
    }

    if (isset($data->password)) {
        $updates[] = "password = :password";
        $params[':password'] = password_hash($data->password, PASSWORD_BCRYPT);
    }

    if (isset($data->role)) {
        $updates[] = "role = :role";
        $params[':role'] = htmlspecialchars(strip_tags($data->role));
    }

    // If no updates, return error
    if (empty($updates)) {
        throw new Exception("No fields to update.");
    }

    // Prepare and execute update query
    $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE unique_id = :unique_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(["success" => 200, "message" => "User updated successfully."]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["success" => 400, "message" => $e->getMessage()]);
}
?>
