<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
require_once 'dbconfig/config.php';

try {
    // Fetch all activity logs
    $stmt = $pdo->prepare("SELECT id, unique_id, table_type, types_unique_id, order_id, vendor_customer_id, invoice_number, lr_no, lr_date, shipment_date, shipment_name, transport_name, delivery_details, original_unique_id, staff_id, staff_name, product_id, product_name, sku, quantity, mrp, product, sales_through, created_date, admin_confirmation FROM notifications WHERE deleted_at = 0 ORDER BY id ASC");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format status field
    foreach ($logs as &$log) {
        $log['status'] = $log['admin_confirmation'] == 1 ? 'Approved' : 'Declined';
        unset($log['admin_confirmation']); // Remove raw status field
    }

    echo json_encode([
        'status' => 'success',
        'data' => $logs
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
