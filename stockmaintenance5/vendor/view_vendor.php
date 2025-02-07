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

// Check if 'vendor_id' is provided in the input, if so, fetch that specific vendor
if (isset($input['vendor_id'])) {
    // Fetch vendor by vendor_id
    $vendorId = $input['vendor_id'];

    try {
        // Prepare the SQL query to fetch vendor data
        $stmt = $pdo->prepare("SELECT * FROM vendors WHERE vendor_id = :vendor_id AND deleted_at = 0");
        $stmt->execute(['vendor_id' => $vendorId]);

        // Fetch the vendor data
        $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the vendor exists
        if ($vendor) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Vendor found.',
                'data' => $vendor
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Vendor not found or deleted.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
} else {
    // If no vendor_id is provided, fetch all active vendors
    try {
        // Prepare the SQL query to fetch all active vendor data
        $stmt = $pdo->prepare("SELECT * FROM vendors WHERE deleted_at = 0");
        $stmt->execute();

        // Fetch all vendor records
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any vendors exist
        if ($vendors) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Vendors found.',
                'data' => $vendors
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No active vendors found.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
}
?>
