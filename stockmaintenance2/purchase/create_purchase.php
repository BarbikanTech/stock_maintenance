<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['vendor_id'], $input['product_id'], $input['quantity'], $input['invoice_number'], $input['mrp'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$vendorId = $input['vendor_id'];
$productId = $input['product_id'];
$quantity = (int)$input['quantity']; // Ensure quantity is an integer
$invoiceNumber = $input['invoice_number']; 
$mrp = $input['mrp'];  
$date = isset($input['date']) ? $input['date'] : date('Y-m-d');  

// Generate a unique purchase ID and order ID
$uniqueId = uniqid();  
try{
// Check if the vendor exists in the vendors table
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE vendor_id = :vendor_id");
$stmt->execute([':vendor_id' => $vendorId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vendor not found'
    ]);
    exit;
}

// Fetch product details (name, SKU, unit) from `product` table
$stmt = $pdo->prepare("SELECT product_id, product_name, sku, unit FROM product WHERE product_id = :product_id");
$stmt->execute([':product_id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product not found'
    ]);
    exit;
}

// Extract unit name (remove numeric prefix)
$unitParts = explode(' ', $product['unit']);
$unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit']; // Extract 'Barrel' from '1 Barrel'

// Fetch MRP details from `product_mrp` table based on the provided MRP
$stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, minimum_stock, physical_stock, excess_stock, notification FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
$stmt->execute([':product_id' => $product['product_id'], ':mrp' => $mrp]);
$mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mrpDetails) {
    echo json_encode([
        'status' => 'error',
        'message' => 'MRP not found for the product'
    ]);
    exit;
}

// Check if stock exists for the selected MRP
if ($mrpDetails['current_stock'] === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Stock not found for the selected MRP'
    ]);
    exit;
}

// Combine quantity and unit for storage
$quantityWithUnit = $quantity . ' ' . $unitName;

// Generate sequential order ID in the format ORD_001
$stmt = $pdo->prepare("SELECT order_id FROM purchase ORDER BY id DESC LIMIT 1");
$stmt->execute();
$lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lastOrder) {
    // Extract numeric part of the last order_id and increment it
    $lastNumber = (int)substr($lastOrder['order_id'], 4);
    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT); // Increment and pad with zeros
} else {
    $newNumber = '001'; // Start with 001 if no previous orders exist
}
$orderId = "ORD_" . $newNumber;

// Insert the purchase record
$stmt = $pdo->prepare("INSERT INTO purchase (unique_id, date, order_id, invoice_number, vendor_id, product_id, product_name, sku, quantity, mrp) 
                       VALUES (:unique_id, :date, :order_id, :invoice_number, :vendor_id, :product_id, :product_name, :sku, :quantity, :mrp)");
$stmt->execute([ 
    ':unique_id' => $uniqueId,
    ':date' => $date,
    ':order_id' => $orderId,
    ':invoice_number' => $invoiceNumber,
    ':vendor_id' => $vendorId,
    ':product_id' => $product['product_id'],
    ':product_name' => $product['product_name'],
    ':sku' => $product['sku'],
    ':quantity' => $quantityWithUnit,
    ':mrp' => $mrp
]);

$newStock = $mrpDetails['current_stock'] + $quantity; // Add numeric quantity

// Calculate physical stock and set notification
$physicalStock = $newStock + $mrpDetails['excess_stock']; // After purchase  
$notification = '';  // Default to empty string

// Check if stock is below minimum and set notification
if ($physicalStock < $mrpDetails['minimum_stock']) {
    $notification = 'Low stock warning';
}else { 
    $notification = ''; // Clear notification if stock is sufficient
}

// Update the `product_mrp` table with the new stock and notification value
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
  
// Insert into stock_history table for inward transaction
$stmt = $pdo->prepare("INSERT INTO stock_history (unique_id, types, invoice_number, vendor_id, customer_id, product_id, order_id, sku, mrp, quantity) 
                       VALUES (:unique_id, 'inward', :invoice_number, :vendor_id, :customer_id, :product_id, :order_id, :sku, :mrp, :quantity)");
$stmt->execute([
    ':unique_id' => $uniqueId,
    ':invoice_number' => $invoiceNumber,
    ':vendor_id' => $vendorId,
    ':customer_id' => 'N/A',    // No customer for purchase
    ':product_id' => $productId,
    ':order_id' => $orderId,
    ':sku' => $product['sku'],
    ':mrp' => $mrp,
    ':quantity' => $quantityWithUnit
]);

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Purchase record created successfully',
    'data' => [
        'unique_id' => $uniqueId,
        'order_id' => $orderId,
        'product_id' => $productId,
        'product_name' => $product['product_name'],
        'sku' => $product['sku'],
        'quantity' => $quantityWithUnit,
        'mrp' => $mrp,
        'physical_stock' => $physicalStock,
        'notification' => $notification
    ]
]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
