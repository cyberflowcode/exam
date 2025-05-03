<?php
require '../db/db.php';

function startExamSession($exam_id, $student_id) {
    global $conn;
    
    // Check if session already exists
    $check_sql = "SELECT * FROM exam_sessions WHERE exam_id = ? AND student_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $exam_id, $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $session = $result->fetch_assoc();
        return $session;
    }
    
    // Get exam duration
    $exam_sql = "SELECT duration FROM exams WHERE id = ?";
    $exam_stmt = $conn->prepare($exam_sql);
    $exam_stmt->bind_param("i", $exam_id);
    $exam_stmt->execute();
    $exam = $exam_stmt->get_result()->fetch_assoc();
    
    // Calculate end time
    $start_time = time();
    $end_time = $start_time + ($exam['duration'] * 60);
    
    // Create new session
    $insert_sql = "INSERT INTO exam_sessions (exam_id, student_id, start_time, end_time) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiii", $exam_id, $student_id, $start_time, $end_time);
    $insert_stmt->execute();
    
    return [
        'exam_id' => $exam_id,
        'student_id' => $student_id,
        'start_time' => $start_time,
        'end_time' => $end_time
    ];
}

function getExamSession($exam_id, $student_id) {
    global $conn;
    
    $sql = "SELECT * FROM exam_sessions WHERE exam_id = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $exam_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function autoSubmitExpiredExam($exam_id, $student_id) {
    global $conn;
    
    // Mark exam as completed
    $complete_sql = "UPDATE exam_students SET completed = TRUE WHERE exam_id = ? AND student_id = ?";
    $complete_stmt = $conn->prepare($complete_sql);
    $complete_stmt->bind_param("ii", $exam_id, $student_id);
    $complete_stmt->execute();
    
    // Delete session
    $delete_sql = "DELETE FROM exam_sessions WHERE exam_id = ? AND student_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $exam_id, $student_id);
    $delete_stmt->execute();
    
    return true;
}
?>