<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error_log.txt');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->name) && isset($data->username) && isset($data->password) && isset($data->role)) {
        $name = htmlspecialchars(strip_tags($data->name));
        $username = htmlspecialchars(strip_tags($data->username));
        $password = htmlspecialchars(strip_tags($data->password));
        $role = htmlspecialchars(strip_tags($data->role));

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $unique_id = uniqid();

        $prefix = strtoupper(substr($role, 0, 3));

        do {
            // Get last name_id
            $query = "SELECT name_id FROM users WHERE role = :role ORDER BY id DESC LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            $last_id = $stmt->fetchColumn();

            if ($last_id) {
                $last_number = intval(substr($last_id, 4));
                $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $new_number = '001';
            }

            $name_id = $prefix . '_' . $new_number;

            // Check if name_id already exists
            $query = "SELECT COUNT(*) FROM users WHERE name_id = :name_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':name_id', $name_id);
            $stmt->execute();
            $exists = $stmt->fetchColumn();

        } while ($exists > 0); // Regenerate if it already exists

        // Insert user
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
    } else {
        throw new Exception("Invalid input data.");
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["success" => 400, "message" => "Internal Server Error. Check logs."]);
}

?>
