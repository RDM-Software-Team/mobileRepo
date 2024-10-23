<?php
// Disable error reporting to prevent unintended output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure we're outputting JSON
header('Content-Type: application/json');

// Function to output JSON and exit
function output_json($data) {
    echo json_encode($data);
    exit;
}

// Start output buffering to catch any unexpected output
ob_start();

include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    output_json(["error" => "Invalid request method"]);
}

$token = $_POST['token'] ?? '';

if (empty($token)) {
    output_json(["error" => "Token is required"]);
}

try {
    // Step 1: Get customer ID from session token
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
    $stmt->execute([$token]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer || !isset($customer['customer_id'])) {
        output_json(["error" => "Invalid token"]);
    }

    $customer_id = $customer['customer_id'];

    // Step 2: Get repairs for the customer
    $stmt = $conn->prepare("SELECT repair_id, description, booked_date, image FROM repairs WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update the image path for each repair
    foreach ($repairs as &$repair) {
        $repair['image'] = "https://backend-php-dsehdsc0adbxbuey.southafricanorth-01.azurewebsites.net/uploads/repairs/" . basename($repair['image']);
    }

    // Clear the output buffer and send the JSON response
    ob_end_clean();
    output_json($repairs);

} catch (Exception $e) {
    // Handle any exception and return an error message
    ob_end_clean();
    output_json(["error" => "Error: " . $e->getMessage()]);
} finally {
    $conn = null; // Close the PDO connection
}
?>
