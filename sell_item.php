<?php
include 'DBconn.php';
header('Content-Type: application/json');

// Function to send JSON responses with appropriate HTTP status codes
function sendResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", 405);
}

// Check for required fields
if (empty($_POST['token']) || empty($_POST['description']) || empty($_POST['price'])) {
    sendResponse("error", "Missing required fields", 400);
}

$token = $_POST['token'];
$description = $_POST['description'];
$price = (float) $_POST['price'];

// Create directory for uploaded sell images if it doesn't exist
$upload_dir = 'uploads/sells/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$image_paths = [];

// Process uploaded images (up to 3 images)
for ($i = 1; $i <= 3; $i++) {
    if (isset($_FILES["image$i"]) && $_FILES["image$i"]['size'] > 0) {
        if ($_FILES["image$i"]['size'] > 10000000) { // Check image size limit
            sendResponse("error", "One or more images exceed the 10MB limit", 400);
        }

        // Generate unique image name and move the file
        $image_name = uniqid() . '_' . basename($_FILES["image$i"]['name']);
        $image_path = $upload_dir . $image_name;

        if (!move_uploaded_file($_FILES["image$i"]['tmp_name'], $image_path)) {
            sendResponse("error", "Failed to upload image$i", 500);
        }
        $image_paths[] = $image_path; // Add the uploaded image path
    } else {
        $image_paths[] = ''; // Add empty string if image not uploaded
    }
}

try {
    // Verify token and retrieve customer_id
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !isset($row['customer_id'])) {
        sendResponse("error", "Invalid or expired token", 403);
    }

    $customer_id = $row['customer_id'];

    // Insert sale listing into database
    $insertQuery = "INSERT INTO sell (customer_id, image1, image2, image3, description, price) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);

    if ($stmt->execute([$customer_id, $image_paths[0], $image_paths[1], $image_paths[2], $description, $price])) {
        sendResponse("success", "Item listed for sale", 201);
    } else {
        sendResponse("error", "Error executing query: " . $stmt->errorInfo()[2], 500);
    }
} catch (Exception $e) {
    sendResponse("error", "Server error: " . $e->getMessage(), 500);
} finally {
    $conn = null; // Close connection
}
