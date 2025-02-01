<?php
// Include the database configuration file
include_once '../dbconfig/config.php'; // Adjust the path as per your directory structure

// Decode the incoming JSON request
$requestPayload = file_get_contents("php://input");
$data = json_decode($requestPayload, true);

header("Content-Type: application/json");

// Validate the input data
if (!$data || !isset($data['product_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing product_id']);
    exit;
}

$productId = $data['product_id'];

try {
    // Begin a transact ion to ensure both tables are updated atomically
    $pdo->beginTransaction();

    // Update product details (if provided in the request)
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

    // Remove the trailing comma and add the WHERE clause
    $updateProductQuery = rtrim($updateProductQuery, ', ') . " WHERE product_id = :product_id";
    $updateParams[':product_id'] = $productId;

    // Execute the product update
    $stmt = $pdo->prepare($updateProductQuery);
    $stmt->execute($updateParams);

    // Update MRP details (if provided in the request)
    if (isset($data['mrp_details']) && is_array($data['mrp_details'])) {
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

        foreach ($data['mrp_details'] as $mrp) {
            // Calculate the physical_stock as current_stock + excess_stock
            $physicalStock = $mrp['current_stock'] + $mrp['excess_stock'];
            
            // Check if the physical_stock is less than the minimum_stock and set notification
            $notification = ($physicalStock < $mrp['minimum_stock']) ? 'Low stock warning' : '';

            // Execute the MRP update for each MRP entry
            $mrpStmt->execute([
                ':product_id' => $productId,
                ':unique_id' => $mrp['unique_id'], // Assuming each MRP has a unique ID
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

    // Commit the transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Product and related MRP details updated successfully']);
} catch (PDOException $e) {
    // Rollback the transaction if an error occurs
    $pdo->rollBack();

    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
