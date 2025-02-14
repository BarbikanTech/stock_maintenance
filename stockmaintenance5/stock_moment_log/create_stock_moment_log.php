<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require '../dbconfig/config.php';

// Check database connection
if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['date'], $data['product_id'], $data['mrp'], $data['lob'])) {
    echo json_encode(['error' => 'Invalid input data.']);
    exit;
}

$date = $data['date'];
$product_id = $data['product_id'];
$mrp = $data['mrp'];
$lob = $data['lob'];

try {
    $stmt = $pdo->prepare("SELECT product_name, sku FROM product WHERE product_id = :product_id");
    if (!$stmt->execute(['product_id' => $product_id])) {
        echo json_encode(['error' => 'Error fetching product details.']);
        exit;
    }
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT physical_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    if (!$stmt->execute(['product_id' => $product_id, 'mrp' => $mrp])) {
        echo json_encode(['error' => 'Error fetching product MRP details.']);
        exit;
    }
    $product_mrp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_mrp || !isset($product_mrp['physical_stock'])) {
        echo json_encode(['error' => 'MRP not found or missing physical_stock.']);
        exit;
    }

    $physical_stock = $product_mrp['physical_stock'];

    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN types = 'inward' THEN 1 ELSE 0 END) AS inward_count,
            SUM(CASE WHEN types = 'outward' THEN 1 ELSE 0 END) AS outward_count
        FROM stock_history 
        WHERE mrp = :mrp
    ");
    if (!$stmt->execute(['mrp' => $mrp])) {
        echo json_encode(['error' => 'Error fetching stock history.']);
        exit;
    }
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $inward_count = $counts['inward_count'] ?? 0;
    $outward_count = $counts['outward_count'] ?? 0;

    $unique_id = uniqid();
    $stmt = $pdo->prepare("INSERT INTO stock_moment_log (unique_id, date, product_id, product_name, sku, mrp, lob, inward, outward, available_piece) 
                           VALUES (:unique_id, :date, :product_id, :product_name, :sku, :mrp, :lob, :inward, :outward, :available_piece)");
    if (!$stmt->execute([
        'unique_id' => $unique_id,
        'date' => $date,
        'product_id' => $product_id,
        'product_name' => $product['product_name'],
        'sku' => $product['sku'],
        'mrp' => $mrp,
        'lob' => $lob,
        'inward' => $inward_count,
        'outward' => $outward_count,
        'available_piece' => $physical_stock
    ])) {
        echo json_encode(['error' => 'Error inserting stock log.']);
        exit;
    }

    echo json_encode(['success' => 'Data logged successfully.']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
