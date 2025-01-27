<?php
include 'config.php'; // Include your database configuration

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $data = json_decode(file_get_contents("php://input"), true);

        $vendorName = $data['VendorName'];
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
        $productMRP = str_replace('₹', '', $product['MRP']); // Remove ₹ symbol if present
        $sku = $product['SKU'];

        // Calculate the total MRP for this purchase
        $totalMRP = $quantity * $productMRP;
        $productDisplayName = $productName . " - ₹" . $productMRP;

        // Generate OrderID (auto-increment simulation)
        $orderIDQuery = $conn->query("SELECT MAX(ID) as maxID FROM Purchase");
        $maxID = $orderIDQuery->fetch(PDO::FETCH_ASSOC)['maxID'] ?? 0;
        $orderID = str_pad($maxID + 1, 4, '0', STR_PAD_LEFT); // Pad with leading zeros (e.g., '0036')

        // Insert into Purchase table
        $purchaseQuery = $conn->prepare("
            INSERT INTO Purchase (OrderID, VendorName, ProductID, ProductName, SKU, Quantity, MRP)
            VALUES (:orderID, :vendorName, :productID, :productName, :sku, :quantity, :totalMRP)
        ");
        $purchaseQuery->bindParam(':orderID', $orderID);
        $purchaseQuery->bindParam(':vendorName', $vendorName);
        $purchaseQuery->bindParam(':productID', $productID);
        $purchaseQuery->bindParam(':productName', $productDisplayName);
        $purchaseQuery->bindParam(':sku', $sku);
        $purchaseQuery->bindParam(':quantity', $quantity);
        $purchaseQuery->bindParam(':totalMRP', $totalMRP); // Store the total amount
        $purchaseQuery->execute();

        // Update total amount for the vendor
        $totalAmountQuery = $conn->prepare("
            SELECT SUM(MRP) AS TotalAmount
            FROM Purchase
            WHERE VendorName = :vendorName
        ");
        $totalAmountQuery->bindParam(':vendorName', $vendorName);
        $totalAmountQuery->execute();
        $totalAmount = $totalAmountQuery->fetch(PDO::FETCH_ASSOC)['TotalAmount'];

        // Update TotalAmount field in the latest record
        $updateQuery = $conn->prepare("
            UPDATE Purchase
            SET TotalAmount = :totalAmount 
            WHERE OrderID = :orderID
        ");
        $updateQuery->bindParam(':totalAmount', $totalAmount);
        $updateQuery->bindParam(':orderID', $orderID);
        $updateQuery->execute();

        // Response 
        echo json_encode([
            "message" => "Purchase created successfully.",
            "OrderID" => $orderID,
            "VendorName" => $vendorName,
            "TotalAmount" => "₹" . $totalAmount
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get input data from query parameters
        $orderID = $_GET['OrderID'] ?? null;
        $vendorName = $_GET['VendorName'] ?? null;

        if ($orderID) {
            // Fetch Purchase details by OrderID
            $query = $conn->prepare("
                SELECT * 
                FROM Purchase 
                WHERE OrderID = :orderID AND IsDeleted = 0
            ");
            $query->bindParam(':orderID', $orderID);
        } elseif ($vendorName) {
            // Fetch Purchase details by VendorName
            $query = $conn->prepare("
                SELECT OrderID, VendorName, ProductID, ProductName, SKU, Quantity, MRP, 
                    (Quantity * MRP) AS TotalAmount
                FROM Purchase 
                WHERE VendorName = :vendorName AND IsDeleted = 0
            ");
            $query->bindParam(':vendorName', $vendorName);
        } else {
            // Fetch all records if no specific parameter is provided
            $query = $conn->prepare("
                SELECT * 
                FROM Purchase 
                WHERE IsDeleted = 0
            ");
        }

        $query->execute();
        $purchases = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($purchases) === 0) {
            echo json_encode(["error" => "No records found."]);
            exit;
        }

        // Response with Purchase records
        echo json_encode([
            "data" => $purchases
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update logic
    try {
        // Get input data
        $data = json_decode(file_get_contents("php://input"), true);

        $orderID = $data['OrderID'];
        $quantity = $data['Quantity'];
        $productID = $data['ProductID'];

        if (!$orderID || !$quantity || !$productID) {
            echo json_encode(["error" => "OrderID, Quantity, and ProductID are required for update."]);
            exit;
        }

        // Validate if the OrderID exists
        $orderQuery = $conn->prepare("SELECT ProductID FROM Purchase WHERE OrderID = :orderID AND IsDeleted = 0");
        $orderQuery->bindParam(':orderID', $orderID);
        $orderQuery->execute();

        if ($orderQuery->rowCount() === 0) {
            echo json_encode(["error" => "Invalid OrderID"]);
            exit;
        }

        // Fetch new Product details
        $productQuery = $conn->prepare("SELECT ProductName, MRP, SKU FROM Product WHERE ID = :productID");
        $productQuery->bindParam(':productID', $productID);
        $productQuery->execute();

        if ($productQuery->rowCount() === 0) {
            echo json_encode(["error" => "Invalid ProductID"]);
            exit;
        }

        $product = $productQuery->fetch(PDO::FETCH_ASSOC);
        $productName = $product['ProductName'];
        $productMRP = str_replace('₹', '', $product['MRP']); // Remove ₹ symbol if present
        $sku = $product['SKU'];
        $productDisplayName = $productName . " - ₹" . $productMRP; // Format as "ProductName - ₹MRP"
        $totalMRP = $quantity * $productMRP;

        // Update the Purchase record
        $updateQuery = $conn->prepare("
            UPDATE Purchase 
            SET Quantity = :quantity, MRP = :totalMRP, ProductID = :productID, 
                ProductName = :productDisplayName, SKU = :sku
            WHERE OrderID = :orderID AND IsDeleted = 0
        ");
        $updateQuery->bindParam(':quantity', $quantity);
        $updateQuery->bindParam(':totalMRP', $totalMRP);
        $updateQuery->bindParam(':productID', $productID);
        $updateQuery->bindParam(':productDisplayName', $productDisplayName);
        $updateQuery->bindParam(':sku', $sku);
        $updateQuery->bindParam(':orderID', $orderID);
        $updateQuery->execute();

        // Recalculate total amount for the vendor
        $vendorNameQuery = $conn->prepare("SELECT VendorName FROM Purchase WHERE OrderID = :orderID");
        $vendorNameQuery->bindParam(':orderID', $orderID);
        $vendorNameQuery->execute();
        $vendorName = $vendorNameQuery->fetch(PDO::FETCH_ASSOC)['VendorName'];

        $totalAmountQuery = $conn->prepare("
            SELECT SUM(MRP) AS TotalAmount
            FROM Purchase
            WHERE VendorName = :vendorName AND IsDeleted = 0
        ");
        $totalAmountQuery->bindParam(':vendorName', $vendorName);
        $totalAmountQuery->execute();
        $totalAmount = $totalAmountQuery->fetch(PDO::FETCH_ASSOC)['TotalAmount'];

        // Update the total amount for the vendor
        $updateTotalAmountQuery = $conn->prepare("
            UPDATE Purchase
            SET TotalAmount = :totalAmount
            WHERE VendorName = :vendorName AND OrderID = :orderID
        ");
        $updateTotalAmountQuery->bindParam(':totalAmount', $totalAmount);
        $updateTotalAmountQuery->bindParam(':vendorName', $vendorName);
        $updateTotalAmountQuery->bindParam(':orderID', $orderID);
        $updateTotalAmountQuery->execute();

        echo json_encode([
            "message" => "Purchase updated successfully.",
            "OrderID" => $orderID,
            "UpdatedProductName" => $productDisplayName,
            "TotalMRP" => $totalMRP
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Get input JSON
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['OrderID'])) {
            echo json_encode(["error" => "OrderID is required."]);
            exit;
        }

        $orderID = $data['OrderID'];

        // Check if the purchase record exists in the Purchase table
        $purchaseQuery = $conn->prepare("SELECT * FROM Purchase WHERE OrderID = :orderID");
        $purchaseQuery->bindParam(':orderID', $orderID);
        $purchaseQuery->execute();

        if ($purchaseQuery->rowCount() === 0) {
            echo json_encode(["error" => "Purchase record not found."]);
            exit;
        }

        // Mark the purchase record as deleted by setting IsDeleted = 1 (soft delete)
        $softDeleteQuery = $conn->prepare("UPDATE Purchase SET IsDeleted = 1 WHERE OrderID = :orderID");
        $softDeleteQuery->bindParam(':orderID', $orderID);
        $softDeleteQuery->execute();

        // Response
        echo json_encode([
            "message" => "Purchase record marked as deleted successfully.",
            "OrderID" => $orderID
        ]);

    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
 else {
    echo json_encode(["error" => "Invalid request method"]);
}
