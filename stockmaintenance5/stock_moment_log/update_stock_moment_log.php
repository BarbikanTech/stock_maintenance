<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database connection
require '../dbconfig/config.php';

// Ensure database connection exists
if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Retrieve JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate required fields
if (!isset($data['unique_id'], $data['date'], $data['product_id'], $data['mrp'], $data['lob'])) {
    echo json_encode(['error' => 'Invalid input data.']);
    exit;
}

// Assign input variables
$unique_id = $data['unique_id'];
$date = $data['date'];
$product_id = $data['product_id'];
$mrp = $data['mrp'];
$lob = $data['lob'];

try {
    // Fetch product details
    $stmt = $pdo->prepare("SELECT product_name, sku FROM product WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
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

        echo json_encode(['success' => 'Data updated successfully.']);
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

        echo json_encode(['success' => 'New data inserted successfully.']);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
