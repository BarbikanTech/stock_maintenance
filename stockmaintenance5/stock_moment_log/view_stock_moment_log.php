<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch only active stock moment logs (where deleted_at = 0)
    $stmt = $pdo->prepare("SELECT * FROM stock_moment_log WHERE deleted_at = 0 ORDER BY id ASC");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'status' => '200',
        'data' => $results
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['status' => '400', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
