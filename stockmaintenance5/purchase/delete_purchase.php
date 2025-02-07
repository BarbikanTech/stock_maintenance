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

// Validate required fields
if (!isset($input['order_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get order_id from input
$orderId = $input['order_id'];

try {
    // Check if the purchase order exists
    $stmt = $pdo->prepare("SELECT * FROM purchase WHERE order_id = :order_id AND deleted_at = 0");
    $stmt->execute([':order_id' => $orderId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Purchase order not found or already deleted'
        ]);
        exit;
    }

    // Soft delete in the purchase table
    $stmt = $pdo->prepare("UPDATE purchase SET deleted_at = 1 WHERE order_id = :order_id");
    $stmt->execute([':order_id' => $orderId]);

    // Soft delete in the purchase_mrp table
    $stmt = $pdo->prepare("UPDATE purchase_mrp SET deleted_at = 1 WHERE order_id = :order_id");
    $stmt->execute([':order_id' => $orderId]);

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase order and related products marked as deleted successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
