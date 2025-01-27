<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all products with their MRP details
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS ID,
            p.product_id AS Product_ID,
            p.product_name AS Product_Name,
            p.sku AS SKU,
            p.subunit AS Subunit,
            pm.mrp AS MRP,
            pm.current_stock AS DMS,
            pm.excess_stock AS Excess_Pieces,
            pm.physical_stock AS Physical,
            pm.notification AS Notification
        FROM 
            product p
        LEFT JOIN 
            product_mrp pm ON p.product_id = pm.product_id
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    $productMap = [];

    // Process the data
    foreach ($results as $row) {
        $id = $row['ID'];

        // Format DMS Liter with Subunit text
        $dmsLiter = is_numeric($row['DMS']) ? $row['DMS'] . ' ' . $row['Subunit'] : $row['Subunit'];

        // Format Physical Notification text
        $physical = $row['Notification'] ? $row['Physical'] . ' ' . $row['Notification'] : null;

        // Prepare the MRP details
        $mrpDetails = [
            "MRP" => $row['MRP'],
            "Dms" => $row['DMS'],
            "Dms Liter" => $dmsLiter,
            "Excess Pieces" => $row['Excess_Pieces'],
            "Physical" => $physical
        ];

        // Group data by product ID
        if (!isset($productMap[$id])) {
            $productMap[$id] = [
                "ID" => $id,
                "Product ID" => $row['Product_ID'],
                "Product Name" => $row['Product_Name'],
                "SKU" => $row['SKU'],
                "mrp_details" => []
            ];
        }

        // Add the MRP details to the product if the notification is present
        if ($row['Notification']) {
            $productMap[$id]['mrp_details'][] = $mrpDetails;
        }
    }

    // Reindex the product data
    $products = array_values($productMap);

    // Return the JSON response
    echo json_encode([
        "status" => "success",
        "products" => $products,
        "debug" => $results // Include raw debug information for troubleshooting
    ]);
} catch (PDOException $e) {
    // Log detailed error message for server debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response with the error details for debugging
    echo json_encode([
        "status" => "error",
        "message" => "Database error occurred",
        "error_details" => $e->getMessage() // Provide error details in the response
    ]);
}
?>
