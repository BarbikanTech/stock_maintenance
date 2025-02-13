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
if (!isset($input['purchase_id'], $input['invoice_number'], $input['purchase_details'], $input['user_unique_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Get data from input
$purchaseId = $input['purchase_id'];
$invoiceNumber = $input['invoice_number'];
$purchaseDetails = $input['purchase_details'];
$date = $input['date'] ?? date('Y-m-d');
$userUniqueId = $input['user_unique_id'];

try {
    // Fetch user role
    $stmt = $pdo->prepare("SELECT role, name_id, name FROM users WHERE unique_id = :unique_id");
    $stmt->execute([':unique_id' => $userUniqueId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $userRole = $user['role'];

    // Start transaction
    $pdo->beginTransaction();

    // Check if purchase exists
    $stmt = $pdo->prepare("SELECT order_id, vendor_id FROM purchase WHERE unique_id = :purchase_id");
    $stmt->execute([':purchase_id' => $purchaseId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Purchase not found']);
        exit;
    }

    $orderId = $purchase['order_id'];
    $vendorId = $purchase['vendor_id'];
    $responses = [];

    foreach ($purchaseDetails as $purchaseDetail) {
        if (!isset($purchaseDetail['unique_id'], $purchaseDetail['product_id'], $purchaseDetail['quantity'], $purchaseDetail['mrp'])) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields for a product']);
            exit;
        }

        $uniqueId = $purchaseDetail['unique_id'];
        $productId = $purchaseDetail['product_id'];
        $quantity = (int)$purchaseDetail['quantity'];
        $mrp = $purchaseDetail['mrp'];

        // Fetch product details
        $stmt = $pdo->prepare("SELECT sku, unit FROM product WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }

        $unitParts = explode(' ', $product['unit']);
        $unitName = isset($unitParts[1]) ? $unitParts[1] : $product['unit'];
        $quantityWithUnit = $quantity . ' ' . $unitName;

        // Fetch stock details
        $stmt = $pdo->prepare("SELECT current_stock, excess_stock, minimum_stock FROM product_mrp WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([':product_id' => $productId, ':mrp' => $mrp]);
        $productMRP = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productMRP) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product MRP details not found']);
            exit;
        }

        // Handle staff user request
        if ($userRole === 'staff') {
        foreach ($purchaseDetails as $purchaseDetail) {
            $stmt = $pdo->prepare("INSERT INTO notifications (unique_id, table_type, original_unique_id, staff_id, staff_name, update_quantity, admin_confirmation)
                                   VALUES (:unique_id, 'purchase', :original_unique_id, :staff_id, :staff_name, :update_quantity, 0)");
            $stmt->execute([
                ':unique_id' => uniqid(),
                ':original_unique_id' => $uniqueId,
                ':staff_id' => $user['name_id'],
                ':staff_name' => $user['name'],
                ':update_quantity' => $quantity,
            ]);
            continue;
        }
        echo json_encode(['status' => '200', 'message' => 'Notification sent to admin for approval']);
        exit;
    }

        $minimumStock = $productMRP['minimum_stock'];

        // Update product stock
        $quantityDifference = $quantity - $productMRP['current_stock'];
        $newCurrentStock = $productMRP['current_stock'] + $quantityDifference;
        $newExcessStock = $productMRP['excess_stock'];
        $physicalStock = $newCurrentStock + $newExcessStock;
        $notification = ($physicalStock < $minimumStock) ? 'Low stock warning' : '';

        $stmt = $pdo->prepare("UPDATE product_mrp SET current_stock = :current_stock, excess_stock = :excess_stock, physical_stock = :physical_stock, notification = :notification WHERE product_id = :product_id AND mrp = :mrp");
        $stmt->execute([
            ':current_stock' => $newCurrentStock,
            ':excess_stock' => $newExcessStock,
            ':physical_stock' => $physicalStock,
            ':notification' => $notification,
            ':product_id' => $productId,
            ':mrp' => $mrp
        ]);

        // Update purchase
        $stmt = $pdo->prepare("UPDATE purchase SET invoice_number = :invoice_number, date = :date WHERE unique_id = :purchase_id");
        $stmt->execute([':invoice_number' => $invoiceNumber, ':date' => $date, ':purchase_id' => $purchaseId]);

        // Update purchase_mrp
        $stmt = $pdo->prepare("UPDATE purchase_mrp SET quantity = :quantity WHERE unique_id = :unique_id");
        $stmt->execute([':quantity' => $quantityWithUnit, ':unique_id' => $uniqueId]);

        // Update stock history
        $stmt = $pdo->prepare("UPDATE stock_history SET types = 'inward', invoice_number = :invoice_number, vendor_id = :vendor_id, customer_id = 'N/A', product_id = :product_id, order_id = :order_id, sku = :sku, mrp = :mrp, quantity = :quantity WHERE unique_id = :unique_id");
        $stmt->execute([':invoice_number' => $invoiceNumber, ':vendor_id' => $vendorId, ':product_id' => $productId, ':order_id' => $orderId, ':sku' => $product['sku'], ':mrp' => $mrp, ':quantity' => $quantityWithUnit, ':unique_id' => $uniqueId]);

        $responses[] = ['status' => '200', 'product_id' => $productId, 'quantity' => $quantityWithUnit, 'physical_stock' => $physicalStock];
    }

    $pdo->commit();
    echo json_encode(['status' => '200', 'message' => 'Purchase records updated successfully', 'data' => $responses]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
