<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include '../dbconfig/config.php';

$query = "SELECT name, username, role FROM users WHERE deleted_at = 0";

$stmt = $pdo->prepare($query);

if ($stmt->execute()) {
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        echo json_encode(["success" => 200, "data" => $users]);
    } else {
        echo json_encode(["success" => 400, "message" => "No users found."]);
    }
} else {
    echo json_encode(["success" => 400, "message" => "Failed to retrieve users."]);
}
?>
