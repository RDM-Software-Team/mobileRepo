<?php
    include 'DBconn.php';
    header('Content-Type: application/json');

    $sql = "SELECT * FROM customers";
    $result = mysqli_query($conn, $sql);
    $rows = array();
    while($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    echo json_encode($rows);