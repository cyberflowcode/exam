<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$student_id = $_SESSION['student_id'];

$history_sql = "
    SELECT DISTINCT e.id, e.exam_name, e.created_at, e.item as total_questions,
                    es.completed, es.created_at as enrolled_date,
                    p.program_name, CONCAT(ys.year, '-', ys.section) as class,
                    t.full_name as teacher_name
    FROM exam_students es
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN programs p ON e.program_id = p.id
    LEFT JOIN year_sections ys ON e.year_section_id = ys.id
    LEFT JOIN teachers t ON e.teacher_id = t.id
    WHERE es.student_id = ?
    ORDER BY es.completed DESC, e.created_at DESC
";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $student_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

return $history_result;
?>