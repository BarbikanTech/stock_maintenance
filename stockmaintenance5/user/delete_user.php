<?php
include '../dbconfig/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->username)) {
        $username = $data->username;

        $query = "UPDATE users SET deleted_at = 1 WHERE username = :username AND deleted_at = 0";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "User soft deleted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "User not found or already deleted."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid input data."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
