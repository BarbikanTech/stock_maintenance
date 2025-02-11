<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error_log.txt');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

try {
    // Read input data
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    if (!isset($data->name, $data->username, $data->password, $data->role)) {
        throw new Exception("Missing required fields.");
    }

    // Sanitize input
    $name = htmlspecialchars(strip_tags($data->name));
    $username = htmlspecialchars(strip_tags($data->username));
    $password = htmlspecialchars(strip_tags($data->password));
    $role = htmlspecialchars(strip_tags($data->role));

    // Check if username already exists
    $query = "SELECT COUNT(*) FROM users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Username already exists.");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $unique_id = uniqid();

    // Generate name_id
    $prefix = strtoupper(substr($role, 0, 3));
    $query = "SELECT name_id FROM users WHERE role = :role ORDER BY id DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    $last_id = $stmt->fetchColumn();

    $new_number = $last_id ? str_pad(intval(substr($last_id, 4)) + 1, 3, '0', STR_PAD_LEFT) : '001';
    $name_id = $prefix . '_' . $new_number;

    // Ensure unique name_id
    $query = "SELECT COUNT(*) FROM users WHERE name_id = :name_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name_id', $name_id);
    $stmt->execute();

    while ($stmt->fetchColumn() > 0) {
        $new_number = str_pad(intval($new_number) + 1, 3, '0', STR_PAD_LEFT);
        $name_id = $prefix . '_' . $new_number;
        $stmt->bindParam(':name_id', $name_id);
        $stmt->execute();
    }

    // Insert user into database
    $query = "INSERT INTO users (unique_id, name_id, name, username, password, role) 
              VALUES (:unique_id, :name_id, :name, :username, :password, :role)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':unique_id', $unique_id);
    $stmt->bindParam(':name_id', $name_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        echo json_encode(["success" => 200, "message" => "User created successfully.", "name_id" => $name_id]);
    } else {
        throw new Exception("Database insertion failed.");
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["success" => 400, "message" => $e->getMessage()]);
}
?>
