<?php
header('Content-Type: application/json');

// Include database configuration       
require_once '../dbconfig/config.php';

try {
    // Define the threshold date for 6 months ago
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

    // Query to fetch products, their MRP details, and calculate the status
    $stmt = $pdo->prepare("
        SELECT 
            pm.mrp AS `MRP`,
            p.id AS `ID`,
            p.product_id AS `Product ID`,
            p.product_name AS `Product Name`,
            p.sku AS `SKU`,
            CASE
                WHEN NOT EXISTS (
                    SELECT 1 
                    FROM sales s
                    WHERE s.product_id = pm.product_id 
                      AND s.mrp = pm.mrp 
                      AND s.created_date >= :six_months_ago
                ) THEN 'Hold'
                WHEN (
                    SELECT SUM(s.quantity)
                    FROM sales s
                    WHERE s.product_id = pm.product_id 
                      AND s.mrp = pm.mrp 
                      AND s.created_date >= :six_months_ago
                ) BETWEEN 1 AND 10 THEN 'Light Move'
                ELSE 'Active'
            END AS `Status`
        FROM 
            product_mrp pm
        LEFT JOIN 
            product p ON pm.product_id = p.product_id
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");
    $stmt->execute(['six_months_ago' => $sixMonthsAgo]);

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the final structured data
    $products = [];
    $groupedData = [];

    foreach ($results as $row) {
        $productId = $row['Product ID'];

        // Only include MRP details if there is a valid status
        if ($row['Status']) {
            if (!isset($groupedData[$productId])) {
                $groupedData[$productId] = [
                    'ID' => $row['ID'],
                    'Product ID' => $row['Product ID'],
                    'Product Name' => $row['Product Name'],
                    'SKU' => $row['SKU'],
                    'mrp_details' => []
                ];
            }

            // Add MRP details with status
            $groupedData[$productId]['mrp_details'][] = [
                'MRP' => $row['MRP'],
                'Status' => $row['Status']
            ];
        }
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
