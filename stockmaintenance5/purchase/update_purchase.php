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
if (!isset($input['purchase_id'], $input['vendor_id'], $input['invoice_number'], $input['purchase_details'])) {
    echo json_encode(['status' => '400', 'message' => 'Missing required fields']);
    exit;
}

// Get data from input
$purchaseId = $input['purchase_id'];
$vendorId = $input['vendor_id'];
$invoiceNumber = $input['invoice_number'];
$purchaseDetails = $input['purchase_details'];
$date = !empty($input['date']) ? $input['date'] : date('Y-m-d');
$userUniqueId = $input['user_unique_id'];

try {
    // Fetch user role
    $stmt = $pdo->prepare("SELECT role, name_id, name FROM users WHERE unique_id = :unique_id");
    $stmt->execute([':unique_id' => $userUniqueId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => '400',
            'message' => 'User not found'
        ]);
        exit;
    }

    $userRole = $user['role'];

    // Start transaction
    $pdo->beginTransaction();

    // check if the vendor exists in vendors table
    $stmt = $pdo->prepare("SELECT vendor_name, mobile_number, business_name, gst_number, address FROM vendors WHERE vendor_id = :vendor_id");
    $stmt->execute([':vendor_id' => $vendorId]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$vendor) {
        echo json_encode([
            'status' => '400',
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

    // Fetch purchase details
    $stmt = $pdo->prepare("SELECT * FROM purchase WHERE unique_id = :purchase_id");
    $stmt->execute([':purchase_id' => $purchaseId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode([
            'status' => '400',
            'message' => 'Purchase record not found',
            'purchase_id' => $purchaseId  // Debugging: Check input sales_id
        ]);
        exit;
    } 

    // Update vendor_id and invoice_number in purchase table
    if($userRole === 'admin'){
    $stmt = $pdo->prepare("UPDATE purchase SET 
        vendor_id = :vendor_id, 
        invoice_number = :invoice_number, 
        vendor_name = :vendor_name, 
        mobile_number = :mobile_number, 
        business_name = :business_name, 
        gst_number = :gst_number, 
        address = :address 
        WHERE unique_id = :purchase_id");
    $stmt->execute([
        ':vendor_id' => $vendorId,
        ':invoice_number' => $invoiceNumber,
        ':vendor_name' => $vendorName,
        ':mobile_number' => $mobileNumber,
        ':business_name' => $businessName,
        ':gst_number' => $gstNumber,
        ':address' => $address,
        ':purchase_id' => $purchaseId  
    ]);
    }
    foreach ($purchaseDetails as $purchaseDetail) {
        if (!isset($purchaseDetail['product_id'], $purchaseDetail['quantity'], $purchaseDetail['mrp'])) {
            echo json_encode([
                'status' => '400', 
                'message' => 'Missing required fields for a product'
            ]);
            exit;
        }

        $productId = $purchaseDetail['product_id'];
        $quantity = (int) filter_var($purchaseDetail['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $mrp = $purchaseDetail['mrp'];
        $uniqueId = $purchaseDetail['unique_id'];

        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['status' => '400', 'message' => 'Product details not found']);
            exit;
        }

        $unitParts = explode(' ', $product['unit']);
        $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];
        $quantityWithUnit = $quantity . ' ' . $unitName;

        // Handle staff user request
        if ($userRole === 'staff') {
            foreach ($purchaseDetails as $purchaseDetail) {
                // purchase_detail['purchase_id'] -> product['product_id'] & product['sku'] should be fetched the product table
                $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
                $stmt->execute([':product_id' => $purchaseDetail['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    echo json_encode(['status' => '400', 'message' => 'Product details not found']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO notifications (unique_id, table_type, types_unique_id, order_id, vendor_customer_id, invoice_number, original_unique_id, staff_id, staff_name, product_id, product_name, sku, quantity, mrp, product, sales_through, admin_confirmation)
                               VALUES (:unique_id, :table_type, :types_unique_id, :order_id, :vendor_customer_id, :invoice_number, :original_unique_id, :staff_id, :staff_name, :product_id, :product_name, :sku, :quantity, :mrp, :product, :sales_through, :admin_confirmation)");
                $stmt->execute([
                    ':unique_id' => uniqid(),
                    ':table_type' => 'purchase',
                    ':types_unique_id' => $purchaseId,
                    ':order_id' => $purchase['order_id'],
                    ':vendor_customer_id' => $vendorId,
                    ':invoice_number' => $invoiceNumber,
                    ':original_unique_id' => $purchaseDetail['unique_id'],
                    ':staff_id' => $user['name_id'], // Logged-in staff ID
                    ':staff_name' => $user['name'], // Staff name
                    ':product_id' => $purchaseDetail['product_id'],
                    ':product_name' => $product['product_name'],
                    ':sku' => $product['sku'],
                    ':quantity' => $purchaseDetail['quantity'],
                    ':mrp' => $purchaseDetail['mrp'], 
                    ':product' => 'N/A',
                    ':sales_through' => 'N/A',
                    ':admin_confirmation' => 0 // Pending admin approval
                ]);
            }

            echo json_encode([
                'status' => '200', 
                'message' => 'Notification sent to admin for approval'
            ]); 
            exit;
        }

        // Fetch old purchase_mrp record
        $stmt = $pdo->prepare("SELECT * FROM purchase_mrp WHERE unique_id = :unique_id");
        $stmt->execute([':unique_id' => $uniqueId]);
        $previousPurchaseRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$previousPurchaseRecord) {
            $pdo->rollBack();
            echo json_encode(['status' => '400', 'message' => 'Previous purchase record not found']);
            exit;
        }

        $oldProductID = $previousPurchaseRecord['product_id'];
        $oldMrp = $previousPurchaseRecord['mrp'];
        $oldQuantity = (int) filter_var($previousPurchaseRecord['quantity'], FILTER_SANITIZE_NUMBER_INT);

        // Fetch old product MRP details
        $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $oldProductID, ':mrp' => $oldMrp]);
        $oldProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldProductMrp) {
            echo json_encode(['status' => '400', 'message' => 'Old MRP product details not found']);
            exit;
        }

        // Adjust old stock
        $oldCurrentStock = $oldProductMrp['current_stock'] - $oldQuantity;
        $oldPhysicalStock = $oldCurrentStock + $oldProductMrp['excess_stock'];

        $oldnotification = '';
        if ($oldCurrentStock < $oldProductMrp['minimum_stock']) {
            $oldnotification = 'Low stock warning';
        } else {
            $oldnotification = '';
        }

        // Update stock for the old MRP
        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            physical_stock = :physical_stock,
            notification = :notification 
            WHERE unique_id = :unique_id");
        $stmt->execute([
            ':current_stock' => $oldCurrentStock,
            ':physical_stock' => $oldPhysicalStock,
            ':notification' => $oldnotification,
            ':unique_id' => $oldProductMrp['unique_id']
        ]);

        // Fetch new product MRP details
        $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
        $newProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newProductMrp) {
            echo json_encode(['status' => '400', 'message' => 'New MRP product details not found']);
            exit;
        }

        // Update stock for the new MRP
        $newCurrentStock = $newProductMrp['current_stock'] + $quantity;
        $newPhysicalStock = $newCurrentStock + $newProductMrp['excess_stock']; 

        $newNotification = '';
        if ($newCurrentStock < $newProductMrp['minimum_stock']) {
            $newNotification = 'Low stock warning';
        } else {
            $newNotification = '';
        }

        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            physical_stock = :physical_stock,
            notification = :notification  
            WHERE unique_id = :unique_id");
        $stmt->execute([
            ':current_stock' => $newCurrentStock,
            ':physical_stock' => $newPhysicalStock,
            ':notification' => $newNotification,
            ':unique_id' => $newProductMrp['unique_id']
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
            ':product_name' => $product['product_name'],
            ':sku' => $product['sku'],
            ':mrp' => $mrp,
            ':quantity' => $quantityWithUnit,
            ':unique_id' => $uniqueId
        ]);

        // Insert stock_history table
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
            ':customer_id' => 'N/A',
            ':product_id' => $productId, 
            ':order_id' => $purchase['order_id'],
            ':sku' => $product['sku'], 
            ':mrp' => $mrp, 
            ':quantity' => $quantityWithUnit, 
            ':unique_id' => $uniqueId 
        ]); 


        $responses[] = [
            'product_id' => $productId,
            'mrp' => $mrp, 
            'quantity' => $quantityWithUnit, 
            'physical_stock' => $oldPhysicalStock, 
            'new_current_stock' => $newCurrentStock,
            'new_excess_stock' => $newProductMrp['excess_stock'],
            'new_physical_stock' => $newPhysicalStock 
        ];
        
    }

    $pdo->commit();
    echo json_encode([
        'status' => '200', 
        'message' => 'Purchase records updated successfully', 
        'data' => $responses
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => '400',
        'message' => $e->getMessage()
    ]);
}
?>
