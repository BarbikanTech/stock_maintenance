<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all sales records with customer and product details
    $stmt = $pdo->prepare("
        SELECT 
            s.unique_id AS sales_id,
            s.date,
            s.order_id,
            s.invoice_number,
            s.customer_id,
            c.customer_name,
            s.product_id,
            p.product_name,
            s.sku,
            s.quantity,
            s.mrp,
            s.product,
            s.sales_through
        FROM 
            sales s
        LEFT JOIN 
            customers c ON s.customer_id = c.customer_id
        LEFT JOIN 
            product p ON s.product_id = p.product_id
        ORDER BY 
            s.date DESC
    ");
    $stmt->execute();

    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sales) {
        echo json_encode([
            'status' => 'success',
            'data' => $sales
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'No sales records found',
            'data' => []
        ]);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage()); // Log error for debugging
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
}
?>
