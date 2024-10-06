<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $description = $_POST['description'];
    $booked_date = $_POST['booked_date'];
    $image = file_get_contents($_FILES['image']['tmp_name']); // Handle image upload

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        $stmt = $conn->prepare("INSERT INTO repair (customer_id, image, description, booked_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ibss", $customer_id, $image, $description, $booked_date);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Repair request submitted"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}