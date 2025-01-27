<?php
include '../dbconfig/config.php';  // Ensure the correct path to the config file

// Function to generate SKU number starting from 501
function generateSkuNumber($pdo) {
    // Get the last SKU number from the product table
    $stmt = $pdo->query("SELECT MAX(sku_number) AS max_sku FROM product");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxSku = $row['max_sku'] ? $row['max_sku'] : 500;  // If no SKU is found, start from 500
    return $maxSku + 1;  // Increment the SKU number
}

// Input JSON Data
$data = json_decode(file_get_contents("php://input"), true);

// Extracting input data
$date = isset($data['date']) ? $data['date'] : '0000-00-00';  // Default to 0000-00-00 if missing
$productName = $data['product_name'];
$unit = $data['unit'];
$subunit = $data['subunit'];
$mrpDetails = $data['mrp_details'];

// Generate product_id (PROD_001 format) and SKU number
$productId = generateProductId($pdo);
$skuNumber = generateSkuNumber($pdo);  // Generate SKU number starting from 501

// Generate unique_id for the product
$productUniqueId = uniqid();

// Start a transaction
$pdo->beginTransaction();

try {
    // Insert product data into the product table
    $productQuery = "INSERT INTO product (unique_id, product_id, product_name, unit, subunit, sku_number, date) 
                     VALUES (:unique_id, :product_id, :product_name, :unit, :subunit, :sku_number, :date)";
    $stmt = $pdo->prepare($productQuery);
    $stmt->execute([
        'unique_id' => $productUniqueId,
        'product_id' => $productId,
        'product_name' => $productName,
        'unit' => $unit,
        'subunit' => $subunit,
        'sku_number' => $skuNumber,  // Insert the generated SKU number
        'date' => $date
    ]);

    // Get the inserted product_id (for use in the product_mrp table)
    $productIdInserted = $productId;

    // Insert MRP records for the same product_id in the product_mrp table
    $mrpQuery = "INSERT INTO product_mrp (unique_id, product_id, mrp, opening_stock, current_stock, minimum_stock, excess_stock) 
                 VALUES (:unique_id, :product_id, :mrp, :opening_stock, :current_stock, :minimum_stock, :excess_stock)";
    $stmt = $pdo->prepare($mrpQuery);

    foreach ($mrpDetails as $mrpData) {
        $mrpUniqueId = uniqid();  // Normal unique_id for product_mrp, generated automatically

        $stmt->execute([
            'unique_id' => $mrpUniqueId,
            'product_id' => $productIdInserted,
            'mrp' => $mrpData['mrp'],
            'opening_stock' => $mrpData['opening_stock'],
            'current_stock' => $mrpData['current_stock'],
            'minimum_stock' => $mrpData['minimum_stock'],
            'excess_stock' => $mrpData['excess_stock']
        ]);
    }

    // Commit the transaction
    $pdo->commit();

    // Send the response
    echo json_encode([
        "status" => "success",
        "product_id" => $productIdInserted,
        "mrp_details" => $mrpDetails,
        "sku_number" => $skuNumber  // Return the SKU number in the response
    ]);
} catch (Exception $e) {
    // If an error occurs, roll back the transaction
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$pdo = null;
?>
