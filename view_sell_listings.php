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

$stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || !isset($row['customer_id'])) {
    output_json(["error" => "Invalid token"]);
}

$customer_id = $row['customer_id'];

$stmt = $conn->prepare("SELECT sell_id, image1, image2, image3, description, price FROM sell WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$sells = [];
while ($row = $result->fetch_assoc()) {
    $row['image1'] = "http://192.168.18.113/computer_Complex_mobile/uploads/sells/" . basename($row['image1']);
    $row['image2'] = "http://192.168.18.113/computer_Complex_mobile/uploads/sells/" . basename($row['image2']);
    $row['image3'] = "http://192.168.18.113/computer_Complex_mobile/uploads/sells/" . basename($row['image3']);
    $sells[] = $row;
}

$stmt->close();
$conn->close();

// Clear the output buffer and send the JSON response
ob_end_clean();
output_json($sells);
?>