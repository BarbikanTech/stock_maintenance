<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

// Retrieve raw POST data if it's in JSON format
$data = json_decode(file_get_contents("php://input"), true);

// Check if order_id exists in the JSON payload
if (isset($data['order_id'])) {
    $order_id = $data['order_id'];

    try {
        // Begin a transaction to ensure atomicity (both updates are successful or none)
        $pdo->beginTransaction();

        // Prepare SQL query to update the "deleted_at" flag to 1 in the sales table
        $stmt_sales = $pdo->prepare("UPDATE sales SET deleted_at = 1 WHERE order_id = :order_id");

        // Bind the order_id parameter for sales table
        $stmt_sales->bindParam(':order_id', $order_id, PDO::PARAM_STR);

        // Execute the query for the sales table
        $stmt_sales->execute();

        // Prepare SQL query to update the "deleted_at" flag to 1 in the sales_mrp table
        $stmt_sales_mrp = $pdo->prepare("UPDATE sales_mrp SET deleted_at = 1 WHERE order_id = :order_id");

        // Bind the order_id parameter for sales_mrp table
        $stmt_sales_mrp->bindParam(':order_id', $order_id, PDO::PARAM_STR);

        // Execute the query for the sales_mrp table
        $stmt_sales_mrp->execute();

        // Check if any rows were affected in either table
        if ($stmt_sales->rowCount() > 0 || $stmt_sales_mrp->rowCount() > 0) {
            // Commit the transaction if both updates were successful
            $pdo->commit();

            // Success response
            echo json_encode([
                'status' => '200',
                'message' => 'Sales and sales_mrp records soft deleted successfully.'
            ]);
        } else {
            // If no rows were updated, the order_id might not exist in either table
            echo json_encode([
                'status' => '404',
                'message' => 'Sales record not found in either sales or sales_mrp.'
            ]);
        }
    } catch (PDOException $e) {
        // Rollback the transaction if there was an error
        $pdo->rollBack();

        // Log error and return message
        error_log('Database error: ' . $e->getMessage());
        echo json_encode([
            'status' => '500',
            'message' => 'Database error occurred: ' . $e->getMessage()
        ]);
    }
} else {
    // If no order_id is provided
    echo json_encode([
        'status' => '400',
        'message' => 'Order ID is required.'
    ]);
}
?>
