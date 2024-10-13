<?php
header('Content-Type: application/json');

include 'DBconn.php';

// Function to send JSON responses with appropriate HTTP status codes
function sendResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", 405);
}

// Required fields for registration
$requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'password'];
$userData = [];

// Validate that all fields are provided
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        sendResponse("error", "All fields are required", 400);
    }
    $userData[$field] = trim($_POST[$field]);
}

// Validate email format
if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
    sendResponse("error", "Invalid email format", 400);
}

try {
    // Check if the email is already registered
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
    $stmt->execute([$userData['email']]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse("error", "Email already registered", 409);
    }

    // Hash the password
    $userData['password'] = password_hash($userData['password'], PASSWORD_BCRYPT);

    // Insert the new user into the database
    $insertQuery = "INSERT INTO customers (firstName, lastName, email, phone, address, pwrd) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    
    if ($stmt->execute([
        $userData['firstName'],
        $userData['lastName'],
        $userData['email'],
        $userData['phone'],
        $userData['address'],
        $userData['password']
    ])) {
        sendResponse("success", "Registration successful", 201);
    } else {
        sendResponse("error", "Error executing query: " . $stmt->errorInfo()[2], 500);
    }
} catch (Exception $e) {
    sendResponse("error", "Server error: " . $e->getMessage(), 500);
} finally {
    // Close the connection
    $conn = null;
}
