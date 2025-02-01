<?php
include '../dbconfig/config.php';

$username = isset($_GET['username']) ? $_GET['username'] : die(json_encode(["success" => false, "message" => "Username not provided."]));

$query = "SELECT id, unique_id, name_id, name, username, role, created_at 
          FROM users WHERE username = :username AND deleted_at = 0";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username);

if ($stmt->execute()) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["success" => true, "data" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Failed to retrieve user."]);
}
?>
