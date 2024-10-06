<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        $stmt = $conn->prepare("UPDATE customers SET firstName = ?, lastName = ?, phone = ?, address = ? WHERE customer_id = ?");
        $stmt->bind_param("ssssi", $firstName, $lastName, $phone, $address, $customer_id);
        $stmt->execute();

        echo json_encode(["message" => "Profile updated"]);
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}
