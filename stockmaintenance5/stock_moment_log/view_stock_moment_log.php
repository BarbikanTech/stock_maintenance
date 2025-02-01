<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all stock moment log records with product details
    $stmt = $pdo->prepare("
        SELECT 
            sm.unique_id,
            sm.date,
            sm.product_id,
            p.product_name,
            p.sku,
            sm.mrp,
            sm.lob,
            sm.inward,
            sm.outward,
            sm.available_piece
        FROM 
            stock_moment_log sm
        LEFT JOIN 
            product p ON sm.product_id = p.product_id
        ORDER BY 
            sm.date DESC
    ");
    $stmt->execute();

    $stock_moment_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($stock_moment_logs) {
        echo json_encode([
            'status' => 'success',
            'data' => $stock_moment_logs
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'No stock moment logs found',
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
