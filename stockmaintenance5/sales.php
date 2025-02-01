<?php
include 'config.php'; // Database connection

header('Content-Type: application/json');

// POST method for creating sales record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input JSON
        $data = json_decode(file_get_contents("php://input"), true);

        $customerName = $data['CustomerName'];
        $productID = $data['ProductID'];
        $quantity = $data['Quantity'];
 
        // Fetch Product details from Product table
        $productQuery = $conn->prepare("SELECT ProductName, MRP, SKU FROM Product WHERE ID = :productID");
        $productQuery->bindParam(':productID', $productID);
        $productQuery->execute();

        if ($productQuery->rowCount() === 0) {
            echo json_encode(["error" => "Invalid ProductID"]);
            exit;
        }

        $product = $productQuery->fetch(PDO::FETCH_ASSOC);
        $productName = $product['ProductName'];
        $productMRP = str_replace('₹', '', $product['MRP']); // Remove ₹ symbol for calculation
        $sku = $product['SKU'];

        // Calculate Total MRP for current sale
        $totalMRP = $quantity * $productMRP;

        // Concatenate ProductName - MRP
        $productDisplayName = $productName . " - ₹" . $productMRP;

        // Generate unique OrderID
        $orderIDQuery = $conn->query("SELECT MAX(ID) as maxID FROM Sales");
        $maxID = $orderIDQuery->fetch(PDO::FETCH_ASSOC)['maxID'] ?? 0;
        $orderID = str_pad($maxID + 1, 4, '0', STR_PAD_LEFT);

        // Insert data into Sales table
        $salesQuery = $conn->prepare("
            INSERT INTO Sales (OrderID, CustomerName, ProductID, ProductName, SKU, Quantity, MRP)
            VALUES (:orderID, :customerName, :productID, :productName, :sku, :quantity, :totalMRP)
        ");
        $salesQuery->bindParam(':orderID', $orderID);
        $salesQuery->bindParam(':customerName', $customerName);
        $salesQuery->bindParam(':productID', $productID);
        $salesQuery->bindParam(':productName', $productDisplayName);
        $salesQuery->bindParam(':sku', $sku);
        $salesQuery->bindParam(':quantity', $quantity);
        $salesQuery->bindParam(':totalMRP', $totalMRP); // Correctly bind the totalMRP
        $salesQuery->execute();

        // Aggregate MRP for the same CustomerName
        $totalAmountQuery = $conn->prepare("
            SELECT SUM(MRP) as TotalAmount 
            FROM Sales 
            WHERE CustomerName = :customerName
        ");
        $totalAmountQuery->bindParam(':customerName', $customerName);
        $totalAmountQuery->execute();
        $totalAmount = $totalAmountQuery->fetch(PDO::FETCH_ASSOC)['TotalAmount'];

        // Update TotalAmount field in the latest record
        $updateQuery = $conn->prepare("
            UPDATE Sales
            SET TotalAmount = :totalAmount 
            WHERE OrderID = :orderID
        ");
        $updateQuery->bindParam(':totalAmount', $totalAmount);
        $updateQuery->bindParam(':orderID', $orderID);
        $updateQuery->execute();

        // Response
        echo json_encode([
            "message" => "Sales record created successfully.",
            "OrderID" => $orderID,
            "CustomerName" => $customerName,
            "TotalAmount" => "₹" . $totalAmount
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// GET method for retrieving sales records
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if OrderID is provided in the query string
        if (isset($_GET['OrderID'])) {
            $orderID = $_GET['OrderID'];

            // Fetch sales record by OrderID, excluding deleted records
            $salesQuery = $conn->prepare("SELECT * FROM Sales WHERE OrderID = :orderID AND deleted_at = 0");
            $salesQuery->bindParam(':orderID', $orderID);
            $salesQuery->execute();

            // Check if the sales record exists
            if ($salesQuery->rowCount() === 0) {
                echo json_encode(["error" => "Sales record not found."]);
                exit;
            }

            $salesRecord = $salesQuery->fetch(PDO::FETCH_ASSOC);

            // Fetch product details based on ProductID
            $productQuery = $conn->prepare("SELECT ProductName, MRP, SKU FROM Product WHERE ID = :productID");
            $productQuery->bindParam(':productID', $salesRecord['ProductID']);
            $productQuery->execute();
            $product = $productQuery->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(["error" => "Product not found."]);
                exit;
            }

            // Prepare the response with sales and product details
            $response = [
                "OrderID" => $salesRecord['OrderID'],
                "CustomerName" => $salesRecord['CustomerName'],
                "ProductName" => $product['ProductName'],
                "SKU" => $product['SKU'],
                "Quantity" => $salesRecord['Quantity'],
                "MRP" => "₹" . $salesRecord['MRP'],
                "TotalAmount" => "₹" . $salesRecord['MRP'] * $salesRecord['Quantity'],
            ];

            echo json_encode($response);

        } else {
            // If no OrderID is provided, fetch all sales records excluding deleted ones
            $salesQuery = $conn->query("SELECT * FROM Sales WHERE deleted_at = 0");
            $sales = $salesQuery->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sales)) {
                echo json_encode(["error" => "No sales records found."]);
                exit;
            }

            // Prepare the response with all sales records
            $response = [];
            foreach ($sales as $sale) {
                // Fetch product details for each sale
                $productQuery = $conn->prepare("SELECT ProductName, MRP, SKU FROM Product WHERE ID = :productID");
                $productQuery->bindParam(':productID', $sale['ProductID']);
                $productQuery->execute();
                $product = $productQuery->fetch(PDO::FETCH_ASSOC);

                $response[] = [
                    "OrderID" => $sale['OrderID'],
                    "CustomerName" => $sale['CustomerName'],
                    "ProductName" => $product['ProductName'],
                    "SKU" => $product['SKU'],
                    "Quantity" => $sale['Quantity'],
                    "MRP" => "₹" . $sale['MRP'],
                    "TotalAmount" => "₹" . $sale['TotalAmount'],
                ];
            }

            echo json_encode($response);
        }

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
 // PUT method for updating sales record
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Get input JSON
        $data = json_decode(file_get_contents("php://input"), true);

        $orderID = $data['OrderID'];
        $customerName = $data['CustomerName'];
        $productID = $data['ProductID'];
        $quantity = $data['Quantity'];

        // Fetch Product details from Product table
        $productQuery = $conn->prepare("SELECT ProductName, MRP, SKU FROM Product WHERE ID = :productID");
        $productQuery->bindParam(':productID', $productID);
        $productQuery->execute();

        if ($productQuery->rowCount() === 0) {
            echo json_encode(["error" => "Invalid ProductID"]);
            exit;
        }

        $product = $productQuery->fetch(PDO::FETCH_ASSOC);
        $productName = $product['ProductName'];
        $productMRP = str_replace('₹', '', $product['MRP']); // Remove ₹ symbol for calculation
        $sku = $product['SKU'];

        // Calculate Total MRP for the current sale
        $totalMRP = $quantity * $productMRP;

        // Concatenate ProductName - MRP
        $productDisplayName = $productName . " - ₹" . $productMRP;

        // Update data in Sales table
        $updateQuery = $conn->prepare("
            UPDATE Sales 
            SET CustomerName = :customerName, 
                ProductID = :productID, 
                ProductName = :productName, 
                SKU = :sku, 
                Quantity = :quantity, 
                MRP = :totalMRP
            WHERE OrderID = :orderID
        ");
        $updateQuery->bindParam(':orderID', $orderID);
        $updateQuery->bindParam(':customerName', $customerName);
        $updateQuery->bindParam(':productID', $productID);
        $updateQuery->bindParam(':productName', $productDisplayName);
        $updateQuery->bindParam(':sku', $sku);
        $updateQuery->bindParam(':quantity', $quantity);
        $updateQuery->bindParam(':totalMRP', $totalMRP);
        $updateQuery->execute();

        // Aggregate MRP for the same CustomerName after the update
        $totalAmountQuery = $conn->prepare("
            SELECT SUM(MRP) as TotalAmount 
            FROM Sales 
            WHERE CustomerName = :customerName
        ");
        $totalAmountQuery->bindParam(':customerName', $customerName);
        $totalAmountQuery->execute();
        $totalAmount = $totalAmountQuery->fetch(PDO::FETCH_ASSOC)['TotalAmount'];

        // Update TotalAmount field in the sales record
        $updateAmountQuery = $conn->prepare("
            UPDATE Sales
            SET TotalAmount = :totalAmount 
            WHERE OrderID = :orderID
        ");
        $updateAmountQuery->bindParam(':totalAmount', $totalAmount);
        $updateAmountQuery->bindParam(':orderID', $orderID);
        $updateAmountQuery->execute();

        // Response
        echo json_encode([
            "message" => "Sales record updated successfully.",
            "OrderID" => $orderID,
            "CustomerName" => $customerName,
            "TotalAmount" => "₹" . $totalAmount
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} // DELETE method for soft deleting sales record
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Get input JSON
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['OrderID'])) {
            echo json_encode(["error" => "OrderID is required."]);
            exit;
        }

        $orderID = $data['OrderID'];

        // Check if the order exists
        $salesQuery = $conn->prepare("SELECT * FROM Sales WHERE OrderID = :orderID");
        $salesQuery->bindParam(':orderID', $orderID);
        $salesQuery->execute();

        if ($salesQuery->rowCount() === 0) {
            echo json_encode(["error" => "Sales record not found."]);
            exit;
        }

        // Mark the sales record as deleted by setting is_deleted = 1 (or 'true')
        $softDeleteQuery = $conn->prepare("UPDATE Sales SET deleted_at = 1 WHERE OrderID = :orderID");
        $softDeleteQuery->bindParam(':orderID', $orderID);
        $softDeleteQuery->execute();

        // Response
        echo json_encode([
            "message" => "Sales record marked as deleted successfully.",
            "OrderID" => $orderID
        ]);

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
