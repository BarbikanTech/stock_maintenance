<?php
header("Content-Type: application/json");

// Include the database configuration file
include 'dbconfig/config.php'; 

// Check if $conn is set
if (!$conn) {
    echo json_encode(["error" => "Database connection failed!"]);
    http_response_code(500); // Internal Server Error
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Create a new vendor
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['VendorName']) || !isset($input['MobileNumber'])) {
        echo json_encode(["error" => "VendorName and MobileNumber are required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $vendorName = $input['VendorName'];
    $businessName = $input['BusinessName'] ?? null; // Optional field
    $mobileNumber = $input['MobileNumber'];
    $createdDate = date('Y-m-d H:i:s'); // Timestamp for created_date

    try {
        // Insert vendor data into the database
        $query = $conn->prepare(
            "INSERT INTO Vendor (VendorName, BusinessName, MobileNumber, created_date) 
             VALUES (:vendorName, :businessName, :mobileNumber, :createdDate)"
        );
        $query->bindParam(':vendorName', $vendorName);
        $query->bindParam(':businessName', $businessName);
        $query->bindParam(':mobileNumber', $mobileNumber);
        $query->bindParam(':createdDate', $createdDate);
        $query->execute();

        // Get the last inserted id
        $lastId = $conn->lastInsertId();

        // Format VendorID as VEN_001
        $formattedVendorID = 'VEN_' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
        $uniqueID = uniqid('VEN_', true); // Generate a unique ID

        // Update the vendor with the formatted VendorID and unique_id
        $updateQuery = $conn->prepare(
            "UPDATE Vendor SET VendorID = :formattedVendorID, unique_id = :uniqueID WHERE id = :lastId"
        );
        $updateQuery->bindParam(':formattedVendorID', $formattedVendorID);
        $updateQuery->bindParam(':uniqueID', $uniqueID);
        $updateQuery->bindParam(':lastId', $lastId);
        $updateQuery->execute();

        echo json_encode([
            "message" => "Vendor created successfully.",
            "VendorID" => $formattedVendorID,
            "unique_id" => $uniqueID
        ]);
        http_response_code(201); // Created
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
    exit;
} elseif ($method === 'GET') {
    // Fetch all vendors (excluding those marked as deleted)
    try {
        $query = $conn->query("SELECT * FROM Vendor WHERE deleted_at = 0");
        $vendors = $query->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($vendors);
        http_response_code(200); // OK
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
    exit;
} elseif ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    file_put_contents('php://stderr', "Raw input: $rawInput\n");

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["error" => "Invalid JSON input: " . json_last_error_msg()]);
        http_response_code(400); // Bad Request
        exit;
    }

    // Validate input
    if (
        !isset($input['VendorID']) ||
        !isset($input['VendorName']) ||
        !isset($input['MobileNumber'])
    ) {
        echo json_encode(["error" => "VendorID, VendorName, and MobileNumber are required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $vendorID = $input['VendorID'];
    $vendorName = $input['VendorName'];
    $businessName = $input['BusinessName'] ?? null;
    $mobileNumber = $input['MobileNumber'];

    try {
        // Update vendor data
        $query = $conn->prepare(
            "UPDATE Vendor SET VendorName = :vendorName, BusinessName = :businessName, MobileNumber = :mobileNumber 
             WHERE VendorID = :vendorID"
        );
        $query->bindParam(':vendorName', $vendorName);
        $query->bindParam(':businessName', $businessName);
        $query->bindParam(':mobileNumber', $mobileNumber);
        $query->bindParam(':vendorID', $vendorID);
        $query->execute();

        echo json_encode(["message" => "Vendor updated successfully."]);
        http_response_code(200); // OK
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
    exit;
} elseif ($method === 'DELETE') {
    // Soft delete a vendor
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['VendorID'])) {
        echo json_encode(["error" => "VendorID is required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $vendorID = $input['VendorID'];
    $deletedAt = date('Y-m-d H:i:s'); // Timestamp for deletion

    try {
        // Mark vendor as deleted
        $query = $conn->prepare("UPDATE Vendor SET deleted_at = 1 WHERE VendorID = :vendorID");
        $query->bindParam(':vendorID', $vendorID);
        $query->execute();

        echo json_encode(["message" => "Vendor deleted successfully."]);
        http_response_code(200); // OK
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
    exit;
} else {
    echo json_encode(["error" => "Method not allowed."]);
    http_response_code(405); // Method Not Allowed
    exit;
}
?>
