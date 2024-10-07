<?php
include 'DBconn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $payment_type = $_POST['payment_type'] ?? null;
    $order_id = $_POST['order_id'] ?? null;

    if ($token && $payment_type && $order_id) {
        // Step 1: Get customer ID from session token
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($customer_id);
        $stmt->fetch();
        $stmt->close();

        if ($customer_id) {
            // Step 2: Insert payment details into the payments table
            $stmt = $conn->prepare("INSERT INTO payments (paymet_type, order_id) VALUES (?, ?)");
            $stmt->bind_param("si", $payment_type, $order_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(["success" => true, "message" => "Payment processed successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid token"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Required data missing"]);
    }

    $conn->close();
}
?>
