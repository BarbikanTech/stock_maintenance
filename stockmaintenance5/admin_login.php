<?php
header("Content-Type: application/json");
include 'dbconfig/config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['adminID']) || !isset($input['password'])) {
        echo json_encode(["error" => "Admin ID and Password are required."]);
        http_response_cod   e(400); // Bad Request
        exit;
    }

    $adminID = $input['adminID'];
    $password = $input['password'];

    try {
        // Query the database to check for valid credentials
        $query = $conn->prepare("SELECT * FROM AdminLogin WHERE AdminID = :adminID AND Password = :password");
        $query->bindParam(':adminID', $adminID);
        $query->bindParam(':password', $password);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['admin'] = $adminID;

            echo json_encode([
                "message" => "Login successful",
                "adminID" => $adminID,
            ]);
            http_response_code(200); // OK
        } else {
            echo json_encode(["error" => "Invalid Admin ID or Password."]);
            http_response_code(401); // Unauthorized
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} else {
    echo json_encode(["error" => "Method not allowed."]);
    http_response_code(405); // Method Not Allowed
}
?>
