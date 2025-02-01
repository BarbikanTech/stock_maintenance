<?php
include 'config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (
        !isset($input['ProductName']) ||
        !isset($input['MainUnit']) ||
        !isset($input['SubUnit'])
    ) {
        echo json_encode(["error" => "ProductName, MainUnit, and SubUnit are required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $productName = $input['ProductName'];
    $mainUnit = $input['MainUnit'];
    $subUnit = $input['SubUnit'];

    // Generate unique_id
    $uniqueId = 'unit-' . uniqid();

    try {
        $query = $conn->prepare(
            "INSERT INTO unit (unique_id, ProductName, MainUnit, SubUnit, created_date) 
             VALUES (:unique_id, :productName, :mainUnit, :subUnit, NOW())"
        );
        $query->bindParam(':unique_id', $uniqueId);
        $query->bindParam(':productName', $productName);
        $query->bindParam(':mainUnit', $mainUnit);
        $query->bindParam(':subUnit', $subUnit);
        $query->execute();

        echo json_encode(["message" => "Unit created successfully.", "unique_id" => $uniqueId]);
        http_response_code(201); // Created
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all units
    try {
        $stmt = $conn->prepare("SELECT unique_id, ProductName, MainUnit, SubUnit, created_date FROM unit WHERE deleted_at = 0 ORDER BY ProductName ASC");
        $stmt->execute();
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'units' => $units]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update an existing unit
    $input = json_decode(file_get_contents('php://input'), true);

    if (
        !isset($input['unique_id']) ||
        !isset($input['ProductName']) ||
        !isset($input['MainUnit']) ||
        !isset($input['SubUnit'])
    ) {
        echo json_encode(["error" => "unique_id, ProductName, MainUnit, and SubUnit are required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $uniqueId = $input['unique_id'];
    $productName = $input['ProductName'];
    $mainUnit = $input['MainUnit'];
    $subUnit = $input['SubUnit'];

    try {
        $query = $conn->prepare(
            " UPDATE unit 
            SET ProductName = :productName, MainUnit = :mainUnit, SubUnit = :subUnit, updated_date = NOW() 
            WHERE unique_id = :unique_id AND deleted_at = 0"
        );

        $query->bindParam(':unique_id', $uniqueId);
        $query->bindParam(':productName', $productName);
        $query->bindParam(':mainUnit', $mainUnit);
        $query->bindParam(':subUnit', $subUnit);
        $query->execute();

        if ($query->rowCount() > 0) {
            echo json_encode(["message" => "Unit updated successfully."]);
        } else {
            echo json_encode(["error" => "Unit not found or already deleted."]);
            http_response_code(404); // Not Found
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Soft delete a unit
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['unique_id'])) {
        echo json_encode(["error" => "unique_id is required."]);
        http_response_code(400); // Bad Request
        exit;
    }

    $uniqueId = $input['unique_id'];

    try {
        $query = $conn->prepare(
            "UPDATE unit 
             SET deleted_at = 1, updated_date = NOW() 
             WHERE unique_id = :unique_id AND deleted_at = 0"
        );
        $query->bindParam(':unique_id', $uniqueId);
        $query->execute();

        if ($query->rowCount() > 0) {
            echo json_encode(["message" => "Unit deleted successfully."]);
        } else {
            echo json_encode(["error" => "Unit not found or already deleted."]);
            http_response_code(404); // Not Found
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
