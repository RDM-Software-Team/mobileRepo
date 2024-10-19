<?php
include 'DBconn.php';  // Ensure this file sets up your PDO connection
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $payment_type = $_POST['payment_type'] ?? null;
    $order_id = $_POST['order_id'] ?? null;

    if ($token && $payment_type && $order_id) {
        try {
            // Step 1: Get customer ID from session token
            $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
            $stmt->execute([$token]);  // Pass parameters as an array to `execute()`
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);  // Fetch as associative array

            if ($customer && isset($customer['customer_id'])) {
                $customer_id = $customer['customer_id'];

                // Step 2: Insert payment details into the payments table
                $stmt = $conn->prepare("INSERT INTO payments (payment_type, order_id, customer_id) VALUES (?, ?, ?)");
                $stmt->execute([$payment_type, $order_id, $customer_id]);  // Pass parameters for insertion

                echo json_encode(["success" => true, "message" => "Payment processed successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid token"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error processing payment: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Required data missing"]);
    }

    $conn = null;  // Close the connection
}
?>
