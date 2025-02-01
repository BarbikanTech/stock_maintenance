<?php
header('Content-Type: application/json');
require_once 'dbconfig/config.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['unique_id'], $input['admin_confirmation'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

$uniqueId = $input['unique_id'];
$adminConfirmation = (int) $input['admin_confirmation'];

try {
    // Fetch notification details
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE unique_id = :unique_id");
    $stmt->execute([':unique_id' => $uniqueId]);
    $notifications = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notifications) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Notification not found'
        ]);
        exit;
    }

    $originalUniqueId = $notifications['original_unique_id'];
    $updateQuantity = (int) $notifications['update_quantity']; // Ensure quantity is numeric

    // Update admin confirmation status
    $stmt = $pdo->prepare("UPDATE notifications SET admin_confirmation = :admin_confirmation WHERE unique_id = :unique_id");
    $stmt->execute([
        ':admin_confirmation' => $adminConfirmation,
        ':unique_id' => $uniqueId
    ]);

    if ($adminConfirmation === 1) {
        // Fetch purchase details using original_unique_id
        $stmt = $pdo->prepare("SELECT * FROM purchase_mrp WHERE unique_id = :unique_id");
        $stmt->execute([':unique_id' => $originalUniqueId]);
        $purchaseDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$purchaseDetail) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Purchase details not found'
            ]);
            exit;
        }

        // Fetch product details using product_id
        $stmt = $pdo->prepare("SELECT unit FROM product WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $purchaseDetail['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product details not found'
            ]);
            exit;
        }

        // Extract unit name (remove numeric prefix)
        $unitParts = explode(' ', $product['unit']);
        $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

        // Combine quantity and unit for storage
        $quantityWithUnit = $updateQuantity . ' ' . $unitName;

        $productId = $purchaseDetail['product_id'];
        $mrp = $purchaseDetail['mrp'];

        // Fetch product stock
        $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([
            ':product_id' => $productId,
            ':mrp' => $mrp
        ]);
        $productMRP = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productMRP) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product MRP details not found'
            ]);
            exit;
        }

        $minimumStock = $productMRP['minimum_stock'];

        // Update stock calculations
        $purchaseQuantity = (int) $purchaseDetail['quantity']; // Ensure purchase quantity is numeric
        $quantityDifference = $updateQuantity - $purchaseQuantity;
        $newCurrentStock = $productMRP['current_stock'] + $quantityDifference;
        $newExcessStock = $productMRP['excess_stock'];
        $PhysicalStock = $newCurrentStock + $productMRP['excess_stock'];
        $notification = ($PhysicalStock < $minimumStock) ? 'Low stock warning' : '';

        // Update product_mrp table
        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock,
            excess_stock = :excess_stock,
            physical_stock = :physical_stock,
            notification = :notification 
            WHERE product_id = :product_id AND mrp = :mrp");
        
        $stmt->execute([
            ':current_stock' => $newCurrentStock,
            ':excess_stock' => $newExcessStock,
            ':physical_stock' => $PhysicalStock,
            ':notification' => $notification,
            ':product_id' => $productId,
            ':mrp' => $mrp
        ]);

        // Update purchase_mrp table
        $stmt = $pdo->prepare("UPDATE purchase_mrp SET 
            quantity = :quantity 
            WHERE unique_id = :unique_id");
        
        $stmt->execute([
            ':quantity' => $quantityWithUnit,
            ':unique_id' => $originalUniqueId
        ]);

        // Update stock_history table
        $stmt = $pdo->prepare("UPDATE stock_history SET 
            quantity = :quantity
            WHERE unique_id = :unique_id");

        $stmt->execute([
            ':quantity' => $quantityWithUnit,
            ':unique_id' => $originalUniqueId
        ]);
    }

    echo json_encode([
        'status' => '200',
        'message' => 'Notification action updated successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
