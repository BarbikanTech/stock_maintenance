<?php
header('Content-Type: application/json');

// Include database configuration
require_once 'dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (!isset($input['username'], $input['password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Username and password are required.'
    ]);
    exit;
}

$username = $input['username'];
$password = $input['password'];

try {
    // Query to fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND deleted_at = 0");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'id' => $user['id'],
                'unique_id' => $user['unique_id'],
                'name' => $user['name'],
                'role' => $user['role'], // Include role in the response
                'created_at' => $user['created_at']
            ]
        ]);
    } else {
        // Invalid credentials
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ]);
    }
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
