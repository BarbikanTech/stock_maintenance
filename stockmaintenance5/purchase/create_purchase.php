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
if (!isset($input['vendor_id'], $input['invoice_number'], $input['product_details']) || !is_array($input['product_details'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing required fields or invalid product details'
    ]);
    exit;
}

// Get common data from input
$vendorId = $input['vendor_id'];
$invoiceNumber = $input['invoice_number'];
$date = isset($input['date']) ? $input['date'] : date('Y-m-d');
$uniqueId = uniqid(); // Unique purchase ID

try {
    // Check if the vendor exists in the vendors table
    $stmt = $pdo->prepare("SELECT vendor_name, mobile_number, business_name, gst_number, address FROM vendors WHERE vendor_id = :vendor_id");
    $stmt->execute([':vendor_id' => $vendorId]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Vendor not found'
        ]);
        exit;
    }

    // Extract vendor details
    $vendorName = $vendor['vendor_name'];
    $mobileNumber = $vendor['mobile_number'];
    $businessName = $vendor['business_name'];
    $gstNumber = $vendor['gst_number'];
    $address = $vendor['address'];

    // Generate sequential order ID in the format ORD_001
    $stmt = $pdo->prepare("SELECT order_id FROM purchase ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    $newNumber = $lastOrder ? str_pad((int)substr($lastOrder['order_id'], 4) + 1, 3, '0', STR_PAD_LEFT) : '001';
    $orderId = "ORD_" . $newNumber;

    // Insert a single record into the purchase table
    $stmt = $pdo->prepare("INSERT INTO purchase (unique_id, date, order_id, vendor_id, vendor_name, mobile_number, business_name, gst_number, address, invoice_number, created_date) 
                           VALUES (:unique_id, :date, :order_id, :vendor_id, :vendor_name, :mobile_number, :business_name, :gst_number, :address, :invoice_number, NOW())");

    $stmt->execute([
        ':unique_id' => $uniqueId,
        ':date' => $date,
        ':order_id' => $orderId,
        ':vendor_id' => $vendorId,
        ':vendor_name' => $vendorName,
        ':mobile_number' => $mobileNumber,
        ':business_name' => $businessName,
        ':gst_number' => $gstNumber,
        ':address' => $address,
        ':invoice_number' => $invoiceNumber
    ]);

    $responses = [];
 
    // Loop through each product in product_details and insert into purchase_mrp table
    foreach ($input['product_details'] as $productDetail) {
        if (!isset($productDetail['product_id'], $productDetail['quantity'], $productDetail['mrp'])) {
            $responses[] = [
                'status' => 'error',
                'message' => 'Missing required fields for a product'
            ];
            continue;
        }

    $productId = $productDetail['product_id'];
    $quantity = (int)$productDetail['quantity'];
    $mrp = $productDetail['mrp'];

    // Fetch product details (name, SKU, unit) from product table
    $stmt = $pdo->prepare("SELECT product_id, product_name, sku, unit FROM product WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $responses[] = [
            'status' => 'error',
            'message' => "Product not found for ID: $productId"
        ];
        continue;
    }

    // Extract unit name (remove numeric prefix)
    $unitParts = explode(' ', $product['unit']);
    $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

    // Fetch MRP details from product_mrp table
    $stmt = $pdo->prepare("SELECT unique_id, current_stock, minimum_stock, excess_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
    $stmt->execute([':product_id' => $product['product_id'], ':mrp' => $mrp]);
    $mrpDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mrpDetails) {
        $responses[] = [
            'status' => 'error',
            'message' => "MRP not found for product ID: $productId"
        ];
        continue;
    }

    $quantityWithUnit = $quantity . ' ' . $unitName;
    $newStock = $mrpDetails['current_stock'] + $quantity;
    $physicalStock = $newStock + $mrpDetails['excess_stock'];

    // Set notification if stock is below minimum
    $notification = $physicalStock < $mrpDetails['minimum_stock'] ? 'Low stock warning' : '';

    // Insert into purchase_mrp table
    $stmt = $pdo->prepare("INSERT INTO purchase_mrp (unique_id, order_id, product_id, product_name, sku, quantity, mrp) 
                           VALUES (:unique_id, :order_id, :product_id, :product_name, :sku, :quantity, :mrp)");

    $stmt->execute([
        ':unique_id' => uniqid(),
        ':order_id' => $orderId,
        ':product_id' => $productId,   
        ':product_name' => $product['product_name'],
        ':sku' => $product['sku'],
        ':quantity' => $quantityWithUnit,
        ':mrp' => $mrp
    ]);
 
    // fetch the unique_id of the purchase_mrp record
    $stmt = $pdo->prepare("SELECT unique_id FROM purchase_mrp WHERE order_id = :order_id AND product_id = :product_id AND quantity = :quantity AND mrp = :mrp");
    $stmt->execute([
        ':order_id' => $orderId,
        ':product_id' => $productId,
        ':quantity' => $quantityWithUnit,
        ':mrp' => $mrp
    ]);
     
    $purchase_mrp_unique_id = $stmt->fetchColumn();

    // Update product_mrp table
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

    // Insert into stock_history table
    $stmt = $pdo->prepare("INSERT INTO stock_history (unique_id, types, invoice_number, vendor_id, customer_id, product_id, order_id, sku, mrp, quantity) 
                           VALUES (:unique_id, 'inward', :invoice_number, :vendor_id, :customer_id, :product_id, :order_id, :sku, :mrp, :quantity)");

    $stmt->execute([
        ':unique_id' => $purchase_mrp_unique_id, // Use the unique_id from purchase_mrp
        ':invoice_number' => $invoiceNumber,
        ':vendor_id' => $vendorId,
        ':customer_id' => 'N/A', // Assuming 'N/A' for this field
        ':product_id' => $productId,
        ':order_id' => $orderId, // Make sure this is fetched earlier or set correctly
        ':sku' => $product['sku'],
        ':mrp' => $mrp,
        ':quantity' => $quantityWithUnit
    ]);

    $responses[] = [
        'status' => 'success',
        'unique_id' => $purchase_mrp_unique_id,
        'product_id' => $productId,
        'product_name' => $product['product_name'],
        'sku' => $product['sku'],
        'quantity' => $quantityWithUnit,
        'mrp' => $mrp,
        'physical_stock' => $physicalStock,
        'notification' => $notification
    ];      
    }

    // Return response for all products
    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase records processed',
        'data' => $responses
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
