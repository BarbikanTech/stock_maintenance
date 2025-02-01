<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all sales records and associated sales_mrp records
    $stmt = $pdo->prepare("
        SELECT s.*, sm.*
        FROM sales s
        LEFT JOIN sales_mrp sm ON s.order_id = sm.order_id
        WHERE s.deleted_at = 0  -- Ensure only non-deleted records are fetched
        ORDER BY s.created_date DESC
    ");
    $stmt->execute();
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are any records
    if ($salesData) {
        echo json_encode([
            'status' => '200',
            'message' => 'Sales and sales_mrp records fetched successfully',
            'data' => $salesData
        ]);
    } else {
        echo json_encode([
            'status' => '404',
            'message' => 'No sales records found'
        ]);
    }
} catch (PDOException $e) {
    // Log error and return message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'status' => '500',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>
