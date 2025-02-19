<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include_once '../dbconfig/config.php';

// Decode the incoming JSON request
$requestPayload = file_get_contents("php://input");
$data = json_decode($requestPayload, true);

// Validate input data
if (!$data || !isset($data['product_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing product_id']);
    exit;
}

$productId = $data['product_id'];

try {
    $pdo->beginTransaction(); // Start transaction

    // Update product details
    $updateProductQuery = "UPDATE product SET ";
    $updateParams = [];

    if (isset($data['product_name'])) {
        $updateProductQuery .= "product_name = :product_name, ";
        $updateParams[':product_name'] = $data['product_name'];
    }
    if (isset($data['unit'])) {
        $updateProductQuery .= "unit = :unit, ";
        $updateParams[':unit'] = $data['unit'];
    }
    if (isset($data['subunit'])) {
        $updateProductQuery .= "subunit = :subunit, ";
        $updateParams[':subunit'] = $data['subunit'];
    }

    // Remove trailing comma
    $updateProductQuery = rtrim($updateProductQuery, ', ') . " WHERE product_id = :product_id";
    $updateParams[':product_id'] = $productId;

    // Execute product update
    $stmt = $pdo->prepare($updateProductQuery);
    $stmt->execute($updateParams);

    // Process MRP details
    if (isset($data['mrp_details']) && is_array($data['mrp_details'])) {
        foreach ($data['mrp_details'] as $mrp) {
            $physicalStock = $mrp['current_stock'] + $mrp['excess_stock'];
            $notification = ($physicalStock < $mrp['minimum_stock']) ? 'Low stock warning' : '';

            if (isset($mrp['unique_id'])) {
                // Update existing MRP
                $updateMrpQuery = "UPDATE product_mrp SET 
                                    mrp = :mrp, 
                                    opening_stock = :opening_stock, 
                                    current_stock = :current_stock, 
                                    minimum_stock = :minimum_stock, 
                                    excess_stock = :excess_stock, 
                                    physical_stock = :physical_stock, 
                                    notification = :notification 
                                   WHERE product_id = :product_id AND unique_id = :unique_id";

                $mrpStmt = $pdo->prepare($updateMrpQuery);
                $mrpStmt->execute([
                    ':product_id' => $productId,
                    ':unique_id' => $mrp['unique_id'],
                    ':mrp' => $mrp['mrp'],
                    ':opening_stock' => $mrp['opening_stock'],
                    ':current_stock' => $mrp['current_stock'],
                    ':minimum_stock' => $mrp['minimum_stock'],
                    ':excess_stock' => $mrp['excess_stock'],
                    ':physical_stock' => $physicalStock,
                    ':notification' => $notification
                ]);
            } else {
                // Insert new MRP entry (unique_id is missing)
                $insertMrpQuery = "INSERT INTO product_mrp 
                                   (product_id, unique_id, mrp, opening_stock, current_stock, minimum_stock, excess_stock, physical_stock, notification) 
                                   VALUES (:product_id, UUID(), :mrp, :opening_stock, :current_stock, :minimum_stock, :excess_stock, :physical_stock, :notification)";

                $mrpStmt = $pdo->prepare($insertMrpQuery);
                $mrpStmt->execute([
                    ':product_id' => $productId,
                    ':mrp' => $mrp['mrp'],
                    ':opening_stock' => $mrp['opening_stock'],
                    ':current_stock' => $mrp['current_stock'],
                    ':minimum_stock' => $mrp['minimum_stock'],
                    ':excess_stock' => $mrp['excess_stock'],
                    ':physical_stock' => $physicalStock,
                    ':notification' => $notification
                ]);
            }
        }
    }

    $pdo->commit(); // Commit transaction
    echo json_encode(['status' => 'success', 'message' => 'Product and related MRP details updated successfully']);
} catch (PDOException $e) {
    $pdo->rollBack(); // Rollback on error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
