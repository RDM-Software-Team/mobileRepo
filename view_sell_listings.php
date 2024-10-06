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
        $stmt = $conn->prepare("SELECT sell_id, image1, image2, image3, description, price FROM sell WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $sells = [];
        while ($row = $result->fetch_assoc()) {
            $row['image1'] = base64_encode($row['image1']);
            $row['image2'] = base64_encode($row['image2']);
            $row['image3'] = base64_encode($row['image3']);
            $sells[] = $row;
        }

        echo json_encode($sells);
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}