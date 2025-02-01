<?php
// Include the database configuration file
include_once '../dbconfig/config.php'; // Adjust the path as per your directory structure

header("Content-Type: application/json");

try {
    // Query to fetch product details excluding soft-deleted products and related MRP data
    $stmt = $pdo->prepare("SELECT 
                                p.product_id, 
                                p.product_name, 
                                p.sku, 
                                p.unit, 
                                p.subunit, 
                                p.date,
                                pm.mrp,
                                pm.opening_stock,
                                pm.current_stock,
                                pm.minimum_stock,
                                pm.excess_stock
                           FROM 
                                product p
                           LEFT JOIN 
                                product_mrp pm ON p.product_id = pm.product_id
                           WHERE 
                                p.delete_at = 0 AND pm.delete_at = 0
                           ORDER BY 
                                p.date ASC");
    $stmt->execute();
    $products = [];

    // Fetch data and structure it into a grouped array for easy response
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productId = $row['product_id'];

        // Organize product data and group MRP details
        if (!isset($products[$productId])) {
            $products[$productId] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'sku' => $row['sku'],
                'unit' => $row['unit'],
                'subunit' => $row['subunit'],
                'date' => $row['date'],
                'mrp_details' => []
            ];
        }

        // Calculate physical stock
        $physicalStock = $row['current_stock'] + $row['excess_stock'];

        // Determine if notification is needed (if current stock is less than minimum stock)
        $notification = ($row['current_stock'] < $row['minimum_stock']) ? 'Low stock warning' : '';

        // Add MRP details including physical stock and notification
        $products[$productId]['mrp_details'][] = [
            'mrp' => $row['mrp'],
            'opening_stock' => $row['opening_stock'],
            'current_stock' => $row['current_stock'],
            'minimum_stock' => $row['minimum_stock'],
            'excess_stock' => $row['excess_stock'],
            'physical_stock' => $physicalStock,
            'notification' => $notification
        ];
    }

    // Return products in a JSON response
    echo json_encode(['status' => 'success', 'products' => array_values($products)]);
} catch (PDOException $e) {
    // Handle database error and return message
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
