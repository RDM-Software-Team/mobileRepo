<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT customer_id, pwrd FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($customer_id, $hashed_password);
    $stmt->fetch();

    if ($customer_id && password_verify($password, $hashed_password)) {
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

        $stmt = $conn->prepare("REPLACE INTO sessions (customer_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $customer_id, $token, $expiry);
        $stmt->execute();

        echo json_encode(["token" => $token]);
    } else {
        echo json_encode(["message" => "Invalid credentials"]);
    }

    $stmt->close();
    $conn->close();
}