<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
require '../dbconfig/config.php';

// Retrieve JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['unique_id'], $data['date'], $data['product_id'], $data['mrp'], $data['lob'])) {
    echo json_encode(['error' => 'Invalid input data.']);
    exit;
}

$unique_id = $data['unique_id'];  // Use unique_id to identify and update the record
$date = $data['date'];
$product_id = $data['product_id'];
$mrp = $data['mrp'];
$lob = $data['lob'];

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT product_name, sku FROM product WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found.']);
        exit;
    }

    $product_name = $product['product_name'];
    $sku = $product['sku'];

    // Get physical_stock from product_mrp table
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
            SUM(CASE WHEN types = 'inward' THEN 1 ELSE 0 END) AS inward_count,
            SUM(CASE WHEN types = 'outward' THEN 1 ELSE 0 END) AS outward_count
        FROM stock_history 
        WHERE mrp = :mrp
    ");
    $stmt->execute(['mrp' => $mrp]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $inward_count = $counts['inward_count'] ?? 0;
    $outward_count = $counts['outward_count'] ?? 0;

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
    }  

    // Generate CSV file
    $stmt = $pdo->query("SELECT id, product_name, sku, mrp, lob, inward, outward, available_piece FROM stock_moment_log");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $csvFile = '../exports/stock_moment_log.csv';
    $file = fopen($csvFile, 'w');
    fputcsv($file, ['ID', 'Product Name', 'SKU', 'MRP', 'LOB', 'Inward', 'Outward', 'Available Piece']);
    foreach ($results as $row) {
        fputcsv($file, $row);
    }
    fclose($file);

    echo json_encode(['success' => 'CSV generated successfully.', 'csv_file' => $csvFile]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
