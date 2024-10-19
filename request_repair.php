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

// Check if token, description, and booked date are provided
if (empty($_POST['token']) || empty($_POST['description']) || empty($_POST['booked_date'])) {
    sendResponse("error", "Missing required fields", 400);
}

$token = $_POST['token'];
$description = $_POST['description'];
$booked_date = $_POST['booked_date'];

// Create directory for uploaded repair images if it doesn't exist
$upload_dir = 'uploads/repairs/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process the uploaded image
if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    if ($_FILES['image']['size'] > 10000000) { // Check image size
        sendResponse("error", "Image size exceeds the 10MB limit", 400);
    }

    // Generate unique file name
    $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $image_path = $upload_dir . $image_name;

    // Move uploaded image to the correct folder
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
        sendResponse("error", "Failed to upload image", 500);
    }

    try {
        // Verify token and get customer_id
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !isset($row['customer_id'])) {
            sendResponse("error", "Invalid or expired token", 403);
        }

        $customer_id = $row['customer_id'];

        // Insert repair request into database
        $insertQuery = "INSERT INTO repairs (customer_id, image, description, booked_date) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        
        if ($stmt->execute([$customer_id, $image_path, $description, $booked_date])) {
            sendResponse("success", "Repair request submitted", 201);
        } else {
            sendResponse("error", "Error executing query: " . $stmt->errorInfo()[2], 500);
        }
    } catch (Exception $e) {
        sendResponse("error", "Server error: " . $e->getMessage(), 500);
    } finally {
        $conn = null; // Close the connection
    }
} else {
    sendResponse("error", "No image uploaded", 400);
}
