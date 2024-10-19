<?php
include 'DBconn.php';

header('Content-Type: application/json'); // Ensure JSON response is returned

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;

    if (!$token) {
        echo json_encode(["message" => "Token is required"]);
        exit;
    }

    try {
        // Verify the token and retrieve customer_id (using SQL Server's GETDATE())
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
        $stmt->execute([$token]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Generate new token and set expiry
            $new_token = bin2hex(random_bytes(16));
            $expiry = (new DateTime('now', new DateTimeZone('Africa/Johannesburg')))
                        ->modify('+5 minutes')
                        ->format('Y-m-d H:i:s');

            // Update token and expiry for the customer
            $update_stmt = $conn->prepare("UPDATE sessions SET token = ?, expiry = ? WHERE customer_id = ?");
            $update_stmt->execute([$new_token, $expiry, $customer['customer_id']]);

            // Return new token in JSON format
            echo json_encode(["token" => $new_token]);
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
