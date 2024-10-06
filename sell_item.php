<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Create a directory for sell images if it doesn't exist
    $upload_dir = 'uploads/sells/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_paths = [];

    // Process uploaded images
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['size'] > 0) {
            if ($_FILES["image$i"]['size'] > 10000000) {
                echo json_encode(["message" => "One or more images exceed the 10MB limit"]);
                exit();
            }
            $image_name = uniqid() . '_' . basename($_FILES["image$i"]['name']);
            $image_path = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES["image$i"]['tmp_name'], $image_path)) {
                $image_paths[] = $image_path;
            } else {
                echo json_encode(["message" => "Failed to upload image$i"]);
                exit();
            }
        } else {
            $image_paths[] = ''; // Add empty string if image not uploaded
        }
    }

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && isset($row['customer_id'])) {
        $customer_id = $row['customer_id'];
        $stmt = $conn->prepare("INSERT INTO `sell` (`customer_id`, `image1`, `image2`, `image3`, `description`, `price`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssd", $customer_id, $image_paths[0], $image_paths[1], $image_paths[2], $description, $price);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Item listed for sale"]);
        } else {
            echo json_encode(["message" => "Error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $conn->close();
}
?>