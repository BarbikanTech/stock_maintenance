<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['sales_id'], $input['invoice_number'], $input['sales_details'], $input['user_unique_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get data from input
$salesId = $input['sales_id'];
$invoiceNumber = $input['invoice_number'];
$salesDetails = $input['sales_details'];
$date = $input['date'] ?? date('Y-m-d');
$userUniqueId = $input['user_unique_id']; // Get the logged-in user's unique ID

try {
    // Fetch the role of the user (admin or staff)
    $stmt = $pdo->prepare("SELECT role, name_id, name FROM users WHERE unique_id = :unique_id");
    $stmt->execute([':unique_id' => $userUniqueId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }

    $userRole = $user['role'];

    // Start the transaction
    $pdo->beginTransaction();

    // Check if the sale exists
    $stmt = $pdo->prepare("SELECT order_id, customer_id FROM sales WHERE unique_id = :sales_id");
    $stmt->execute([':sales_id' => $salesId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sale not found'
        ]);
        exit;
    }

    $orderId = $sale['order_id'];
    $customerId = $sale['customer_id'];

    $responses = [];

    foreach ($salesDetails as $salesDetail) {
        if (!isset($salesDetail['unique_id'], $salesDetail['product_id'], $salesDetail['quantity'], $salesDetail['mrp'], $salesDetail['product'], $salesDetail['sales_through'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields for a product'
            ]);
            exit;
        }

        $uniqueId = $salesDetail['unique_id'];
        $productId = $salesDetail['product_id'];
        $quantity = (int)$salesDetail['quantity'];
        $mrp = $salesDetail['mrp'];
        $productName = $salesDetail['product'];
        $salesThrough = $salesDetail['sales_through'];

        // Fetch product details
        $stmt = $pdo->prepare("SELECT product_name, sku, unit FROM product WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product not found for the given MRP'
            ]);
            exit;
        }

        // Extract unit name (remove numeric prefix)
        $unitParts = explode(' ', $product['unit']);
        $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

        // Combine quantity and unit for storage
        $quantityWithUnit = $quantity . ' ' . $unitName;

        // Fetch stock details
        $stmt = $pdo->prepare("SELECT unique_id, current_stock, excess_stock, minimum_stock, physical_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
        $productMRP = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productMRP) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product MRP details not found'
            ]);
            exit;
        }

        

        // If the user is a staff member, just insert a notification and exit
        // If the user is a staff member, just insert notifications for all sales_details and exit
        if ($userRole === 'staff') {
        foreach ($salesDetails as $salesDetail) {
            $stmt = $pdo->prepare("INSERT INTO notifications (unique_id, table_type, original_unique_id, staff_id, staff_name, update_quantity, admin_confirmation)
                               VALUES (:unique_id, :table_type, :original_unique_id, :staff_id, :staff_name, :update_quantity, :admin_confirmation)");

            $stmt->execute([
                ':unique_id' => uniqid(),
                ':table_type' => 'sales',
                ':original_unique_id' => $salesDetail['unique_id'],
                ':staff_id' => $user['name_id'], // Logged-in staff ID
                ':staff_name' => $user['name'], // Staff name
                ':update_quantity' => $salesDetail['quantity'],
                ':admin_confirmation' => 0 // Pending admin approval
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Stock update requests sent to admin for approval'
        ]);
        exit;
        }

        // If the user is an admin, process the stock update

        // fetch the existing sales record
        $stmt = $pdo->prepare("SELECT * FROM sales_mrp WHERE unique_id = :unique_id");
        $stmt->execute([':unique_id' => $uniqueId]);
        $salesDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$salesDetail) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Sales record not found'
            ]);
            exit;
        }


        // Revert the previous stock changes
        $oldQuantityStr = $salesDetail['quantity']; // Assuming this is the quantity previously sold

        // Ensure the quantity is in the expected format
        if (preg_match('/^\d+\s+\w+$/', $oldQuantityStr)) { // Check if the format matches "<number> <unit>"
            $oldQuantityParts = explode(' ', $oldQuantityStr);
            $oldQuantity = (int)$oldQuantityParts[0]; // Extract old quantity as integer
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid quantity format in the existing sales record'
            ]);
            exit;
        }

        if ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'DMS Stock') {
            $productMRP['current_stock'] += $oldQuantity;
        } elseif ($salesDetail['product'] === 'Duplicate' && $salesDetail['sales_through'] === 'Excess Stock') {
            $productMRP['current_stock'] += $oldQuantity;
            $productMRP['excess_stock'] -= $oldQuantity;
        } elseif ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'Excess Stock') {
            $productMRP['excess_stock'] += $oldQuantity;
        }

        // Apply new stock changes
        $newCurrentStock = $productMRP['current_stock'];
        $newExcessStock = $productMRP['excess_stock'];

        if ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'DMS Stock') {
            $newCurrentStock -= $quantity;
        } elseif ($salesDetail['product'] === 'Duplicate' && $salesDetail['sales_through'] === 'Excess Stock') {
            $newCurrentStock -= $quantity;
            $newExcessStock += $quantity;
        } elseif ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'Excess Stock') {
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

        $physicalStock = $newCurrentStock + $newExcessStock;
        $notification = ''; // Assuming you will update this elsewhere

        //check if stock is below minimum and set notification
        if ($physicalStock < $productMRP['minimum_stock']) {
            $notification = 'Low stock warning';
        }else{
            $notification = '';
        }

        // Update product_mrp table
        $stmt = $pdo->prepare("UPDATE product_mrp SET 
            current_stock = :current_stock, 
            excess_stock = :excess_stock,
            physical_stock = :physical_stock
            WHERE product_id = :product_id AND mrp = :mrp");

        $stmt->execute([
            ':current_stock' => $newCurrentStock,
            ':excess_stock' => $newExcessStock,
            ':physical_stock' => $physicalStock, // Assuming you will update this elsewhere
            ':product_id' => $productId,
            ':mrp' => $mrp
        ]);

       

        // Update sales table
        $stmt = $pdo->prepare("UPDATE sales SET 
            invoice_number = :invoice_number, 
            date = :date 
            WHERE unique_id = :sales_id");

        $stmt->execute([
            ':invoice_number' => $invoiceNumber,
            ':date' => $date,
            ':sales_id' => $salesId
        ]);

        // Update sales_mrp table
        $stmt = $pdo->prepare("UPDATE sales_mrp SET 
            quantity = :quantity 
            WHERE unique_id = :unique_id");

        $stmt->execute([
            ':quantity' => $quantityWithUnit,
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
            ':order_id' => $orderId,
            ':sku' => $product['sku'],
            ':mrp' => $mrp,
            ':quantity' => $quantityWithUnit,
            ':unique_id' => $uniqueId
        ]);

        // Collect response for this product update
        $responses[] = [
            'status' => 'success',
            'product_id' => $productId,
            'quantity' => $quantityWithUnit,
            'physical_stock' => $productMRP['physical_stock'], // Assuming this is your final stock
        ];
    }


    $pdo->commit();
    echo json_encode([
        'status' => '200',
        'message' => 'Sales records updated successfully',
        'data' => $responses
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>
