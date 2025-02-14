<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json'); 

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Read the JSON input
    $inputData = json_decode(file_get_contents("php://input"), true);
    
    // Validate input dates
    if (!isset($inputData['from_date']) || !isset($inputData['to_date'])) {
        echo json_encode([
            'status' => '400',
            'message' => 'Missing from_date or to_date'
        ]);
        exit;
    }

    $fromDate = date('Y-m-d', strtotime($inputData['from_date']));
    $toDate = date('Y-m-d', strtotime($inputData['to_date']));

    // Query to fetch products and their MRP details with sale-based filtering
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS ID,
            p.product_id AS Product_ID,
            p.product_name AS Product_Name,
            p.sku AS SKU,
            pm.mrp AS MRP
        FROM 
            product p
        LEFT JOIN 
            product_mrp pm ON p.product_id = pm.product_id
        WHERE EXISTS (
            SELECT 1 
            FROM sales_mrp sm
            INNER JOIN sales s ON sm.id = s.id
            WHERE sm.product_id = p.product_id 
            AND sm.mrp = pm.mrp
            AND s.created_date BETWEEN :fromDate AND :toDate
        )
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");

    // Bind parameters
    $stmt->bindParam(':fromDate', $fromDate);
    $stmt->bindParam(':toDate', $toDate);
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the final structured data
    $products = [];
    $groupedData = [];

    foreach ($results as $row) {
        $productId = $row['Product_ID'];

        // Include MRP details
        if (!isset($groupedData[$productId])) {
            $groupedData[$productId] = [
                'ID' => $row['ID'],
                'Product_ID' => $row['Product_ID'],
                'Product_Name' => $row['Product_Name'],
                'SKU' => $row['SKU'],
                'mrp_details' => []
            ];
        }

        // Add MRP details
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
        'status' => '200',
        'products' => $products
    ]);
} catch (PDOException $e) {
    // Log error for debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response
    echo json_encode([
        'status' => '400',
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage()
    ]);
}
?>
