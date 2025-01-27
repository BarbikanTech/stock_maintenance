<?php
include 'config.php'; // Include database configuration

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate input
        if (empty($data['CustomerName']) || empty($data['BusinessName']) || empty($data['MobileNo'])) {
            echo json_encode(["error" => "Missing required fields."]);
            exit;
        }

        $customerName = $data['CustomerName'];
        $businessName = $data['BusinessName'];
        $mobileNo = $data['MobileNo'];
        $createDate = date('Y-m-d H:i:s'); // Current timestamp

        // Generate Customer ID (auto-increment simulation)
        $query = $conn->query("SELECT MAX(ID) as maxID FROM Customer");
        $maxID = $query->fetch(PDO::FETCH_ASSOC)['maxID'] ?? 0;
        $customerID = str_pad($maxID + 1, 3, '0', STR_PAD_LEFT);

        // Insert into Customer table
        $insertQuery = $conn->prepare("
            INSERT INTO Customer (CustomerID, CustomerName, BusinessName, MobileNo, create_date)
            VALUES (:customerID, :customerName, :businessName, :mobileNo, :createDate)
        ");
        $insertQuery->bindParam(':customerID', $customerID);
        $insertQuery->bindParam(':customerName', $customerName);
        $insertQuery->bindParam(':businessName', $businessName);
        $insertQuery->bindParam(':mobileNo', $mobileNo);
        $insertQuery->bindParam(':createDate', $createDate);
        $insertQuery->execute();

        // Response
        echo json_encode([
            "message" => "Customer created successfully.",
            "CustomerID" => $customerID,
            "CustomerName" => $customerName,
            "BusinessName" => $businessName,
            "MobileNo" => $mobileNo,
            "CreateDate" => $createDate
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Retrieve all active customers from the database (excluding soft deleted ones)
        $query = $conn->query("SELECT * FROM Customer WHERE is_deleted = 0");
        $customers = $query->fetchAll(PDO::FETCH_ASSOC);

        // Response with all active customers
        echo json_encode([
            "customers" => $customers
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Get input data for update
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate input
        if (empty($data['CustomerID']) || empty($data['CustomerName']) || empty($data['BusinessName']) || empty($data['MobileNo'])) {
            echo json_encode(["error" => "Missing required fields."]);
            exit;
        }

        $customerID = $data['CustomerID'];
        $customerName = $data['CustomerName'];
        $businessName = $data['BusinessName'];
        $mobileNo = $data['MobileNo'];

        // Update customer in the database
        $updateQuery = $conn->prepare("
            UPDATE Customer 
            SET CustomerName = :customerName, BusinessName = :businessName, MobileNo = :mobileNo
            WHERE CustomerID = :customerID AND is_deleted = 0
        ");
        $updateQuery->bindParam(':customerID', $customerID);
        $updateQuery->bindParam(':customerName', $customerName);
        $updateQuery->bindParam(':businessName', $businessName);
        $updateQuery->bindParam(':mobileNo', $mobileNo);
        $updateQuery->execute();

        // Response
        echo json_encode([
            "message" => "Customer updated successfully.",
            "CustomerID" => $customerID,
            "CustomerName" => $customerName,
            "BusinessName" => $businessName,
            "MobileNo" => $mobileNo
        ]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Get the CustomerID from the request
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['CustomerID'])) {
            echo json_encode(["error" => "Missing CustomerID."]);
            exit;
        }

        $customerID = $data['CustomerID'];

        // Soft delete customer by updating the is_deleted column to 1
        $deleteQuery = $conn->prepare("UPDATE Customer SET is_deleted = 1 WHERE CustomerID = :customerID AND is_deleted = 0");
        $deleteQuery->bindParam(':customerID', $customerID);
        $deleteQuery->execute();

        // Check if any rows were affected (i.e., customer soft-deleted)
        if ($deleteQuery->rowCount() > 0) {
            echo json_encode([
                "message" => "Customer soft deleted successfully.",
                "CustomerID" => $customerID
            ]);
        } else {
            echo json_encode(["error" => "Customer not found or already deleted."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?>
