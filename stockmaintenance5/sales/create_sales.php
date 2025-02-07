<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['customer_id'], $input['invoice_number'], $input['product_details'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$date = $input['date'] ? $input['date'] : date('Y-m-d');
$customerId = $input['customer_id'];
$invoiceNumber = $input['invoice_number'];
$productDetails = $input['product_details'];
$uniqueId = uniqid();

try {
    // Start the transaction
    $pdo->beginTransaction();

    // Check if the customer exists in the customers table
    $stmt = $pdo->prepare("SELECT customer_name, mobile_number, business_name, gst_number, address FROM customers WHERE customer_id = :customer_id");
    $stmt->execute([':customer_id' => $customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer not found'
        ]);
        exit;
    }

    // Extract customer details for the sales table
    $customerName = $customer['customer_name'];
    $mobileNumber = $customer['mobile_number'];
    $businessName = $customer['business_name'];
    $gstNumber = $customer['gst_number'];
    $address = $customer['address'];

    // Generate sequential order ID
    $stmt = $pdo->prepare("SELECT order_id FROM sales ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    $newNumber = $lastOrder ? str_pad((int)substr($lastOrder['order_id'], 5) + 1, 3, '0', STR_PAD_LEFT) : '001';
    $orderId = "SALE_" . $newNumber;

    // Insert into sales table
    $stmt = $pdo->prepare("INSERT INTO sales (unique_id, date, order_id, customer_id, customer_name, mobile_number, business_name, gst_number, address, invoice_number, created_date) 
                           VALUES (:unique_id, :date, :order_id, :customer_id, :customer_name, :mobile_number, :business_name, :gst_number, :address, :invoice_number, NOW())");
    $stmt->execute([
        ':unique_id' => $uniqueId,
        ':date' => $date,
        ':order_id' => $orderId,
        ':customer_id' => $customerId,
        ':customer_name' => $customerName,
        ':mobile_number' => $mobileNumber,
        ':business_name' => $businessName,
        ':gst_number' => $gstNumber,
        ':address' => $address,
        ':invoice_number' => $invoiceNumber,
    ]);

    $responses = [];

    foreach ($productDetails as $productDetail) {
        if (!isset($productDetail['product_id'], $productDetail['quantity'], $productDetail['mrp'], $productDetail['product'], $productDetail['sales_through'])) {
            $responses[] = [
                'status' => 'error',
                'message' => 'Missing required fields for a product'
            ];
            exit;
        }

        $productId = $productDetail['product_id'];
        $quantity = (int)$productDetail['quantity'];
        $mrp = $productDetail['mrp'];
        $productName = $productDetail['product'];
        $salesThrough = $productDetail['sales_through'];

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
        $stmt = $pdo->prepare("SELECT unique_id, mrp, current_stock, excess_stock, minimum_stock, physical_stock, notification FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $productDetails['product_id'], ':mrp' => $mrp]);
        $mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mrpDetails) {
            echo json_encode([
                'status' => 'error',
                'message' => 'MRP details not found for product'
            ]);
            exit;
        }

        // Handle stock updates
        $minimumStock = $mrpDetails['minimum_stock'] ?? 0;
        $newCurrentStock = $mrpDetails['current_stock'];
        $newExcessStock = $mrpDetails['excess_stock'];

        if ($productName === 'Original' && $salesThrough === 'DMS Stock') {
            $newCurrentStock -= $quantity;
        } elseif ($productName === 'Duplicate' && $salesThrough === 'Excess Stock') {
            $newCurrentStock -= $quantity;
            $newExcessStock += $quantity;
        } elseif ($productName === 'Original' && $salesThrough === 'Excess Stock') {
            $newExcessStock -= $quantity;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid product or sales method'
            ]);
            exit;
        }

        // combine quantity and unit for storage
        $quantityWithUnit = $quantity . ' ' . $unitName;

        // Calculate physical stock
        $newPhysicalStock = $newCurrentStock + $newExcessStock;
        $notification = $newPhysicalStock < $minimumStock ? 'Low stock warning' : '';

        // Update product MRP table
        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            excess_stock = :excess_stock,
            physical_stock = :physical_stock,
            notification = :notification
            WHERE unique_id = :unique_id");
        $stmt->execute([    
            ':current_stock' => $newCurrentStock,
            ':excess_stock' => $newExcessStock,
            ':physical_stock' => $newPhysicalStock,
            ':notification' => $notification,
            ':unique_id' => $mrpDetails['unique_id']
        ]);

        // Insert into sales_mrp table
        $stmt = $pdo->prepare("INSERT INTO sales_mrp (unique_id, order_id, product_id, product_name, sku, quantity, mrp, product, sales_through) 
                               VALUES (:unique_id, :order_id, :product_id, :product_name, :sku, :quantity, :mrp, :product, :sales_through)");
        $stmt->execute([
            ':unique_id' => uniqid(),
            ':order_id' => $orderId,
            ':product_id' => $productDetails['product_id'],
            ':product_name' => $productDetails['product_name'],
            ':sku' => $productDetails['sku'],
            ':quantity' => $quantityWithUnit,
            ':mrp' => $mrp,
            ':product' => $productName,
            ':sales_through' => $salesThrough
        ]);

        // fetch the unique_id of the sales_mrp record
        $stmt = $pdo->prepare("SELECT unique_id FROM sales_mrp WHERE order_id = :order_id AND product_id = :product_id AND quantity = :quantity AND mrp = :mrp");
        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':quantity' => $quantityWithUnit,
            ':mrp' => $mrp
        ]);

        $sales_mrp_unique_id = $stmt->fetchColumn();

        // Insert into stock_history table
        $stmt = $pdo->prepare("INSERT INTO stock_history (unique_id, types, invoice_number, vendor_id, customer_id, product_id, order_id, sku, mrp, quantity) 
                               VALUES (:unique_id, :types, :invoice_number, :vendor_id, :customer_id, :product_id, :order_id, :sku, :mrp, :quantity)");
        $stmt->execute([
            ':unique_id' => $sales_mrp_unique_id,
            ':types' => 'outward',
            ':invoice_number' => $invoiceNumber,
            ':vendor_id' => 'N/A',
            ':customer_id' => $customerId,
            ':product_id' => $productDetails['product_id'],
            ':order_id' => $orderId,
            ':sku' => $productDetails['sku'],
            ':mrp' => $mrp,
            ':quantity' => $quantityWithUnit
        ]);

        // Collect response
        $responses[] = [
            'status' => '200',
            'unique_id' => $sales_mrp_unique_id,
            'product_id' => $productId,
            'product_name' => $productDetails['product_name'],
            'sku' => $productDetails['sku'],
            'quantity' => $quantity,
            'mrp' => $mrp,
            'product' => $productName,
            'sales_through' => $salesThrough,
            'physical_stock' => $newCurrentStock + $newExcessStock
        ];
    }

    // Commit transaction
    $pdo->commit();

    // Return response
    echo json_encode([
        'status' => '200', 
        'message' => 'Sales records created successfully', 
        'data' => $responses
    ]);
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    
    // Log error and return message
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>
