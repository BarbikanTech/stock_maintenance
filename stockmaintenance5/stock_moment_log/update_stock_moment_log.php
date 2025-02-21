<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

//Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require '../dbconfig/config.php';

// Ensure database connection exists
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Retrieve JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate required fields
$required_fields = ['unique_id', 'date', 'product_id', 'mrp', 'lob'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Assign input variables
$unique_id = $data['unique_id'];
$date = $data['date'];
$product_id = $data['product_id'];
$mrp = $data['mrp'];
$lob = $data['lob'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Fetch product details
    $stmt = $pdo->prepare("SELECT product_name, sku FROM product WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found.']);
        exit;
    }

    $product_name = $product['product_name'];
    $sku = $product['sku'];

    // Fetch physical stock from product_mrp table
    $stmt = $pdo->prepare("SELECT physical_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    $stmt->execute(['product_id' => $product_id, 'mrp' => $mrp]);
    $product_mrp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_mrp) {
        http_response_code(404);
        echo json_encode(['error' => 'MRP not found for the product.']);
        exit;
    }

    $physical_stock = $product_mrp['physical_stock'];

    // Calculate inward and outward from stock_history
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN types = 'inward' THEN 1 ELSE 0 END), 0) AS inward_count,
            COALESCE(SUM(CASE WHEN types = 'outward' THEN 1 ELSE 0 END), 0) AS outward_count
        FROM stock_history 
        WHERE mrp = :mrp
    ");
    $stmt->execute(['mrp' => $mrp]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $inward_count = $counts['inward_count'];
    $outward_count = $counts['outward_count'];

    // Calculate available pieces
    $available_piece = $physical_stock;

    // Check if record with unique_id exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_moment_log WHERE unique_id = :unique_id");
    $stmt->execute(['unique_id' => $unique_id]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE stock_moment_log SET 
                                date = :date, 
                                product_id = :product_id, 
                                product_name = :product_name, 
                                sku = :sku, 
                                mrp = :mrp, 
                                lob = :lob, 
                                inward = :inward, 
                                outward = :outward, 
                                available_piece = :available_piece 
                               WHERE unique_id = :unique_id");

        $stmt->execute([
            'unique_id' => $unique_id,
            'date' => $date,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'sku' => $sku,
            'mrp' => $mrp,
            'lob' => $lob,
            'inward' => $inward_count,
            'outward' => $outward_count,
            'available_piece' => $available_piece
        ]);

        $message = 'Data updated successfully.';
    } else {
        // Insert new record if unique_id does not exist
        $stmt = $pdo->prepare("INSERT INTO stock_moment_log 
                               (unique_id, date, product_id, product_name, sku, mrp, lob, inward, outward, available_piece) 
                               VALUES 
                               (:unique_id, :date, :product_id, :product_name, :sku, :mrp, :lob, :inward, :outward, :available_piece)");

        $stmt->execute([
            'unique_id' => $unique_id,
            'date' => $date,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'sku' => $sku,
            'mrp' => $mrp,
            'lob' => $lob,
            'inward' => $inward_count,
            'outward' => $outward_count,
            'available_piece' => $available_piece
        ]);

        $message = 'New data inserted successfully.';
    }

    // Commit transaction
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode(['success' => 200, 'message' => $message]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage(), 3, "../logs/error_log.txt");
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
?>
