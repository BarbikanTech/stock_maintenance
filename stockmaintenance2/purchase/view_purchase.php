<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Prepare SQL query to fetch all purchase records with related vendor and product details
    $stmt = $pdo->prepare("
        SELECT p.unique_id, p.date, p.order_id, p.invoice_number, p.quantity, p.mrp, 
               v.vendor_name, pr.product_name, pr.sku, pr.unit
        FROM purchase p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        JOIN product pr ON p.product_id = pr.product_id
    ");
    
    // Execute the query
    $stmt->execute();

    // Fetch all purchase records
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($purchases) {
        // Success: Return all purchases in the response
        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase records fetched successfully',
            'data' => $purchases
        ]);
    } else {
        // If no records found
        echo json_encode([
            'status' => 'error',
            'message' => 'No purchase records found'
        ]);
    }
} catch (PDOException $e) {
    // Error: Handle database errors
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
