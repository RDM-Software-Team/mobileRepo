<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        $new_token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

        $stmt = $conn->prepare("UPDATE sessions SET token = ?, expiry = ? WHERE customer_id = ?");
        $stmt->bind_param("ssi", $new_token, $expiry, $customer_id);
        $stmt->execute();

        echo json_encode(["token" => $new_token]);
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}