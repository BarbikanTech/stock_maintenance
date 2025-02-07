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

// Initialize the notification variable
$notifications = ''; // Add this line

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

    // Extract notification details
    $originalUniqueId = $notifications['original_unique_id'];
    $updateQuantity = (int)$notifications['update_quantity']; // Ensure quantity is numeric

    // Update admin confirmation status
    $stmt = $pdo->prepare("UPDATE notifications SET admin_confirmation = :admin_confirmation WHERE unique_id = :unique_id");
    $stmt->execute([
        ':admin_confirmation' => $adminConfirmation,
        ':unique_id' => $uniqueId
    ]);

    // If admin has confirmed, proceed to update
    if ($adminConfirmation == 1) {
        // Retrieve the table type (purchase or sales) and relevant data
        $tableType = $notifications['table_type'];

        // Handle purchase notification
        if ($tableType == 'purchase') {
            // Fetch purchase details using original_unique_id
            $stmt = $pdo->prepare("SELECT * FROM purchase_mrp WHERE unique_id = :unique_id");
            $stmt->execute([':unique_id' => $originalUniqueId]);
            $purchaseDetail = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseDetail) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Purchase details not found'
                ]);
                exit;
            }

            // Fetch product details using product_id
            $stmt = $pdo->prepare("SELECT unit FROM product WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $purchaseDetail['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product details not found'
                ]);
                exit;
            }

            // Extract unit name (remove numeric prefix)
            $unitParts = explode(' ', $product['unit']);
            $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

            // Combine quantity and unit for storage
            $quantityWithUnit = $updateQuantity . ' ' . $unitName;

            $productId = $purchaseDetail['product_id'];
            $mrp = $purchaseDetail['mrp'];

            // Fetch product stock
            $stmt = $pdo->prepare("SELECT * FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([
                ':product_id' => $productId, 
                ':mrp' => $mrp
            ]);
            $productMRP = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$productMRP) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product MRP details not found'
                ]);
                exit;
            }

            // Define minimum stock before use
            $minimumStock = $productMRP['minimum_stock'];

            // Update product stock
            $purchaseQuantity = (int) $purchaseDetail['quantity']; // Assuming quantity is stored as "<number> <unit>"
            $quantityDifference = $updateQuantity - $purchaseQuantity;
            $newCurrentStock = $productMRP['current_stock'] + $quantityDifference;
            $newExcessStock = $productMRP['excess_stock'];
            $physicalStock = $newCurrentStock + $productMRP['excess_stock'];
            $notification = ($physicalStock < $minimumStock) ? 'Low stock warning for product ID: ' . $productId : '';

            // Corrected SQL Update Query
            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                excess_stock = :excess_stock, 
                physical_stock = :physical_stock, 
                notification = :notification 
                WHERE product_id = :product_id AND mrp = :mrp");

            $stmt->execute([
                ':current_stock' => $newCurrentStock,
                ':excess_stock' => $newExcessStock,
                ':physical_stock' => $physicalStock,
                ':notification' => $notification,
                ':product_id' => $productId,
                ':mrp' => $mrp
            ]);

            // Update purchase_mrp table
            $stmt = $pdo->prepare("UPDATE purchase_mrp SET 
                quantity = :quantity 
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':quantity' => $quantityWithUnit,  // Corrected variable name
                ':unique_id' => $originalUniqueId  // Use original_unique_id from notification table
            ]);

            // Insert into stock history
            $stmt = $pdo->prepare ("UPDATE stock_history SET
                quantity = :quantity
                WHERE unique_id = :unique_id");

            $stmt->execute([
                ':quantity' => $quantityWithUnit,  // Corrected variable name
                ':unique_id' => $originalUniqueId  // Use original_unique_id from notification table
            ]);
        }

        // Handle sales notification
        // Handle sales notification
        else if ($tableType == 'sales') {

            // Fetch existing sales record
            $stmt = $pdo->prepare("SELECT * FROM sales_mrp WHERE unique_id = :unique_id");
            $stmt->execute([':unique_id' => $originalUniqueId]);  // Link using original_unique_id
            $salesDetail = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$salesDetail) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Sales record not found'
                ]);
                exit;
            }

            // Fetch product details using product_id
            $stmt = $pdo->prepare("SELECT unit FROM product WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $salesDetail['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product details not found'
                ]);
                exit;
            }

            // Extract unit name (remove numeric prefix)
            $unitParts = explode(' ', $product['unit']);
            $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];

            // Combine new quantity and unit for storage
            $quantityWithUnit = $updateQuantity . ' ' . $unitName;

            $productId = $salesDetail['product_id'];
            $mrp = $salesDetail['mrp'];

            // Fetch product stock from product_mrp table
            $stmt = $pdo->prepare("SELECT unique_id, current_stock, excess_stock, minimum_stock, physical_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
            $stmt->execute([
                ':product_id' => $productId,
                ':mrp' => $mrp
            ]);
            $productMRP = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$productMRP) {
                echo json_encode([
                    'status' => 'error',  
                    'message' => 'Product MRP details not found'
                ]);
                exit;
            }

            // Retrieve the old quantity from the sales record (assuming format "10 kg")
            $oldQuantityStr = $salesDetail['quantity'];
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

            // STEP 1: Revert the old sale
            if ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'DMS Stock') {
                $productMRP['current_stock'] += $oldQuantity;
            } elseif ($salesDetail['product'] === 'Duplicate' && $salesDetail['sales_through'] === 'Excess Stock') {
                $productMRP['current_stock'] += $oldQuantity;
                $productMRP['excess_stock'] -= $oldQuantity;
            } elseif ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'Excess Stock') {
                $productMRP['excess_stock'] += $oldQuantity;
            }

            $newCurrentStock = $productMRP['current_stock'];
            $newExcessStock = $productMRP['excess_stock'];

            // STEP 2: Apply the new sale
            if ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'DMS Stock') {
                $newCurrentStock -= $updateQuantity;
            } elseif ($salesDetail['product'] === 'Duplicate' && $salesDetail['sales_through'] === 'Excess Stock') {
                $newCurrentStock -= $updateQuantity;
                $newExcessStock += $updateQuantity;
            } elseif ($salesDetail['product'] === 'Original' && $salesDetail['sales_through'] === 'Excess Stock') {
                $newExcessStock -= $updateQuantity;
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid sales record details for product ID: ' . $productId . ' and MRP: ' . $mrp . '. Please check the product and sales_through fields'
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

            // Recalculate physical stock and set notification if needed
            $physicalStock = $newCurrentStock + $newExcessStock;
            $notification = '';

            if ($physicalStock < $productMRP['minimum_stock']) {
                $notification = 'Low stock warning for product ID: ' . $productId;
            } else{
                $notification = '';
            }



            // Update product_mrp table
            $stmt = $pdo->prepare("UPDATE product_mrp SET 
                current_stock = :current_stock, 
                excess_stock = :excess_stock, 
                physical_stock = :physical_stock, 
                notification = :notification 
                WHERE product_id = :product_id AND mrp = :mrp");

            $stmt->execute([
                ':current_stock' => $newCurrentStock,
                ':excess_stock' => $newExcessStock,
                ':physical_stock' => $physicalStock,
                ':notification' => $notification,
                ':product_id' => $productId,
                ':mrp' => $mrp
            ]);

            // Update sales_mrp table with the new quantity (include unit)
            $stmt = $pdo->prepare("UPDATE sales_mrp SET 
                quantity = :quantity 
                WHERE unique_id = :unique_id");

            $stmt->execute([ 
                ':quantity' => $quantityWithUnit,
                ':unique_id' => $originalUniqueId
            ]);

            // Update stock_history table if needed
            $stmt = $pdo->prepare("UPDATE stock_history SET 
                quantity = :quantity
                WHERE unique_id = :unique_id");

            $stmt->execute([
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
    // Rollback the transaction in case of error
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
