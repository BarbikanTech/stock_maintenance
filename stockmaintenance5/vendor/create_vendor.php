<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (!isset($input['vendor_name'], $input['mobile_number'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vendor name and mobile number are required.'
    ]);
    exit;
}

$vendorName = $input['vendor_name'];
$mobileNumber = $input['mobile_number'];
$businessName = $input['business_name'] ?? null;
$gstNumber = $input['gst_number'] ?? null;
$address = $input['address'] ?? null;

try {
    // Generate unique_id
    $uniqueId = uniqid();

    // Generate vendor_id
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(vendor_id, 5) AS UNSIGNED)) AS max_id FROM vendors");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxId = $row['max_id'] ?? 0;
    $newVendorId = 'VEN_' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);

    // Insert the new vendor
    $stmt = $pdo->prepare("
        INSERT INTO vendors (unique_id, vendor_id, vendor_name, mobile_number, business_name, gst_number, address)
        VALUES (:unique_id, :vendor_id, :vendor_name, :mobile_number, :business_name, :gst_number, :address)
    ");
    $stmt->execute([
        'unique_id' => $uniqueId,
        'vendor_id' => $newVendorId,
        'vendor_name' => $vendorName,
        'mobile_number' => $mobileNumber,
        'business_name' => $businessName,
        'gst_number' => $gstNumber,
        'address' => $address
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Vendor created successfully.',
        'data' => [
            'unique_id' => $uniqueId,
            'vendor_id' => $newVendorId,
            'vendor_name' => $vendorName,
            'mobile_number' => $mobileNumber,
            'business_name' => $businessName,
            'gst_number' => $gstNumber,
            'address' => $address
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
