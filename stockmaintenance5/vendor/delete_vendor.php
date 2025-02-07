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

// Validate input
if (!isset($input['vendor_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vendor ID is required.'
    ]);
    exit;
}

$vendorId = $input['vendor_id'];

try {
    // Check if the vendor exists first
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE vendor_id = :vendor_id AND deleted_at = 0");
    $stmt->execute(['vendor_id' => $vendorId]);

    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vendor) {
        // Soft delete by setting 'deleted_at' to 1
        $updateStmt = $pdo->prepare("UPDATE vendors SET deleted_at = 1 WHERE vendor_id = :vendor_id");
        $updateStmt->execute(['vendor_id' => $vendorId]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Vendor successfully Soft deleted.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Vendor not found or already deleted.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
