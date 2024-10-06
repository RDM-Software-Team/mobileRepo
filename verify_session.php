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
        echo json_encode(["message" => "Session valid", "customer_id" => $customer_id]);
    } else {
        echo json_encode(["message" => "Session invalid or expired"]);
    }

    $stmt->close();
    $conn->close();
}
