<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id'])) {
    $exam_id = intval($_GET['id']);

    // Update the exam status to 'active'
    $sql = "UPDATE exams SET status = 'active' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $exam_id);
        if ($stmt->execute()) {
            // Redirect back with a success alert
            echo "<script>alert('Exam started successfully.'); window.location.href='../instructor/manage-exams.php';</script>";
            exit();
        } else {
            // Handle error
            echo "Error updating exam: " . $stmt->error;
        }
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "No exam ID provided.";
}
?>