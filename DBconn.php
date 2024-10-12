<?php
    /*$servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "computer_complex";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
        */
// PHP Data Objects(PDO) Sample Code:
try {
    $conn = new PDO("sqlsrv:server = tcp:disappdd.database.windows.net,1433; Database = ComputerCmplex", "st10107568", "dianaK1209$");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    print("Error connecting to SQL Server.");
    die(print_r($e));
}

// SQL Server Extension Sample Code:
$connectionInfo = array("UID" => "st10107568", "pwd" => "dianaK1209$", "Database" => "ComputerCmplex", "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
$serverName = "tcp:disappdd.database.windows.net,1433";
$conn = sqlsrv_connect($serverName, $connectionInfo);
