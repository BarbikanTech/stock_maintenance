<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['customer_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer ID is required.'
    ]);
    exit;
}

$customerId = $input['customer_id'];

try {
    // Check if the customer exists first
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id AND deleted_at = 0");
    $stmt->execute(['customer_id' => $customerId]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        // Soft delete by setting 'deleted_at' to 1
        $updateStmt = $pdo->prepare("UPDATE customers SET deleted_at = 1 WHERE customer_id = :customer_id");
        $updateStmt->execute(['customer_id' => $customerId]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Customer successfully deleted.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer not found or already deleted.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
