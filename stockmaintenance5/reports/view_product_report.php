<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json'); 

// Include database configuration
require_once '../dbconfig/config.php';

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

$from_date = isset($input['from_date']) ? date('Y-m-d', strtotime($input['from_date'])) : null;
$to_date = isset($input['to_date']) ? date('Y-m-d', strtotime($input['to_date'])) : null;

if (!$from_date || !$to_date) {
    echo json_encode([
        'status' => '400',
        'message' => 'Invalid or missing date range'
    ]);
    exit;
}

try {
    // Query to fetch products, their MRP details, and both Purchase and Sales quantities within date range
    $stmt = $pdo->prepare("  
        SELECT 
            p.id AS ID,
            p.product_id AS Product_ID,
            p.product_name AS Product_Name,
            p.sku AS SKU,
            sh.mrp AS MRP,
            COALESCE(SUM(CASE WHEN sh.types = 'inward' THEN sh.quantity ELSE 0 END), 0) AS Purchase,
            COALESCE(SUM(CASE WHEN sh.types = 'outward' THEN sh.quantity ELSE 0 END), 0) AS Sales
        FROM stock_history sh
        INNER JOIN product p ON sh.product_id = p.product_id
        WHERE sh.created_date BETWEEN :from_date AND :to_date
        GROUP BY sh.product_id, sh.mrp
        ORDER BY p.id ASC, sh.mrp ASC
    ");

    $stmt->execute([
        ':from_date' => $from_date,
        ':to_date' => $to_date
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the final structured data
    $products = [];
    $groupedData = [];

    foreach ($results as $row) {
        $productId = $row['Product_ID'];

        if (!isset($groupedData[$productId])) {
            $groupedData[$productId] = [
                'ID' => $row['ID'],
                'Product_ID' => $row['Product_ID'],
                'Product_Name' => $row['Product_Name'],
                'SKU' => $row['SKU'],
                'mrp_details' => []
            ];
        }

        $groupedData[$productId]['mrp_details'][] = [
            'ID' => $row['ID'],
            'Product_ID' => $row['Product_ID'],
            'Product_Name' => $row['Product_Name'],
            'SKU' => $row['SKU'],
            'MRP' => number_format($row['MRP'], 2),
            'Purchase' => (int)$row['Purchase'],
            'Sales' => (int)$row['Sales']
        ];
    }

    foreach ($groupedData as $product) {
        $products[] = $product;
    }

    echo json_encode([
        'status' => '200',
        'products' => $products
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'status' => '400',
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage()
    ]);
}
?>
