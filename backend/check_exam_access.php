<?php
require '../db/db.php';

function checkExamAccess($exam_id, $student_id) {
    global $conn;
    
    $exam_sql = "
        SELECT e.*, p.program_name, ys.year, ys.section 
        FROM exams e
        LEFT JOIN programs p ON e.program_id = p.id
        LEFT JOIN year_sections ys ON e.year_section_id = ys.id
        WHERE e.id = ? AND e.status = 'active'
    ";
    
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("i", $exam_id);
    $exam_stmt->execute();
    $exam_result = $exam_stmt->get_result();
    $exam = $exam_result->fetch_assoc();
    
    if (!$exam) {
        return ['success' => false, 'message' => 'Exam not found or not active'];
    }
    
    // Check enrollment
    $enrollment_sql = "SELECT * FROM exam_students WHERE exam_id = ? AND student_id = ?";
    $enrollment_stmt = $conn->prepare($enrollment_sql);
    $enrollment_stmt->bind_param("ii", $exam_id, $student_id);
    $enrollment_stmt->execute();
    $enrollment_result = $enrollment_stmt->get_result();
    
    if (!$enrollment_result->fetch_assoc()) {
        return ['success' => false, 'message' => 'You are not enrolled in this exam'];
    }
    
    return ['success' => true, 'exam' => $exam];
}
?>