<?php
require '../db/db.php'; // Include your database connection

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); 
    exit();
}

$exam_id = $_GET['id'] ?? null;

if (!$exam_id) {
    die("Exam ID is required.");
}

// Prepare and execute the delete statement
$delete_sql = "DELETE FROM exams WHERE id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();

// Check if the deletion was successful
if ($stmt->affected_rows > 0) {
    // Optionally, also delete related records in exam_students
    $delete_students_sql = "DELETE FROM exam_students WHERE exam_id = ?";
    $delete_students_stmt = $conn->prepare($delete_students_sql);
    $delete_students_stmt->bind_param("i", $exam_id);
    $delete_students_stmt->execute();

    // Redirect back to the manage exams page with a success message
    header("Location: ../instructor/manage-exams.php?delete=success");
} else {
    // Redirect back with an error message
    header("Location: ../instructor/manage-exams.php?delete=failure");
}

exit();
?>