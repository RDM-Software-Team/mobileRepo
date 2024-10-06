<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $description = $_POST['description'];
    $booked_date = $_POST['booked_date'];

    // Create a directory for repair images if it doesn't exist
    $upload_dir = 'uploads/repairs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Process uploaded image
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        if ($_FILES['image']['size'] > 10000000) {
            echo json_encode(["message" => "Image size exceeds the 10MB limit"]);
            exit();
        }
        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row && isset($row['customer_id'])) {
                $customer_id = $row['customer_id'];
                $stmt = $conn->prepare("INSERT INTO `repairs` (`customer_id`, `image`, `description`, `booked_date`) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $customer_id, $image_path, $description, $booked_date);

                if ($stmt->execute()) {
                    echo json_encode(["message" => "Repair request submitted"]);
                } else {
                    echo json_encode(["message" => "Error: " . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(["message" => "Invalid token"]);
            }
        } else {
            echo json_encode(["message" => "Failed to upload image"]);
        }
    } else {
        echo json_encode(["message" => "No image uploaded"]);
    }

    $conn->close();
}
