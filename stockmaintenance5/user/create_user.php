<?php
include '../dbconfig/config.php';

// Get data from POST request
$data = json_decode(file_get_contents("php://input"));

if (isset($data->name) && isset($data->username) && isset($data->password) && isset($data->role)) {
    // Sanitize inputs
    $name = htmlspecialchars(strip_tags($data->name));
    $username = htmlspecialchars(strip_tags($data->username));
    $password = htmlspecialchars(strip_tags($data->password));
    $role = htmlspecialchars(strip_tags($data->role));
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Generate unique_id
    $unique_id = uniqid();

    // Generate name_id
    $prefix = strtoupper(substr($role, 0, 3)); // Example: 'admin' -> 'ADM'
    $query = "SELECT name_id FROM users WHERE role = :role ORDER BY id DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    $last_id = $stmt->fetchColumn();
    
    if ($last_id) {
        // Extract the numeric part and increment
        $last_number = intval(substr($last_id, 4));
        $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
    } else {
        // Start from 001 if no record exists
        $new_number = '001';
    }

    $name_id = $prefix . '_' . $new_number;

    // Insert user into the database
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
        echo json_encode(["success" => true, "message" => "User created successfully.", "name_id" => $name_id]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create user."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
}
?>
