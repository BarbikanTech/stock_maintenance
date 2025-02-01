<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (!isset($input['customer_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer ID is required.'
    ]);
    exit;
}

$customerId = $input['customer_id'];
$customerName = $input['customer_name'] ?? null;
$mobileNumber = $input['mobile_number'] ?? null;
$businessName = $input['business_name'] ?? null;
$gstNumber = $input['gst_number'] ?? null;
$address = $input['address'] ?? null;

// Validate that at least one field is provided for update
if (empty($customerName) && empty($mobileNumber) && empty($businessName) && empty($gstNumber) && empty($address)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'At least one field is required for update.'
    ]);
    exit;
}

try {
    // Prepare the SQL update query with dynamic fields
    $updateFields = [];
    $params = ['customer_id' => $customerId];

    if ($customerName) {
        $updateFields[] = "customer_name = :customer_name";
        $params['customer_name'] = $customerName;
    }
    if ($mobileNumber) {
        $updateFields[] = "mobile_number = :mobile_number";
        $params['mobile_number'] = $mobileNumber;
    }
    if ($businessName) {
        $updateFields[] = "business_name = :business_name";
        $params['business_name'] = $businessName;
    }
    if ($gstNumber) {
        $updateFields[] = "gst_number = :gst_number";
        $params['gst_number'] = $gstNumber;
    }
    if ($address) {
        $updateFields[] = "address = :address";
        $params['address'] = $address;
    }

    // If no fields to update, return an error
    if (empty($updateFields)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No valid fields to update.'
        ]);
        exit;
    }

    // Join the fields for the update query
    $updateQuery = "UPDATE customers SET " . implode(", ", $updateFields) . " WHERE customer_id = :customer_id";

    // Prepare and execute the update statement
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute($params);

    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Customer updated successfully.',
            'data' => $params
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No changes were made. Customer not found or data is the same.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
