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
if (!isset($input['sales_id'], $input['customer_id'], $input['invoice_number'], $input['sales_details'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$salesId = $input['sales_id'];
$customerId = $input['customer_id'];
$invoiceNumber = $input['invoice_number'];
$salesDetails = $input['sales_details'];
$date = !empty($input['date']) ? $input['date'] : date('Y-m-d');
$userUniqueId = $input['user_unique_id']; 

// Optional fields
$lrNo = $input['lr_no'] ?? null;
$lrDate = $input['lr_date'] ?? null;
$shipmentDate = $input['shipment_date'] ?? null;
$shipmentName = $input['shipment_name'] ?? null;
$transportName = $input['transport_name'] ?? null;
$deliveryDetails = $input['delivery_details'] ?? null;

try {
    // Fetch the role of the user (admin or staff)
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

    // Fetch sales details
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE unique_id = :sales_id");
    $stmt->execute([':sales_id' => $salesId]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sales) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sales record not found',
            'sales_id' => $salesId  // Debugging: Check input sales_id
        ]);
        exit;
    }

    // Update customer_id and invoice_number in sales table
    $stmt = $pdo->prepare("UPDATE sales SET customer_id = :customer_id, invoice_number = :invoice_number, customer_name = :customer_name, mobile_number = :mobile_number, business_name = :business_name, gst_number = :gst_number , address = :address, lr_no = :lr_no, lr_date = :lr_date, shipment_date = :shipment_date, shipment_name = :shipment_name, transport_name = :transport_name, delivery_details = :delivery_details WHERE unique_id = :sales_id");
    $stmt->execute([
        ':customer_id' => $customerId,
        ':invoice_number' => $invoiceNumber,
        ':sales_id' => $salesId,
        ':customer_name' => $customerName,
        ':mobile_number' => $mobileNumber,
        ':business_name' => $businessName,
        ':gst_number' => $gstNumber,
        ':address' => $address,
        ':lr_no' => $lrNo,
        ':lr_date' => $lrDate,
        ':shipment_date' => $shipmentDate,
        ':shipment_name' => $shipmentName,
        ':transport_name' => $transportName,
        ':delivery_details' => $deliveryDetails
    ]);

    foreach ($salesDetails as $salesDetail) {
        if (!isset($salesDetail['product_id'], $salesDetail['quantity'], $salesDetail['mrp'], $salesDetail['product'], $salesDetail['sales_through'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields for a product'
            ]);
            exit;
        }

        $productId = $salesDetail['product_id'];
        $quantity = (int) filter_var($salesDetail['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $mrp = $salesDetail['mrp'];
        $productName = $salesDetail['product'];
        $salesThrough = $salesDetail['sales_through'];
        $uniqueId = $salesDetail['unique_id'];

        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product details not found'
            ]);
            exit;
        }

        // Extract unit name
        $unitParts = explode(' ', $product['unit']);
        $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

        // Combine quantity and unit name
        $quantityWithUnit = $quantity . ' ' . $unitName;

         // If the user is a staff member, just insert notifications for all sales_details and exit
        if ($userRole === 'staff') {
        foreach ($salesDetails as $salesDetail) {
            // sales_details['product_id'] -> product['product_id'] & product['sku'] product['sku'] should be fetched from the product table
            $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $salesDetail['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['status' => 'error', 'message' => 'Product details not found']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO notifications (unique_id, table_type, types_unique_id, order_id, vendor_customer_id, invoice_number, lr_no, lr_date, shipment_date, shipment_name, transport_name, delivery_details, original_unique_id, staff_id, staff_name, product_id, product_name, sku, quantity, mrp, product, sales_through, admin_confirmation)
                               VALUES (:unique_id, :table_type, :types_unique_id, :order_id, :vendor_customer_id, :invoice_number, :lr_no, :lr_date, :shipment_date, :shipment_name, :transport_name, :delivery_details, :original_unique_id, :staff_id, :staff_name, :product_id, :product_name, :sku, :quantity, :mrp, :product, :sales_through, :admin_confirmation)");

            $stmt->execute([
                ':unique_id' => uniqid(),
                ':table_type' => 'sales',
                ':types_unique_id' => $salesId,
                ':order_id' => $sales['order_id'],
                ':vendor_customer_id' => $customerId,
                ':invoice_number' => $invoiceNumber,
                ':lr_no' => $lrNo,
                ':lr_date' => $lrDate,
                ':shipment_date' => $shipmentDate,
                ':shipment_name' => $shipmentName,
                ':transport_name' => $transportName,
                ':delivery_details' => $deliveryDetails,
                ':original_unique_id' => $salesDetail['unique_id'],
                ':staff_id' => $user['name_id'], // Logged-in staff ID
                ':staff_name' => $user['name'], // Staff name
                ':product_id' => $salesDetail['product_id'],
                ':product_name' => $product['product_name'],
                ':sku' => $product['sku'],
                ':quantity' => $salesDetail['quantity'],
                ':mrp' => $salesDetail['mrp'],
                ':product' => $salesDetail['product'],
                ':sales_through' => $salesDetail['sales_through'],
                ':admin_confirmation' => 0 // Pending admin approval
            ]);
        }

        echo json_encode([
            'status' => '200',
            'message' => 'Stock update requests sent to admin for approval'
        ]);
        exit;
        }

        // Fetch previous sales_mrp details
        $stmt = $pdo->prepare("SELECT * FROM sales_mrp WHERE unique_id = :unique_id");
        $stmt->execute([':unique_id' => $uniqueId]);
        $previousSalesRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$previousSalesRecord) {
            echo json_encode(['status' => 'error', 'message' => 'Previous sales record not found']);
            exit;
        }

        $oldProductID = $previousSalesRecord['product_id'];
        $oldMrp = $previousSalesRecord['mrp'];
        $oldQuantity = (int) filter_var($previousSalesRecord['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $oldProduct = $previousSalesRecord['product'];
        $oldSalesThrough = $previousSalesRecord['sales_through'];

        // Reverse stock changes for the old MRP & Product entry
        $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $oldProductID, ':mrp' => $oldMrp]);
        $oldProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldProductMrp) {
            echo json_encode(['status' => 'error', 'message' => 'Old MRP product details not found']);
            exit;
        }

        if ($oldProduct === 'Original' && $oldSalesThrough === 'DMS Stock') {
            $oldProductMrp['current_stock'] += $oldQuantity;
        } elseif ($oldProduct === 'Duplicate' && $oldSalesThrough === 'Excess Stock') {
            $oldProductMrp['current_stock'] += $oldQuantity;
            $oldProductMrp['excess_stock'] -= $oldQuantity;
        } elseif ($oldProduct === 'Original' && $oldSalesThrough === 'Excess Stock') {
            $oldProductMrp['excess_stock'] += $oldQuantity;
        }

        $oldProductMrp['physical_stock'] = $oldProductMrp['current_stock'] + $oldProductMrp['excess_stock'];

        $oldnotification = '';
        if ($oldProductMrp['physical_stock'] < $oldProductMrp['minimum_stock']) {
            $oldnotification = 'Low stock warning';
        }
        else {
            $oldnotification = '';
        }

        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            excess_stock = :excess_stock,
            physical_stock = :physical_stock
            WHERE unique_id = :unique_id");

        $stmt->execute([
            ':current_stock' => $oldProductMrp['current_stock'],
            ':excess_stock' => $oldProductMrp['excess_stock'],
            ':physical_stock' => $oldProductMrp['physical_stock'],
            ':unique_id' => $oldProductMrp['unique_id']
        ]);

        // Deduct stock for the new MRP
        $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
        $newProductMrp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newProductMrp) {
            echo json_encode(['status' => 'error', 'message' => 'New MRP product details not found']);
            exit;
        }

        if ($productName === 'Original' && $salesThrough === 'DMS Stock') {
            $newProductMrp['current_stock'] -= $quantity;
        } elseif ($productName === 'Duplicate' && $salesThrough === 'Excess Stock') {
            $newProductMrp['current_stock'] -= $quantity;
            $newProductMrp['excess_stock'] += $quantity;
        } elseif ($productName === 'Original' && $salesThrough === 'Excess Stock') {
            $newProductMrp['excess_stock'] -= $quantity;
        }

        if ($newProductMrp['current_stock'] < 0 || $newProductMrp['excess_stock'] < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient stock for the product']);
            exit;
        }

        $notification = '';
        if ($newProductMrp['physical_stock'] < $newProductMrp['minimum_stock']) {
            $notification = 'Low stock warning';
        } else {
            $notification = '';
        }

        $newProductMrp['physical_stock'] = $newProductMrp['current_stock'] + $newProductMrp['excess_stock'];

        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            excess_stock = :excess_stock,
            physical_stock = :physical_stock,
            notification = :notification
            WHERE unique_id = :unique_id");

        $stmt->execute([
            ':current_stock' => $newProductMrp['current_stock'],
            ':excess_stock' => $newProductMrp['excess_stock'],
            ':physical_stock' => $newProductMrp['physical_stock'],
            ':notification' => $notification,
            ':unique_id' => $newProductMrp['unique_id']
        ]);

        // Update sales_mrp table
        $stmt = $pdo->prepare("UPDATE sales_mrp 
            SET product_id = :product_id, 
                product_name = :product_name,
                sku = :sku,
                mrp = :mrp, 
                quantity = :quantity, 
                product = :product, 
                sales_through = :sales_through 
            WHERE unique_id = :unique_id");

        $stmt->execute([
            ':product_id' => $productId,
            ':product_name' => $product['product_name'],
            ':sku' => $product['sku'],
            ':mrp' => $mrp,
            ':quantity' => $quantityWithUnit,
            ':product' => $productName,
            ':sales_through' => $salesThrough,
            ':unique_id' => $uniqueId
        ]);

        // Insert into stock_history table
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
            ':vendor_id' => 'N/A',
            ':customer_id' => $customerId,
            ':product_id' => $productId,
            ':order_id' => $sales['order_id'],
            ':sku' => $product['sku'],
            ':mrp' => $mrp,
            ':quantity' => $quantityWithUnit,
            ':unique_id' => $uniqueId
        ]);

        $responses[] = [
            'product_id' => $productId,
            'mrp' => $mrp,
            'quantity' => $quantityWithUnit,
            'product' => $productName,
            'sales_through' => $salesThrough,
            'new_current_stock' => $newProductMrp['current_stock'],
            'new_excess_stock' => $newProductMrp['excess_stock'],
            'physical_stock' => $newProductMrp['physical_stock']
        ];
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Sales update successful', 
        'responses' => $responses
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
