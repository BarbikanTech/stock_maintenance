<?php
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

require_once '../dbconfig/config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['unique_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: unique_id']);
    exit;
}

$uniqueId = $data['unique_id'];

try {
    // Get existing adjustment record
    $stmt = $pdo->prepare("SELECT * FROM stock_adjustment WHERE unique_id = :uniqueId");
    $stmt->bindParam(':uniqueId', $uniqueId);
    $stmt->execute();
    $adjustment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adjustment) {
        http_response_code(404);
        echo json_encode(['error' => 'Stock adjustment not found']);
        exit;
    }

    // Get product details 
    $stmt = $pdo->prepare("SELECT product_name FROM product WHERE product_id = :productId");
    $stmt->bindParam(':productId', $adjustment['product_id']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get original stock data (including minimum_stock)
    $stmt = $pdo->prepare("SELECT current_stock, excess_stock, minimum_stock FROM product_mrp WHERE product_id = :productId AND mrp = :mrp"); 
    $stmt->bindParam(':productId', $adjustment['product_id']);
    $stmt->bindParam(':mrp', $adjustment['mrp']);
    $stmt->execute();
    $originalStockData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$originalStockData) {
        http_response_code(404);
        echo json_encode(['error' => 'Stock data not found for the product and MRP']); 
        exit;
    }

    // Calculate original stock values
    if ($adjustment['adjusted_type'] === 'subtract') {
        $originalCurrentStock = $originalStockData['current_stock'] + $adjustment['adjusted_stock'];
    } else { // adjustedType === 'add'
        $originalCurrentStock = $originalStockData['current_stock'] - $adjustment['adjusted_stock'];
    }
    $originalPhysicalStock = $originalCurrentStock + $originalStockData['excess_stock'];

    // Prepare updated data
    $updatedData = [
        'date' => $data['date'] ?? $adjustment['date'],
        'stock_id' => $data['stock_id'] ?? $adjustment['stock_id'], // Allow stock_id update
        'adjusted_stock' => $data['adjusted_stock'] ?? $adjustment['adjusted_stock'],
        'adjusted_type' => $data['adjusted_type'] ?? $adjustment['adjusted_type'],
        'reason' => $data['reason'] ?? $adjustment['reason']
    ];

    // Update stock adjustment record
    $updateQuery = "UPDATE stock_adjustment SET ";
    $updateValues = [];
    foreach ($updatedData as $key => $value) {
        $updateQuery .= "`$key` = :$key, ";
        $updateValues[":" . $key] = $value;
    }
    $updateQuery = rtrim($updateQuery, ', ') . " WHERE unique_id = :uniqueId"; // Use unique_id for WHERE clause

    $stmt = $pdo->prepare($updateQuery);
    $stmt->bindParam(':uniqueId', $uniqueId); // Bind uniqueId for WHERE clause
    foreach ($updateValues as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    // Recalculate and update stock in product_mrp table
    if ($updatedData['adjusted_type'] === 'subtract') {
        $newCurrentStock = $originalCurrentStock - $updatedData['adjusted_stock'];
    } else { // adjustedType === 'add'
        $newCurrentStock = $originalCurrentStock + $updatedData['adjusted_stock'];
    }
    $newPhysicalStock = $newCurrentStock + $originalStockData['excess_stock'];

    $stmt = $pdo->prepare("UPDATE product_mrp SET current_stock = :newCurrentStock, physical_stock = :newPhysicalStock 
                            WHERE product_id = :productId AND mrp = :mrp");
    $stmt->bindParam(':newCurrentStock', $newCurrentStock);
    $stmt->bindParam(':newPhysicalStock', $newPhysicalStock);
    $stmt->bindParam(':productId', $adjustment['product_id']);
    $stmt->bindParam(':mrp', $adjustment['mrp']);
    $stmt->execute();

    // Check and update notification status
    if ($newPhysicalStock <= $originalStockData['minimum_stock']) { 
        $updateNotificationStmt = $pdo->prepare("UPDATE product_mrp SET notification = 'Low stock warning' WHERE product_id = :productId AND mrp = :mrp");
    } else {
        $updateNotificationStmt = $pdo->prepare("UPDATE product_mrp SET notification = NULL WHERE product_id = :productId AND mrp = :mrp");
    }
    $updateNotificationStmt->bindParam(':productId', $adjustment['product_id']);
    $updateNotificationStmt->bindParam(':mrp', $adjustment['mrp']);
    $updateNotificationStmt->execute();

    echo json_encode(['status' => '200', 'message' => 'Stock adjustment updated successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
