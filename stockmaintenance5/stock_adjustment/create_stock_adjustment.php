<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once '../dbconfig/config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date']) || !isset($data['product_id']) || !isset($data['mrp']) || !isset($data['adjusted_stock']) || !isset($data['adjusted_type']) || !isset($data['reason'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$date = $data['date'];
$productId = $data['product_id'];
$mrp = (float)$data['mrp']; // Cast mrp to float
$adjustedStock = $data['adjusted_stock'];
$adjustedType = $data['adjusted_type'];
$reason = $data['reason'];

try {
    // Get product details from product table
    $stmt = $pdo->prepare("SELECT product_name FROM product WHERE product_id = :productId"); 
    $stmt->bindParam(':productId', $productId);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Get stock details from product_mrp table, including mrp
    $stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, excess_stock, minimum_stock FROM product_mrp WHERE product_id = :productId AND mrp = :mrp"); 
    $stmt->bindParam(':productId', $productId);
    $stmt->bindParam(':mrp', $mrp, PDO::PARAM_STR); // Bind mrp as a string (adjust as needed based on your database schema)
    $stmt->execute();
    $stockData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stockData) {
        http_response_code(404);
        echo json_encode(['error' => 'Stock data not found for the product and MRP']); 
        exit;
    }

    if ($adjustedType === 'subtract') {
        $newCurrentStock = $stockData['current_stock'] - $adjustedStock;
    } else ($adjustedType === 'add') { 
        $newCurrentStock = $stockData['current_stock'] + $adjustedStock;
    }

    // Calculate new physical stock
    $newPhysicalStock = $newCurrentStock + $stockData['excess_stock'];

    // Generate stock ID (STO-001, STO-002, etc.)
    $lastStockId = $pdo->query("SELECT stock_id FROM stock_adjustment ORDER BY id DESC LIMIT 1")->fetchColumn(); 
    if (!$lastStockId) {
        $stockId = "STO-001"; 
    } else {
        $lastNumber = intval(substr($lastStockId, 4)); 
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT); 
        $stockId = "STO-" . $nextNumber; 
    }

    // Insert stock adjustment record
    $stmt = $pdo->prepare("INSERT INTO stock_adjustment (unique_id, date, stock_id, product_id, product_name, mrp, adjusted_stock, adjusted_type, reason) 
                            VALUES (:uniqueId, :date, :stockId, :productId, :productName, :mrp, :adjustedStock, :adjustedType, :reason)");
    $uniqueId = uniqid(); // Generate unique ID
    $productName = $product['product_name'];
    $stmt->bindParam(':uniqueId', $uniqueId);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':stockId', $stockId);
    $stmt->bindParam(':productId', $productId);
    $stmt->bindParam(':productName', $productName);
    $stmt->bindParam(':mrp', $mrp); // Bind mrp directly without quotes
    $stmt->bindParam(':adjustedStock', $adjustedStock);
    $stmt->bindParam(':adjustedType', $adjustedType);
    $stmt->bindParam(':reason', $reason);
    $stmt->execute();

    // Update product_mrp table
    $stmt = $pdo->prepare("UPDATE product_mrp SET current_stock = :newCurrentStock, physical_stock = :newPhysicalStock WHERE product_id = :productId AND mrp = :mrp"); 
    $stmt->bindParam(':newCurrentStock', $newCurrentStock);
    $stmt->bindParam(':newPhysicalStock', $newPhysicalStock);
    $stmt->bindParam(':productId', $productId);
    $stmt->bindParam(':mrp', $mrp); // Bind mrp directly without quotes
    $stmt->execute();

    // Check and update notification status
    if ($newPhysicalStock <= $stockData['minimum_stock']) {
        $updateNotificationStmt = $pdo->prepare("UPDATE product_mrp SET notification = 'Low stock warning' WHERE product_id = :productId AND mrp = :mrp");
        $updateNotificationStmt->bindParam(':productId', $productId);
        $updateNotificationStmt->bindParam(':mrp', $mrp); 
        $updateNotificationStmt->execute();
    } else {
        $updateNotificationStmt = $pdo->prepare("UPDATE product_mrp SET notification = NULL WHERE product_id = :productId AND mrp = :mrp");
        $updateNotificationStmt->bindParam(':productId', $productId);
        $updateNotificationStmt->bindParam(':mrp', $mrp); 
        $updateNotificationStmt->execute();
    }

    echo json_encode(['message' => 'Stock adjustment created successfully', 'stock_id' => $stockId]); 

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
