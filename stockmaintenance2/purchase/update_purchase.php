<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['purchase_id'], $input['vendor_id'], $input['product_id'], $input['quantity'], $input['mrp'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$purchaseId = $input['purchase_id'];
$vendorId = $input['vendor_id'];
$productId = $input['product_id'];
$quantity = (int)$input['quantity']; // Ensure quantity is an integer
$mrp = $input['mrp'];
$date = isset($input['date']) ? $input['date'] : date('Y-m-d');
$userId = isset($input['user_id']) ? $input['user_id'] : 'system'; // Optional user ID for audit trail

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Fetch existing purchase record to verify it exists
    $stmt = $pdo->prepare("SELECT * FROM purchase WHERE unique_id = :purchase_id");
    $stmt->execute([':purchase_id' => $purchaseId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('Purchase record not found');
    }

    // Check if the vendor exists
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE vendor_id = :vendor_id");
    $stmt->execute([':vendor_id' => $vendorId]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        throw new Exception('Vendor not found');
    }

    // Fetch product details
    $stmt = $pdo->prepare("SELECT product_id, product_name, sku, unit FROM product WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Extract unit name (remove numeric prefix)
    $unitParts = explode(' ', $product['unit']);
    $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit']; // Extract 'Barrel' from '1 Barrel'

    // Fetch MRP details for the product
    $stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, physical_stock, excess_stock, minimum_stock, notification FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    $stmt->execute([':product_id' => $product['product_id'], ':mrp' => $mrp]);
    $mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mrpDetails) {
        throw new Exception('MRP not found for the product');
    }

    // Check stock sufficiency for the update
    $oldQuantity = (int)explode(' ', $purchase['quantity'])[0]; // Extract numeric part of the old quantity
    $quantityDifference = $quantity - $oldQuantity; // Positive for increase, negative for decrease

    if ($quantityDifference < 0 && abs($quantityDifference) > $mrpDetails['current_stock']) {
        throw new Exception('Insufficient stock to decrease the quantity');
    }

    // Combine quantity and unit for storage
    $quantityWithUnit = $quantity . ' ' . $unitName;

    // Update the purchase record
    $stmt = $pdo->prepare("UPDATE purchase SET vendor_id = :vendor_id, 
                           product_id = :product_id, product_name = :product_name, sku = :sku, quantity = :quantity, mrp = :mrp 
                           WHERE unique_id = :purchase_id");
    $stmt->execute([
        ':vendor_id' => $vendorId,
        ':product_id' => $product['product_id'],
        ':product_name' => $product['product_name'],
        ':sku' => $product['sku'],
        ':quantity' => $quantityWithUnit,
        ':mrp' => $mrp,
        ':purchase_id' => $purchaseId
    ]);

    // Update stock in the `product_mrp` table
    $newStock = $mrpDetails['current_stock'] + $quantityDifference;  // Update stock based on quantity change
    $physicalStock = $newStock + $mrpDetails['excess_stock'];  // Physical stock includes excess stock
    $notification = ''; // Check stock level

    if ($newStock < $mrpDetails['minimum_stock']) {
        $notification = 'Low stock warning';
    }else{
        $notification = '';
    }

    $stmt = $pdo->prepare("UPDATE product_mrp SET 
        current_stock = :current_stock, 
        physical_stock = :physical_stock,
        notification = :notification 
        WHERE unique_id = :unique_id");
    $stmt->execute([
        ':current_stock' => $newStock,
        ':physical_stock' => $physicalStock,
        ':notification' => $notification,
        ':unique_id' => $mrpDetails['unique_id']
    ]);

    // Insert record into stock history for audit trail
    $stmt = $pdo->prepare("UPDATE stock_history SET 
        types = :types,
        invoice_number = :invoice_number,   
        vendor_id = :vendor_id, 
        customer_id = :customer_id, 
        product_id = :product_id, 
        order_id = :order_id, 
        sku = :sku, 
        mrp = :mrp, 
        quantity = :quantity 
        WHERE unique_id = :purchase_id");

    $stmt->execute([
        ':types' => 'inward',
        ':invoice_number' => $purchase['invoice_number'],
        ':vendor_id' => $vendorId,
        ':customer_id' => 'N/A',
        ':product_id' => $productId,
        ':order_id' => $purchase['order_id'],
        ':sku' => $product['sku'],
        ':mrp' => $mrp,
        ':quantity' => $quantityWithUnit,
        ':purchase_id' => $purchaseId
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'status' => '200',
        'message' => 'Purchase record updated successfully',
        'data' => [
            'unique_id' => $purchaseId,
            'vendor_id' => $vendorId,
            'product_id' => $productId,
            'product_name' => $product['product_name'],
            'sku' => $product['sku'],
            'quantity' => $quantityWithUnit,
            'mrp' => $mrp,
            'current_stock' => $newStock,
            'physical_stock' => $physicalStock,
            'notification' => $notification
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
