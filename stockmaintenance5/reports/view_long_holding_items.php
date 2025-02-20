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

    // Convert dates to Y-m-d format
    $fromDate = date('Y-m-d', strtotime($inputData['from_date']));
    $toDate = date('Y-m-d', strtotime($inputData['to_date']));

    // Define the threshold date for 6 months ago
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

    // Query to fetch products, their MRP details, and calculate the status
    $stmt = $pdo->prepare("
        SELECT 
            pm.mrp AS `MRP`,
            p.id AS `ID`,
            p.product_id AS `Product_ID`,
            p.product_name AS `Product_Name`,
            p.sku AS `SKU`,
            CASE
                WHEN NOT EXISTS (
                    SELECT 1 
                    FROM sales_mrp sm
                    INNER JOIN sales s ON sm.id = s.id
                    WHERE sm.product_id = pm.product_id
                    AND sm.mrp = pm.mrp 
                    AND s.created_date BETWEEN :from_date AND :to_date
                ) THEN 'Hold'
                WHEN (
                    SELECT SUM(sm.quantity)
                    FROM sales_mrp sm
                    INNER JOIN sales s ON sm.id = s.id
                    WHERE sm.product_id = pm.product_id 
                    AND sm.mrp = pm.mrp 
                    AND s.created_date BETWEEN :from_date AND :to_date
                ) BETWEEN 1 AND 20 THEN 'Light Move'
                ELSE 'Active'
            END AS `Status`
        FROM 
            product_mrp pm
        LEFT JOIN 
            product p ON pm.product_id = p.product_id
        ORDER BY 
            p.id ASC, pm.mrp ASC
    ");

    // Bind parameters
    $stmt->execute([
        'from_date' => $fromDate,
        'to_date' => $toDate
    ]);

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the final structured data
    $products = [];
    $groupedData = [];

    foreach ($results as $row) {
        $productId = $row['Product_ID'];

        // Only include MRP details if the status is 'Hold'
        if ($row['Status'] == 'Hold') {
            if (!isset($groupedData[$productId])) {
                $groupedData[$productId] = [
                    'ID' => $row['ID'],
                    'Product_ID' => $row['Product_ID'],
                    'Product_Name' => $row['Product_Name'],
                    'SKU' => $row['SKU'],
                    'mrp_details' => []
                ];
            }

            // Add MRP details with 'Hold' status
            $groupedData[$productId]['mrp_details'][] = [
                'ID' => $row['ID'],
                'Product_ID' => $row['Product_ID'],
                'Product_Name' => $row['Product_Name'],
                'SKU' => $row['SKU'],
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
        'status' => '200',
        'six_months_ago' => $sixMonthsAgo, // Including the threshold date in the response
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
