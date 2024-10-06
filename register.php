<?php
header('Content-Type: application/json');

include 'DBconn.php';

function sendResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", 405);
}

$requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'password'];
$userData = [];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        sendResponse("error", "All fields are required", 400);
    }
    $userData[$field] = trim($_POST[$field]);
}

// Validate email
if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
    sendResponse("error", "Invalid email format", 400);
}

// Check if email already exists
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
$stmt->bind_param("s", $userData['email']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    sendResponse("error", "Email already registered", 409);
}
$stmt->close();

// Hash password
$userData['password'] = password_hash($userData['password'], PASSWORD_BCRYPT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO `customers` (`firstName`, `lastName`, `email`, `phone`, `ddress`, `pwrd`) VALUES (?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    sendResponse("error", "Error preparing statement: " . $conn->error, 500);
}

$stmt->bind_param("ssssss", $userData['firstName'], $userData['lastName'], $userData['email'], $userData['phone'], $userData['address'], $userData['password']);

if ($stmt->execute()) {
    sendResponse("success", "Registration successful", 201);
} else {
    sendResponse("error", "Error: " . $stmt->error, 500);
}

$stmt->close();
$conn->close();