<?php
    // Start session only if it hasn't been started yet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "exam_system_sfxc_finali";

    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

    // Set character set to utf8mb4
    $conn->set_charset("utf8mb4");

    // Your code here (e.g. queries)

    // Close connection when done
    // $conn->close();
?>