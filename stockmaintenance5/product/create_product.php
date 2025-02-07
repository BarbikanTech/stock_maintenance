<?php 
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include_once '../dbconfig/config.php';  

// Decode the incoming JSON request
$requestPayload = file_get_contents("php://input");
$data = json_decode($requestPayload, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Extract product details
$productName = $data['product_name'];
$unit = $data['unit'];
$subunit = $data['subunit'];
$mrpDetails = $data['mrp_details'];

try {
    // Insert product into the product table
    $stmt = $pdo->prepare("INSERT INTO product (unique_id, date, product_id, product_name, sku, unit, subunit) 
                           VALUES (UUID(), NOW(), '', :product_name, '', :unit, :subunit)");
    $stmt->execute([
        ':product_name' => $productName,
        ':unit' => $unit,
        ':subunit' => $subunit,
    ]);

    // Get the last inserted product ID
    $productId = $pdo->lastInsertId();

    // Update the product_id and sku
    $productUniqueId = sprintf('PROD-%03d', $productId);
    $productSku = sprintf('%03d', $productId + 500);
    $pdo->prepare("UPDATE product SET product_id = :product_id, sku = :sku WHERE id = :id")
        ->execute([
            ':product_id' => $productUniqueId,
            ':sku' => $productSku,
            ':id' => $productId,
        ]);

    // Insert MRP details into the product_mrp table
    $mrpStmt = $pdo->prepare("INSERT INTO product_mrp (unique_id, product_id, mrp, opening_stock, current_stock, minimum_stock, excess_stock, physical_stock, notification) 
                              VALUES (UUID(), :product_id, :mrp, :opening_stock, :current_stock, :minimum_stock, :excess_stock, :physical_stock, :notification)");

    foreach ($mrpDetails as $mrp) {
        // Calculate the physical_stock as current_stock + excess_stock
        $physicalStock = $mrp['current_stock'] + $mrp['excess_stock'];
        
        // Check if the physical_stock is less than the minimum_stock and set notification
        $notification = ($physicalStock < $mrp['minimum_stock']) ? 'Low stock warning' : '';

        // Execute the MRP insert statement with calculated fields
        $mrpStmt->execute([
            ':product_id' => $productUniqueId,
            ':mrp' => $mrp['mrp'],
            ':opening_stock' => $mrp['opening_stock'],
            ':current_stock' => $mrp['current_stock'],
            ':minimum_stock' => $mrp['minimum_stock'],
            ':excess_stock' => $mrp['excess_stock'],
            ':physical_stock' => $physicalStock,
            ':notification' => $notification
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Product created successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
