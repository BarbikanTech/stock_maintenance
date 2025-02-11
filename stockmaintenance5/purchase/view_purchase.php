<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all purchase records with associated vendor details and product details,
    // excluding records with deleted_at = 1 (soft deleted).
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.unique_id, p.date, p.order_id, p.vendor_id, p.vendor_name, p.mobile_number, p.business_name, p.gst_number, 
            p.address, p.invoice_number, p.created_date, 
            vd.unique_id, vd.product_id, vd.product_name, vd.sku, vd.quantity, vd.mrp
        FROM purchase p
        LEFT JOIN purchase_mrp vd ON p.order_id = vd.order_id
        WHERE (p.deleted_at IS NULL OR p.deleted_at = 0)  -- Only include records not soft deleted
        ORDER BY p.created_date ASC
    ");
    $stmt->execute();

    $purchases = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Group products by order_id
        $orderId = $row['order_id'];
        if (!isset($purchases[$orderId])) {
            $purchases[$orderId] = [
                'id' => $row['id'],
                'unique_id' => $row['unique_id'],
                'date' => $row['date'],
                'order_id' => $row['order_id'],
                'vendor_id' => $row['vendor_id'],
                'vendor_name' => $row['vendor_name'],
                'mobile_number' => $row['mobile_number'],
                'business_name' => $row['business_name'],
                'gst_number' => $row['gst_number'],
                'address' => $row['address'],
                'invoice_number' => $row['invoice_number'],
                'created_date' => $row['created_date'],
                'products' => []
            ];
        }

        $purchases[$orderId]['products'][] = [
            'unique_id' => $row['unique_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'sku' => $row['sku'],
            'quantity' => $row['quantity'],
            'mrp' => $row['mrp']
        ];
    }

    // Return the response with all purchase records and product details
    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase records fetched successfully',
        'data' => array_values($purchases)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
