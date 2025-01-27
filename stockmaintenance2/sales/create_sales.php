<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['customer_id'], $input['product_id'], $input['quantity'], $input['invoice_number'], $input['mrp'], $input['product'], $input['sales_through'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$customerId    = $input['customer_id'];
$productId     = $input['product_id'];
$quantity      = (int)$input['quantity']; // Ensure quantity is an integer
$invoiceNumber = $input['invoice_number'];
$mrp           = $input['mrp'];
$product       = $input['product'];
$salesThrough  = $input['sales_through'];
$date          = isset($input['date']) ? $input['date'] : date('Y-m-d');

// Generate a unique sales ID
$uniqueId = uniqid();

try {
    // Check if the customer exists in the customers table
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id");
    $stmt->execute([':customer_id' => $customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer not found'
        ]);
        exit;
    }

    // Fetch product details
    $stmt = $pdo->prepare("SELECT product_id, product_name, sku, unit FROM product WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productDetails) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
        exit;
    }

    // Extract unit name (remove numeric prefix)
    $unitParts = explode(' ', $productDetails['unit']);
    $unitName = isset($unitParts[1]) ? $unitParts[1] : $productDetails['unit'];

    // Fetch MRP details
    $stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, excess_stock, minimum_stock, physical_stock, notification FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    $stmt->execute([':product_id' => $productDetails['product_id'], ':mrp' => $mrp]);
    $mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mrpDetails) {
        echo json_encode([
            'status' => 'error',
            'message' => 'MRP not found for the product'
        ]);
        exit;
    }

    $minimumStock = isset($mrpDetails['minimum_stock']) ? $mrpDetails['minimum_stock'] : 0;
    $newCurrentStock = $mrpDetails['current_stock'];
    $newExcessStock = $mrpDetails['excess_stock'];

    // Handle stock updates
    if ($product === 'Original' && $salesThrough === 'DMS Stock') {
        $newCurrentStock -= $quantity;
    } elseif ($product === 'Duplicate' && $salesThrough === 'Excess Stock') {
        $newCurrentStock -= $quantity;
        $newExcessStock += $quantity;
    } elseif ($product === 'Original' && $salesThrough === 'Excess Stock') {
        $newExcessStock -= $quantity;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid product or sales method'
        ]);
        exit;
    }

     

    // Combine quantity and unit
    $quantityWithUnit = $quantity . ' ' . $unitName;

    // Generate sequential order ID
    $stmt = $pdo->prepare("SELECT order_id FROM sales ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastOrder) {
        $lastNumber = (int)substr($lastOrder['order_id'], 5);
        $newNumber  = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $newNumber  = '001';
    }
    $orderId = "SALE_" . $newNumber;

    // Insert sales record
    $stmt = $pdo->prepare("INSERT INTO sales (unique_id, date, order_id, invoice_number, customer_id, product_id, product_name, sku, quantity, mrp, product, sales_through) 
                           VALUES (:unique_id, :date, :order_id, :invoice_number, :customer_id, :product_id, :product_name, :sku, :quantity, :mrp, :product, :sales_through)");
    $stmt->execute([
        ':unique_id' => $uniqueId,
        ':date' => $date,
        ':order_id' => $orderId,
        ':invoice_number' => $invoiceNumber,
        ':customer_id' => $customerId,
        ':product_id' => $productDetails['product_id'],
        ':product_name' => $productDetails['product_name'],
        ':sku' => $productDetails['sku'],
        ':quantity' => $quantityWithUnit,
        ':mrp' => $mrp,
        ':product' => $product,
        ':sales_through' => $salesThrough
    ]);

    // Update stock
    $stmt = $pdo->prepare("UPDATE product_mrp SET 
        current_stock = :current_stock, 
        excess_stock = :excess_stock,
        physical_stock = :physical_stock,
        notification = :notification 
        WHERE unique_id = :unique_id");
    $stmt->execute([
        ':current_stock' => $newCurrentStock,
        ':excess_stock' => $newExcessStock,
        ':physical_stock' => $newCurrentStock + $newExcessStock,
        ':notification' => $newCurrentStock + $newExcessStock < $minimumStock ? 'Low stock warning' : '',
        ':unique_id' => $mrpDetails['unique_id']
    ]);
    // Calculate physical stock
    $physicalStock = $newCurrentStock + $newExcessStock;

    // Check if stock is below minimum
    $notification = $physicalStock < $mrpDetails['minimum_stock'] ? 'Low stock warning' : '';  

    // Insert into stock history
    $stmt = $pdo->prepare("INSERT INTO stock_history (unique_id, types, invoice_number, vendor_id, customer_id, product_id, order_id, sku, mrp, quantity) 
                           VALUES (:unique_id, 'outward', :invoice_number, :vendor_id, :customer_id, :product_id, :order_id, :sku, :mrp, :quantity)");
    $stmt->execute([
        
        ':unique_id' => $uniqueId,
        ':invoice_number' => $invoiceNumber,
        ':vendor_id' => 'N/A',
        ':customer_id' => $customerId,
        ':product_id' => $productDetails['product_id'],
        ':order_id' => $orderId,
        ':sku' => $productDetails['sku'],
        ':mrp' => $mrp,
        ':quantity' => $quantityWithUnit
    ]);

    // Return success response
    echo json_encode([
        'status' => '200',
        'message' => 'Sales record created successfully',
        'data' => [
            'unique_id' => $uniqueId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $productDetails['product_name'],
            'sku' => $productDetails['sku'],
            'quantity' => $quantityWithUnit,
            'mrp' => $mrp,
            'product' => $product,
            'sales_through' => $salesThrough,
            'physical_stock' => $physicalStock,
            'notification' => $notification
        ]
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
}
?>
