<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once 'dbconfig/config.php';

// Read and decode JSON input
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
$adminConfirmation = (int)$input['admin_confirmation']; // 1 = updated, 0 = not updated

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if the notification exists
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE unique_id = :unique_id");
    $stmt->execute([':unique_id' => $uniqueId]);
    $notifications = $stmt->fetch(PDO::FETCH_ASSOC);  // Assign fetched data to $notifications

    if (!$notifications) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Notification not found'
        ]);
        exit;
    }

    // Update admin confirmation status
    $stmt = $pdo->prepare("UPDATE notifications SET admin_confirmation = :admin_confirmation WHERE unique_id = :unique_id");
    $stmt->execute([
        ':admin_confirmation' => $adminConfirmation,
        ':unique_id' => $uniqueId
    ]);

    // If admin has confirmed, proceed to update
    if ($adminConfirmation == 1) {
        $tableType = $notifications['table_type']; // Get the table type
        $tableUniqueId = $notifications['types_unique_id']; // Get the table unique ID
        $lrno = $notifications['lr_no'];
        $lrdate = $notifications['lr_date'];
        $shipmentDate = $notifications['shipment_date'];
        $shipmentName = $notifications['shipment_name'];
        $transportName = $notifications['transport_name'];
        $deliveryDetails = $notifications['delivery_details'];
        $originalUniqueId = $notifications['original_unique_id']; // Get the original unique ID
        $productId = $notifications['product_id'];
        $productName = $notifications['product_name'];
        $sku = $notifications['sku'];
        $mrp = $notifications['mrp'];
        $quantity = $notifications['quantity'];
        $invoiceNumber = $notifications['invoice_number'];
        $vendorId = $notifications['vendor_customer_id'];
        $orderId = $notifications['order_id'];
        $product = $notifications['product'];
        $salesThrough = $notifications['sales_through'];

        if ($tableType === 'purchase') {
            //Update purchase table with vendor information
            $stmt = $pdo->prepare("SELECT vendor_name, mobile_number, business_name, gst_number, address FROM vendors WHERE vendor_id = :vendor_id");
            $stmt->execute([':vendor_id' => $vendorId]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$vendor) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Vendor not found'
                ]);
                exit;
            }

            // Extract vendor details for the purchase table
            $vendorName = $vendor['vendor_name'];
            $mobileNumber = $vendor['mobile_number'];
            $businessName = $vendor['business_name'];
            $gstNumber = $vendor['gst_number'];
            $address = $vendor['address'];

            $stmt = $pdo->prepare("UPDATE purchase SET 
            vendor_id = :vendor_id, 
            invoice_number = :invoice_number, 
            vendor_name = :vendor_name, 
            mobile_number = :mobile_number, 
            business_name = :business_name, 
            gst_number = :gst_number, 
            address = :address 
            WHERE unique_id = :unique_id");

            $stmt->execute([
                ':vendor_id' => $vendorId,  
                ':invoice_number' => $invoiceNumber,
                ':vendor_name' => $vendorName,
                ':mobile_number' => $mobileNumber,
                ':business_name' => $businessName,
                ':gst_number' => $gstNumber,
                ':address' => $address,
                ':unique_id' => $tableUniqueId // Check if this value is correctly assigned
            ]); 

            // Fetch product details
            $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['status' => 'error', 'message' => 'Product details not found']);
                exit;
            }

            $unitParts = explode(' ', $product['unit']);
            $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];
            $quantityWithUnit = $quantity . ' ' . $unitName;


            // Fetch purchase details
            $stmt = $pdo->prepare("SELECT * FROM purchase_mrp WHERE unique_id = :unique_id");
            $stmt->execute([':unique_id' => $originalUniqueId]);
            $purchaseDetail = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseDetail) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Purchase details not found for unique_id: ' . $originalUniqueId
                ]);
                exit;
            }

            $oldProductId = $purchaseDetail['product_id'];
            $oldMrp = $purchaseDetail['mrp'];
            $oldQuantity = (int) filter_var($purchaseDetail['quantity'], FILTER_SANITIZE_NUMBER_INT);

            // Fetch old product MRP details
            $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([':product_id' => $oldProductId, ':mrp' => $oldMrp]);
            $oldProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldProductMrp) {
                echo json_encode(['status' => 'error', 'message' => 'Product MRP details not found']);
                exit;
            }

            // Adjust the stock levels
            $oldCurrentStock = $oldProductMrp['current_stock'] - $oldQuantity;
            $oldPhysicalStock = $oldCurrentStock + $oldProductMrp['excess_stock'];

            $oldNotification = '';
            if ($oldCurrentStock < $oldProductMrp['minimum_stock']) {
                $oldNotification = 'Low stock';
            } else {
                $oldNotification = '';
            }

            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                physical_stock = :physical_stock,
                notification = :notification 
                WHERE unique_id = :unique_id");
            $stmt->execute([
                ':current_stock' => $oldCurrentStock,
                ':physical_stock' => $oldPhysicalStock,
                ':notification' => $oldNotification,
                ':unique_id' => $oldProductMrp['unique_id']
            ]);

            // Fetch new product MRP details
            $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
            $productMrp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$productMrp) {
                echo json_encode(['status' => 'error', 'message' => 'Product MRP details not found']);
                exit;
            }

            // Update the stock levels
            $newCurrentStock = $productMrp['current_stock'] + $quantity;
            $newPhysicalStock = $newCurrentStock + $productMrp['excess_stock'];

            $newNotification = '';
            if ($newCurrentStock < $productMrp['minimum_stock']) {
                $newNotification = 'Low stock';
            } else {
                $newNotification = '';
            }

            // Update product_mrp table
            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                physical_stock = :physical_stock,
                notification = :notification 
                WHERE unique_id = :unique_id");
            $stmt->execute([
                ':current_stock' => $newCurrentStock,
                ':physical_stock' => $newPhysicalStock,
                ':notification' => $newNotification,
                ':unique_id' => $productMrp['unique_id']
            ]);

            // Update purchase_mrp table
            $stmt = $pdo->prepare("UPDATE purchase_mrp SET 
                product_id = :product_id,
                product_name = :product_name,
                sku = :sku,
                mrp = :mrp,
                quantity = :quantity 
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':product_id' => $productId,
                ':product_name' => $productName,
                ':sku' => $sku,
                ':mrp' => $mrp,
                ':quantity' => $quantityWithUnit,
                ':unique_id' => $originalUniqueId
            ]);

            // Update stock_history table
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
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':types' => 'inward',
                ':invoice_number' => $invoiceNumber,
                ':vendor_id' => $vendorId,
                ':customer_id' => 'N/A', // Ensure this is correctly set
                ':product_id' => $productId,
                ':order_id' => $orderId,
                ':sku' => $sku,
                ':mrp' => $mrp,
                ':quantity' => $quantityWithUnit,
                ':unique_id' => $originalUniqueId
            ]);
             

        }
        // Handle sales notification
        else if ($tableType == 'sales') {
            // update sales table with customer information
            $stmt = $pdo->prepare("SELECT customer_name, mobile_number, gst_number, address FROM customers WHERE customer_id = :customer_id");
            $stmt->execute([':customer_id' => $vendorId]);
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
            $gstNumber = $customer['gst_number'];
            $address = $customer['address'];

            $stmt = $pdo->prepare("UPDATE sales SET
            customer_id = :customer_id,
            invoice_number = :invoice_number,
            customer_name = :customer_name,
            mobile_number = :mobile_number,
            gst_number = :gst_number,
            address = :address,
            lr_no = :lr_no,
            lr_date = :lr_date,
            shipment_date = :shipment_date,
            shipment_name = :shipment_name,
            transport_name = :transport_name,
            delivery_details = :delivery_details
            WHERE unique_id = :unique_id");

            $stmt->execute([
                ':customer_id' => $vendorId,
                ':invoice_number' => $invoiceNumber,
                ':customer_name' => $customerName,
                ':mobile_number' => $mobileNumber,
                ':gst_number' => $gstNumber,
                ':address' => $address,
                ':lr_no' => $lrno,
                ':lr_date' => $lrdate,
                ':shipment_date' => $shipmentDate,
                ':shipment_name' => $shipmentName,
                ':transport_name' => $transportName,
                ':delivery_details' => $deliveryDetails,
                ':unique_id' => $tableUniqueId
            ]);

            // Fetch product details
            $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            $products = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$products) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product details not found'
                ]);
                exit;
            }

            $unitParts = explode(' ', $products['unit']);
            $unitName = isset($unitParts[1]) ? $unitParts[1] : $products['unit'];
            $quantityWithUnit = $quantity . ' ' . $unitName;

            // Fetch sales details
            $stmt = $pdo->prepare("SELECT * FROM sales_mrp WHERE unique_id = :unique_id");
            $stmt->execute([':unique_id' => $originalUniqueId]);
            $salesDetail = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$salesDetail) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Sales details not found for unique_id: ' . $originalUniqueId
                ]);
                exit;
            }

            $oldProductId = $salesDetail['product_id'];
            $oldMrp = $salesDetail['mrp'];
            $oldQuantity = (int) filter_var($salesDetail['quantity'], FILTER_SANITIZE_NUMBER_INT);
            $oldProduct = $salesDetail['product'];
            $oldSalesThrough = $salesDetail['sales_through'];

            // Fetch old product MRP details
            $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([':product_id' => $oldProductId, ':mrp' => $oldMrp]);
            $oldProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldProductMrp) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product MRP details not found'
                ]);
                exit;
            }

            // Reverse stock changes for the old MRP & Product entry
            if ($oldProduct === 'Original' && $oldSalesThrough === 'DMS Stock') {
                $oldProductMrp['current_stock'] += $oldQuantity;
            } elseif ($oldProduct === 'Duplicate' && $oldSalesThrough === 'Excess Stock') {
                $oldProductMrp['current_stock'] += $oldQuantity;
                $oldProductMrp['excess_stock'] -= $oldQuantity;
            } elseif ($oldProduct === 'Original' && $oldSalesThrough === 'Excess Stock') {
                $oldProductMrp['excess_stock'] += $oldQuantity;
            }

            $oldProductMrp['physical_stock'] = $oldProductMrp['current_stock'] + $oldProductMrp['excess_stock'];

            $oldNotification = '';
            if ($oldProductMrp['current_stock'] < $oldProductMrp['minimum_stock']) {
                $oldNotification = 'Low stock';
            } else {
                $oldNotification = '';
            }

            // Update the old product MRP details
            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                excess_stock = :excess_stock, 
                physical_stock = :physical_stock,
                notification = :notification 
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':current_stock' => $oldProductMrp['current_stock'],
                ':excess_stock' => $oldProductMrp['excess_stock'],
                ':physical_stock' => $oldProductMrp['physical_stock'],
                ':notification' => $oldNotification,
                ':unique_id' => $oldProductMrp['unique_id']
            ]);

            // Deduct stock for the new MRP
            $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
            $productMrp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$productMrp) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product MRP details not found'
                ]);
                exit;
            }

            if ($product === 'Original' && $salesThrough === 'DMS Stock') {
                $productMrp['current_stock'] -= $quantity;
            } elseif ($product === 'Duplicate' && $salesThrough === 'Excess Stock') {
                $productMrp['current_stock'] -= $quantity;
                $productMrp['excess_stock'] += $quantity;
            } elseif ($product === 'Original' && $salesThrough === 'Excess Stock') {
                $productMrp['excess_stock'] -= $quantity;
            }

            $productMrp['physical_stock'] = $productMrp['current_stock'] + $productMrp['excess_stock'];

            $newNotification = '';
            if ($productMrp['current_stock'] < $productMrp['minimum_stock']) {
                $newNotification = 'Low stock';
            } else {
                $newNotification = '';
            }

            // Update the new product MRP details
            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                excess_stock = :excess_stock, 
                physical_stock = :physical_stock,
                notification = :notification 
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':current_stock' => $productMrp['current_stock'],
                ':excess_stock' => $productMrp['excess_stock'],
                ':physical_stock' => $productMrp['physical_stock'],
                ':notification' => $newNotification,
                ':unique_id' => $productMrp['unique_id']
            ]);

            // Update sales_mrp table
            $stmt = $pdo->prepare("UPDATE sales_mrp SET 
                product_id = :product_id,
                product_name = :product_name,
                sku = :sku,
                quantity = :quantity,
                mrp = :mrp,
                product = :product,
                sales_through = :sales_through 
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':product_id' => $productId,
                ':product_name' => $productName,
                ':sku' => $sku,
                ':quantity' => $quantityWithUnit,
                ':mrp' => $mrp,
                ':product' => $product,
                ':sales_through' => $salesThrough,
                ':unique_id' => $originalUniqueId
            ]);

            // Update stock_history table
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
                WHERE unique_id = :unique_id");
            
            $stmt->execute([
                ':types' => 'outward',
                ':invoice_number' => $invoiceNumber,
                ':vendor_id' => 'N/A', // Ensure this is correctly set
                ':customer_id' => $vendorId,
                ':product_id' => $productId,
                ':order_id' => $orderId,
                ':sku' => $sku,
                ':mrp' => $mrp,
                ':quantity' => $quantityWithUnit,
                ':unique_id' => $originalUniqueId
            ]);
        }

        // Commit the transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Notification action completed successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Notification not yet confirmed by admin'
        ]);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("SQL Error: " . $e->getMessage()); // Log error for debugging
}
?>
