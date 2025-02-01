<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Query to fetch products, their MRP details, and both Purchase and Sales quantities
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS `ID`,
            p.product_id AS `Product ID`,
            p.product_name AS `Product Name`,
            p.sku AS `SKU`,
            pm.mrp AS `MRP`,
            COALESCE(
                (
                    SELECT 
                        SUM(sm.quantity)
                    FROM sales_mrp sm
                    WHERE sm.product_id = p.product_id AND sm.mrp = pm.mrp
                ), 0) AS `Sales`,
            COALESCE(
                (
                    SELECT 
                        SUM(pur_mrp.quantity)
                    FROM purchase_mrp pur_mrp
                    WHERE pur_mrp.product_id = p.product_id AND pur_mrp.mrp = pm.mrp
                ), 0) AS `Purchase`,
            COALESCE(
                (
                    SELECT 
                        CASE 
                            WHEN SUM(sm.quantity) = 0 THEN 'Hold'
                            WHEN SUM(sm.quantity) BETWEEN 1 AND 10 THEN 'Light Move'
                            ELSE NULL
                        END
                    FROM sales_mrp sm
                    WHERE sm.product_id = p.product_id AND sm.mrp = pm.mrp
                ),
                'Hold'
            ) AS `Status`
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

        // Grouping product details and MRP details together
        if (!isset($groupedData[$productId])) {
            $groupedData[$productId] = [
                'ID' => $row['ID'],
                'Product ID' => $row['Product ID'],
                'Product Name' => $row['Product Name'],
                'SKU' => $row['SKU'],
                'mrp_details' => []
            ];
        }

        // Only add MRP details with valid statuses (Light Move or Hold with sales)
        if ($row['Status']) {
            $groupedData[$productId]['mrp_details'][] = [
                'MRP' => $row['MRP'],
                'Purchase' => $row['Purchase'],
                'Sales' => $row['Sales']
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
