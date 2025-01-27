<?php
// Include the database configuration file
include_once '../dbconfig/config.php'; // Adjust the path as per your directory structure

// Decode the incoming JSON request
$requestPayload = file_get_contents("php://input");
$data = json_decode($requestPayload, true);

header("Content-Type: application/json");

// Validate the input data
if (!$data || !isset($data['product_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing product_id']);
    exit;
}

$productId = $data['product_id'];

try {
    // Begin a transaction to ensure both tables are updated atomically
    $pdo->beginTransaction();

    // Soft delete the product by updating the 'deleted' flag to 1
    $updateProductQuery = "UPDATE product SET deleted = '1' WHERE product_id = :product_id";
    $stmt = $pdo->prepare($updateProductQuery);
    $stmt->execute([':product_id' => $productId]);

    // Soft delete related MRP records as well by setting 'deleted' flag to 1
    $updateMrpQuery = "UPDATE product_mrp SET deleted = '1' WHERE product_id = :product_id";
    $stmt = $pdo->prepare($updateMrpQuery);
    $stmt->execute([':product_id' => $productId]);

    // Commit the transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Product and related MRP details soft deleted successfully']);
} catch (PDOException $e) {
    // Rollback the transaction if an error occurs
    $pdo->rollBack();

    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
