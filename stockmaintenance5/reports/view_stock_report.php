<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Get the JSON input
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    // Validate date inputs
    $from_date = isset($input['from_date']) ? DateTime::createFromFormat('d-m-Y', $input['from_date']) : null;
    $to_date = isset($input['to_date']) ? DateTime::createFromFormat('d-m-Y', $input['to_date']) : null;

    if (!$from_date || !$to_date) {
        echo json_encode([
            "status" => "400",
            "message" => "Invalid date format. Please use 'dd-mm-yyyy'."
        ]);
        exit;
    }

    // Convert to MySQL format (YYYY-MM-DD)
    $from_date = $from_date->format('Y-m-d');
    $to_date = $to_date->format('Y-m-d');

    // Fetch products with MRP details within the date range
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS ID,
            p.product_id AS Product_ID,
            p.product_name AS Product_Name,
            p.sku AS SKU,
            p.subunit AS Subunit,
            pm.id AS MRP_ID,
            pm.mrp AS MRP,
            pm.current_stock AS DMS,
            pm.excess_stock AS Excess_Pieces,
            pm.physical_stock AS Physical_Stock,
            pm.notification AS Notification,
            pm.created_date AS Created_Date
        FROM 
            product p
        LEFT JOIN 
            product_mrp pm ON p.product_id = pm.product_id
        WHERE 
            DATE(pm.created_date) BETWEEN :from_date AND :to_date
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");

    $stmt->bindParam(':from_date', $from_date);
    $stmt->bindParam(':to_date', $to_date);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    $productMap = [];

    // Process the data
    foreach ($results as $row) {
        $id = $row['ID'];

        // Format Physical Notification text
        $physical = is_numeric($row['Physical_Stock']) ? $row['Physical_Stock'] . ' ' . $row['Notification'] : $row['Notification'];

        // Prepare the MRP details
        $mrpDetails = [
            "ID" => $row['MRP_ID'],
            "Product_ID" => $row['Product_ID'],
            "Product_Name" => $row['Product_Name'],
            "SKU" => $row['SKU'],
            "Subunit" => $row['Subunit'],
            "MRP" => $row['MRP'],
            "DMS" => $row['DMS'],
            "Excess_Pieces" => $row['Excess_Pieces'],
            "Physical_Stock" => $physical,
            "Created_Date" => $row['Created_Date']
        ];

        // Group data by product ID
        if (!isset($productMap[$id])) {
            $productMap[$id] = [
                "ID" => $id,
                "Product_ID" => $row['Product_ID'],
                "Product_Name" => $row['Product_Name'],
                "SKU" => $row['SKU'],
                "mrp_details" => []
            ];
        }

        // Add the MRP details to the product
        $productMap[$id]['mrp_details'][] = $mrpDetails;
    }

    // Reindex the product data
    $products = array_values($productMap);

    // Return the JSON response
    echo json_encode([
        "status" => "200",
        "products" => $products
    ]);

} catch (PDOException $e) {
    // Log detailed error message for server debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response with the error details for debugging
    echo json_encode([
        "status" => "400",
        "message" => "Database error occurred",
        "error_details" => $e->getMessage()
    ]);
}
?>
