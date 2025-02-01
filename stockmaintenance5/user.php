<?php
include("config.php");
header('Content-Type: application/json; charset=utf-8');

// Initialize output variable
$output = [];

// Check for 'action' in the GET request
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Function to register a new user
    function registerUser($username, $password, $role) {
        global $conn;
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();

            return ["status" => 200, "msg" => "User registered successfully"];
        } catch (PDOException $e) {
            return ["status" => 400, "msg" => "Error: " . $e->getMessage()];
        }
    }

    // Function to login a user (POST method)
    function loginUser($username, $password) {
        global $conn;

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND delete_at = 0");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // On successful login, return the user data
                return ["status" => 200, "msg" => "Login successful", "data" => $user];
            } else {
                return ["status" => 400, "msg" => "Invalid username or password"];
            }
        } catch (PDOException $e) {
            return ["status" => 400, "msg" => "Error: " . $e->getMessage()];
        }
    }

    // Function to update user password
    function updatePassword($userId, $newPassword) {
        global $conn;
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return ["status" => 200, "msg" => "Password updated successfully"];
        } catch (PDOException $e) {
            return ["status" => 400, "msg" => "Error: " . $e->getMessage()];
        }
    }

    // Function to edit user details
    function editUser($userId, $newUsername, $newRole) {
        global $conn;

        try {
            $stmt = $conn->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
            $stmt->bindParam(':username', $newUsername, PDO::PARAM_STR);
            $stmt->bindParam(':role', $newRole, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return ["status" => 200, "msg" => "User updated successfully"];
        } catch (PDOException $e) {
            return ["status" => 400, "msg" => "Error: " . $e->getMessage()];
        }
    }

    // Function to soft delete a user
    function deleteUser($userId) {
        global $conn;

        try {
            $stmt = $conn->prepare("UPDATE users SET delete_at = 1 WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return ["status" => 200, "msg" => "User deleted successfully"];
        } catch (PDOException $e) {
            return ["status" => 400, "msg" => "Error: " . $e->getMessage()];
        }
    }

    // Handle the action based on the action parameter
    switch ($action) {
        case "register":
            if (isset($_POST['username'], $_POST['password'], $_POST['role'])) {
                $output = registerUser($_POST['username'], $_POST['password'], $_POST['role']);
            } else {
                $output = ["status" => 400, "msg" => "Missing parameters for registration"];
            }
            break;

        case "login":
            if (isset($_POST['username'], $_POST['password'])) {
                $output = loginUser($_POST['username'], $_POST['password']);
            } else {
                $output = ["status" => 400, "msg" => "Missing username or password"];
            }
            break;

        case "update_password":
            if (isset($_POST['id'], $_POST['newPassword'])) {
                $output = updatePassword($_POST['id'], $_POST['newPassword']);
            } else {
                $output = ["status" => 400, "msg" => "Missing parameters for updating password"];
            }
            break;

        case "edit_user":
            if (isset($_POST['id'], $_POST['newUsername'], $_POST['newRole'])) {
                $output = editUser($_POST['id'], $_POST['newUsername'], $_POST['newRole']);
            } else {
                $output = ["status" => 400, "msg" => "Missing parameters for editing user"];
            }
            break;

        case "delete_user":
            if (isset($_POST['id'])) {
                $output = deleteUser($_POST['id']);
            } else {
                $output = ["status" => 400, "msg" => "Missing user ID for deletion"];
            }
            break;

        default:
            $output = ["status" => 400, "msg" => "Invalid action"];
    }
} else {
    $output = ["status" => 400, "msg" => "Action not provided"];
}

// Return JSON response
echo json_encode($output, JSON_PRETTY_PRINT);
?>
