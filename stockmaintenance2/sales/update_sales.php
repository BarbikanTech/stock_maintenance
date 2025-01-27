<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['sales_id'], $input['customer_id'], $input['product_id'], $input['quantity'], $input['mrp'], $input['invoice_number'], $input['product'], $input['sales_through'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$salesId       = $input['sales_id'];
$customerId    = $input['customer_id'];
$productId     = $input['product_id'];
$quantity      = (int)$input['quantity']; // Ensure quantity is an integer
$mrp           = $input['mrp'];
$invoiceNumber = $input['invoice_number'];
$product       = $input['product'];
$salesThrough  = $input['sales_through'];
$date          = isset($input['date']) ? $input['date'] : date('Y-m-d');

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Fetch the existing sales record
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE unique_id = :sales_id");
    $stmt->execute([':sales_id' => $salesId]);
    $existingSales = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingSales) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sales record not found'
        ]);
        exit;
    }

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

    // Extract unit name
    $unitParts = explode(' ', $productDetails['unit']);
    $unitName = isset($unitParts[1]) ? $unitParts[1] : $productDetails['unit'];

    // Fetch MRP details
    $stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, excess_stock, physical_stock, minimum_stock, notification FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    $stmt->execute([':product_id' => $productDetails['product_id'], ':mrp' => $mrp]);
    $mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mrpDetails) {
        echo json_encode([
            'status' => 'error',
            'message' => 'MRP not found for the product'
        ]);
        exit;
    }

    // Revert the previous stock changes
    $oldQuantityStr = $existingSales['quantity']; // Get the old quantity as a string

    // Ensure the quantity is in the expected format
    if (preg_match('/^\d+\s+\w+$/', $oldQuantityStr)) { // Check if the format matches "<number> <unit>"
        $oldQuantityParts = explode(' ', $oldQuantityStr);
        $oldQuantity = (int) $oldQuantityParts[0]; // Extract old quantity as integer
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid quantity format in the existing sales record'
        ]);
        exit;
    }

    if ($existingSales['product'] === 'Original' && $existingSales['sales_through'] === 'DMS Stock') {
        $mrpDetails['current_stock'] += $oldQuantity;
    } elseif ($existingSales['product'] === 'Duplicate' && $existingSales['sales_through'] === 'Excess Stock') {
        $mrpDetails['current_stock'] += $oldQuantity;
        $mrpDetails['excess_stock'] -= $oldQuantity;
    } elseif ($existingSales['product'] === 'Original' && $existingSales['sales_through'] === 'Excess Stock') {
        $mrpDetails['excess_stock'] += $oldQuantity;
    }

    // Apply new stock changes
    $newCurrentStock = $mrpDetails['current_stock'];
    $newExcessStock = $mrpDetails['excess_stock'];

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

    // Validate stock levels
    if ($newCurrentStock < 0 || $newExcessStock < 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Insufficient stock'
        ]);
        exit;
    }

    // Combine quantity and unit for storage
    $quantityWithUnit = $quantity . ' ' . $unitName;

    // Update sales record
    $stmt = $pdo->prepare("UPDATE sales SET 
        date = :date, 
        customer_id = :customer_id,
        product_id = :product_id,
        product_name = :product_name,
        sku = :sku,
        invoice_number = :invoice_number,
        quantity = :quantity,
        mrp = :mrp,
        product = :product,
        sales_through = :sales_through
        WHERE unique_id = :sales_id");

    $stmt->execute([
        ':date' => $date, 
        ':customer_id' => $customerId,
        ':product_id' => $productDetails['product_id'],
        ':product_name' => $productDetails['product_name'],
        ':sku' => $productDetails['sku'],
        ':invoice_number' => $invoiceNumber,
        ':quantity' => $quantityWithUnit,
        ':mrp' => $mrp,
        ':product' => $product,
        ':sales_through' => $salesThrough,
        ':sales_id' => $salesId
    ]);
 
    $physicalStock = $newCurrentStock + $newExcessStock; // Calculate physical stock
    $notification = ''; // Default to empty string

    // Check if stock is below minimum and set notification
    if ($physicalStock < $mrpDetails['minimum_stock']) {
        $notification = 'Low stock warning';
    }else{
        $notification = ''; // Clear notification if stock is sufficient
    }

    // Update stock in the `product_mrp` table
    $stmt = $pdo->prepare("UPDATE product_mrp SET 
        current_stock = :current_stock, 
        excess_stock = :excess_stock,
        physical_stock = :physical_stock,
        notification = :notification 
        WHERE unique_id = :unique_id");

    $stmt->execute([
        ':current_stock' => $newCurrentStock,
        ':excess_stock' => $newExcessStock,
        ':physical_stock' => $physicalStock,
        ':notification' => $notification,
        ':unique_id' => $mrpDetails['unique_id']
    ]);

    // Update stock history (OUTWARD) for an existing record
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
        WHERE unique_id = :sales_id");

    $stmt->execute([
        ':types' => 'outward',                // Type of transaction (outward sale)
        ':invoice_number' => $invoiceNumber,  // Invoice number
        ':vendor_id' => 'N/A',                // Vendor ID (if applicable, otherwise 'N/A')
        ':customer_id' => $customerId,        // Customer ID
        ':product_id' => $productDetails['product_id'], // Product ID
        ':order_id' => $existingSales['order_id'],      // Existing order ID
        ':sku' => $productDetails['sku'],    // SKU of the product
        ':mrp' => $mrp,                      // MRP of the product
        ':quantity' => $quantityWithUnit,    // Quantity with unit (e.g., "10 kg")
        ':sales_id' => $salesId             // The unique ID to target the specific record
    ]);

    $pdo->commit();

    // Return success response
    echo json_encode([
        'status' => '200',
        'message' => 'Sales record updated successfully',
        'data' => [
            'unique_id' => $salesId,
            'order_id' => $existingSales['order_id'],
            'product_id' => $productId,
            'product_name' => $productDetails['product_name'],
            'sku' => $productDetails['sku'],
            'quantity' => $quantityWithUnit,
            'mrp' => $mrp,
            'physical_stock' => $physicalStock,
            'notification' => $notification
        ]
    ]);
} catch (PDOException $e) {
   // Rollback transaction on error
    $pdo->rollBack();
    error_log('Error updating sales: ' . $e->getMessage());

    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update sales record',
        'error' => $e->getMessage()
    ]);
}
