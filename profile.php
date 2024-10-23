<?php
include 'DBconn.php';

header('Content-Type: application/json'); // Ensure JSON response is returned

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $firstName = $_POST['firstName'] ?? null;
    $lastName = $_POST['lastName'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    if (!$token || !$firstName || !$lastName || !$phone || !$address) {
        echo json_encode(["message" => "All fields are required"]);
        exit;
    }

    try {
        // Verify token and fetch customer ID
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
        $stmt->execute([$token]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Update customer details
            $stmt = $conn->prepare("UPDATE customers SET firstName = ?, lastName = ?, phone = ?, address = ? WHERE customer_id = ?");
            $stmt->execute([$firstName, $lastName, $phone, $address, $customer['customer_id']]);

            echo json_encode(["message" => "Profile updated"]);
        } else {
            echo json_encode(["message" => "Invalid token"]);
        }
    } catch (Exception $e) {
        echo json_encode(["message" => "Error: " . $e->getMessage()]);
    }

    // Close connection
    $conn = null;
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
