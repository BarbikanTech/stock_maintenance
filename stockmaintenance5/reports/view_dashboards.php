<?php  
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');  

// Include database configuration
require_once '../dbconfig/config.php';  

try {
    // Fetch general dashboard metrics
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM product) AS total_products,
            (SELECT COUNT(*) FROM customers) AS total_customers,
            COALESCE(SUM(pm.current_stock), 0) AS total_stock,
            (SELECT COUNT(*) FROM product_mrp WHERE notification = 'Low stock warning') AS less_stock
        FROM 
            product_mrp pm
    ");
    $stmt->execute();
    $dashboardData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch inward and outward report
    $stmt = $pdo->prepare("
        SELECT 
            MONTHNAME(created_date) AS month,
            SUM(CASE WHEN types = 'inward' THEN quantity ELSE 0 END) AS inward_total_quantity,
            SUM(CASE WHEN types = 'outward' THEN quantity ELSE 0 END) AS outward_total_quantity
        FROM stock_history
        GROUP BY MONTH(created_date)
        ORDER BY MONTH(created_date)
    ");
    $stmt->execute();
    $inwardOutwardReport = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total products count
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_products FROM product");
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch product report for Pie Chart
$stmt = $pdo->prepare("
    SELECT 
        p.product_name AS product_name,
        p.sku AS sku,
        CONCAT(ROUND((SUM(pm.current_stock) / (SELECT SUM(current_stock) FROM product_mrp)) * 100, 2), '%') AS total_percentage
    FROM product_mrp pm
    INNER JOIN product p ON pm.product_id = p.product_id
    GROUP BY p.product_id
    ORDER BY total_percentage DESC
");
$stmt->execute();
$productReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Fetch top 10 products
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id AS product_id,
            p.product_name AS product_name,
            p.sku AS sku
        FROM 
            product p
        LIMIT 10
    ");
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top 10 customers
    $stmt = $pdo->prepare("
        SELECT 
            c.customer_id AS customer_id,
            c.customer_name AS customer_name
        FROM 
            customers c
        ORDER BY c.customer_id ASC
        LIMIT 10
    ");
    $stmt->execute();
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the JSON response
    echo json_encode([
    "status" => "success",
    "dashboard" => $dashboardData,
    "inward_outward_report" => $inwardOutwardReport,
    "product_report" => [
        "total_products" => $totalProducts['total_products'],
        "products" => $productReport
    ],
    "top_products" => $topProducts,
    "top_customers" => $topCustomers
]);


} catch (PDOException $e) {
    // Log detailed error message for server debugging
    error_log('Database error: ' . $e->getMessage());

    // Return error response with the error details for debugging
    echo json_encode([
        "status" => "error",
        "message" => "Database error occurred",
        "error_details" => $e->getMessage()
    ]);
}
?>
