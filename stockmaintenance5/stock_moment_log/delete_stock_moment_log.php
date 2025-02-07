<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Retrieve the unique_id from the input data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Validate the required data
    if (!isset($data['unique_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unique ID is required']);
        exit;
    }

    $unique_id = $data['unique_id'];

    // Check if the record exists and is not already marked as deleted (soft delete flag)
    $stmt = $pdo->prepare("SELECT * FROM stock_moment_log WHERE unique_id = :unique_id AND deleted_at = 0");
    $stmt->execute(['unique_id' => $unique_id]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo json_encode(['status' => 'error', 'message' => 'Record not found or already soft deleted']);
        exit;
    }

    // Soft delete the record by updating the 'deleted_at' column (mark as deleted)
    $stmt = $pdo->prepare("UPDATE stock_moment_log SET deleted_at = 1 WHERE unique_id = :unique_id AND deleted_at = 0");
    $stmt->execute(['unique_id' => $unique_id]);

    // Return success response
    echo json_encode(['status' => 'success', 'message' => 'Record soft deleted successfully']);
} catch (PDOException $e) {
    // Log detailed error message for server debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response with the error details for debugging
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage() // Provide error details in the response
    ]);
}
?>
