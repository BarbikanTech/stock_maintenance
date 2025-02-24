<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

try {
    // Fetch all sales records with associated product details
    $stmt = $pdo->prepare("
        SELECT s.id, s.unique_id AS sale_unique_id, s.date, s.order_id, s.invoice_number, 
        s.customer_id, s.customer_name, s.mobile_number, s.business_name, 
        s.gst_number, s.address, s.lr_no, s.lr_date, s.shipment_date, s.shipment_name, 
        s.transport_name, s.delivery_details, s.created_date, 
        sm.unique_id AS product_unique_id, sm.product_id, sm.product_name, sm.sku, 
        sm.quantity, sm.mrp, sm.product, sm.sales_through
        FROM sales s
        LEFT JOIN sales_mrp sm ON s.order_id = sm.order_id
        WHERE (s.deleted_at IS NULL OR s.deleted_at = 0)
        ORDER BY s.id ASC

    ");
    $stmt->execute();

    // Organizing data into a structured JSON format
    $sales = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orderId = $row['order_id'];

        if (!isset($sales[$orderId])) {
            $sales[$orderId] = [
                "id" => $row["id"],
                "unique_id" => $row["sale_unique_id"],
                "date" => $row["date"],
                "order_id" => $row["order_id"],
                "invoice_number" => $row["invoice_number"],
                "customer_id" => $row["customer_id"],
                "customer_name" => $row["customer_name"],
                "mobile_number" => $row["mobile_number"],
                "business_name" => $row["business_name"],
                "gst_number" => $row["gst_number"],
                "address" => $row["address"],
                "lr_no" => $row["lr_no"],
                "lr_date" => $row["lr_date"],
                "shipment_date" => $row["shipment_date"],
                "shipment_name" => $row["shipment_name"],
                "transport_name" => $row["transport_name"],
                "delivery_details" => $row["delivery_details"],
                "created_date" => $row["created_date"],
                "products" => []
            ];
        }

        // Add product details to the respective order
        $sales[$orderId]['products'][] = [
            "unique_id" => $row["product_unique_id"],  // Now clearly referring to sales_mrp unique_id
            "product_id" => $row["product_id"],
            "product_name" => $row["product_name"],
            "sku" => $row["sku"],
            "quantity" => $row["quantity"],
            "mrp" => $row["mrp"],
            "product" => $row["product"],
            "sales_through" => $row["sales_through"]
        ];
    }

    // Return the response with structured sales data
    echo json_encode([
        "status" => "success",
        "message" => "Sales records fetched successfully",
        "data" => array_values($sales)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
