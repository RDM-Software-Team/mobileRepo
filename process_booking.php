<?php
include 'DBconn.php';

// Set response headers for JSON output
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to send JSON responses
function sendResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $booking_time = $_POST['booking_time'] ?? null;
    $booked_date = $_POST['booked_date'] ?? null;

    // Validate the token, duration, booking_time, and booked_date
    if (!$token) {
        sendResponse("error", "Token is required", 400);
    }
    if (!$duration) {
        sendResponse("error", "Duration is required", 400);
    }
    if (!$booking_time) {
        sendResponse("error", "Booking time is required", 400);
    }
    if (!$booked_date) {
        sendResponse("error", "Booked date is required", 400);
    }

    try {
        // Fetch customer_id based on the token
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
        $stmt->execute([$token]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no customer_id found, return an error
        if (!$customer) {
            sendResponse("error", "Invalid or expired token", 403);
        }
        
        $customer_id = $customer['customer_id'];

        // Insert booking details into the database
        $query = "INSERT INTO bookings (customer_id, duration, booking_time, booked_date, status) 
                  VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($query);

        if ($stmt->execute([$customer_id, $duration, $booking_time, $booked_date])) {
            sendResponse("success", "Booking submitted successfully", 201);
        } else {
            sendResponse("error", "Failed to submit booking", 500);
        }

    } catch (Exception $e) {
        sendResponse("error", "Server error: " . $e->getMessage(), 500);
    } finally {
        // Ensure the database connection is closed
        if ($conn) {
            $conn = null; // Close the PDO connection
        }
    }
} else {
    sendResponse("error", "Invalid request method", 405);
}
