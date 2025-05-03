<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    $update_sql = "UPDATE exams SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Exam status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update exam status";
    }
    
    header("Location: ../instructor/manage-exams.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request";
    header("Location: ../instructor/manage-exams.php");
    exit();
}
?>