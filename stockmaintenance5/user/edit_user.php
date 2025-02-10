<?php
// Allow CORS for all origins
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->unique_id) && (isset($data->name) || isset($data->role) || isset($data->password))) {
        $unique_id = $data->unique_id;
        $name = isset($data->name) ? $data->name : null;
        $role = isset($data->role) ? $data->role : null;
        $newPassword = isset($data->password) ? $data->password : null;

        $query = "SELECT * FROM users WHERE unique_id = :unique_id AND deleted_at = 0";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':unique_id', $unique_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            if ($name) {
                $updateNameQuery = "UPDATE users SET name = :name WHERE unique_id = :unique_id";
                $updateStmt = $pdo->prepare($updateNameQuery);
                $updateStmt->bindParam(':name', $name);
                $updateStmt->bindParam(':unique_id', $unique_id);
                $updateStmt->execute();
            }

            if ($newPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $updatePasswordQuery = "UPDATE users SET password = :password WHERE unique_id = :unique_id";
                $updateStmt = $pdo->prepare($updatePasswordQuery);
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':unique_id', $unique_id);
                $updateStmt->execute();
            }

            if ($role) {
                $updateRoleQuery = "UPDATE users SET role = :role WHERE unique_id = :unique_id";
                $updateStmt = $pdo->prepare($updateRoleQuery);
                $updateStmt->bindParam(':role', $role);
                $updateStmt->bindParam(':unique_id', $unique_id);
                $updateStmt->execute();
            }

            echo json_encode(["success" => true, "message" => "User updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "User not found."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid input data."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
