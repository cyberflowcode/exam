<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('../index.php');
    exit();
}

// Fetch all exams with their status
$exams_sql = "SELECT id, status FROM exams";
$exams_result = $conn->query($exams_sql);

$exams = [];
if ($exams_result->num_rows > 0) {
    while ($exam = $exams_result->fetch_assoc()) {
        $exams[] = [
            'id' => $exam['id'],
            'status' => $exam['status']
        ];
    }
}

?>