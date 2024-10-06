<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    $image1 = file_get_contents($_FILES['image1']['tmp_name']);
    $image2 = file_get_contents($_FILES['image2']['tmp_name']);
    $image3 = file_get_contents($_FILES['image3']['tmp_name']);

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        $stmt = $conn->prepare("INSERT INTO sell (customer_id, image1, image2, image3, description, price) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ibbbssd", $customer_id, $image1, $image2, $image3, $description, $price);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Item listed for sale"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}
