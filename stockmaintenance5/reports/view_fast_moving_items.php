<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Define the threshold dates for status calculation
    // $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

    // Query to fetch products and their MRP details with sale-based filtering
    $stmt = $pdo->prepare(" 
        SELECT 
            p.id AS `ID`,
            p.product_id AS `Product ID`,
            p.product_name AS `Product Name`,
            p.sku AS `SKU`,
            pm.mrp AS `MRP`
        FROM 
            product p
        LEFT JOIN 
            product_mrp pm ON p.product_id = pm.product_id
        WHERE EXISTS (
            SELECT 1 
            FROM sales_mrp sm
            WHERE sm.product_id = p.product_id AND sm.mrp = pm.mrp
        )
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");


    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);       

    // Prepare the final structured data
    $products = [];
    $groupedData = [];

    foreach ($results as $row) {
        $productId = $row['Product ID'];

        // Include MRP details
        if (!isset($groupedData[$productId])) {
            $groupedData[$productId] = [
                'ID' => $row['ID'],
                'Product ID' => $row['Product ID'],
                'Product Name' => $row['Product Name'],
                'SKU' => $row['SKU'],
                'mrp_details' => []
            ];
        }

        // Add MRP details without status
        $groupedData[$productId]['mrp_details'][] = [
            'MRP' => $row['MRP']
        ];
    }

    // Convert grouped data to indexed array
    foreach ($groupedData as $product) {
        $products[] = $product;
    }

    // Output the JSON response
    echo json_encode([
        'status' => 'success',
        'products' => $products
    ]);
} catch (PDOException $e) {
    // Log error for debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage()
    ]);
}
?>
