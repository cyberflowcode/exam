<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete records from dependent tables
        // Delete from student_program_history
        $conn->query("DELETE FROM student_program_history WHERE student_id = $id");
        
        // Delete from student_year_section_history
        $conn->query("DELETE FROM student_year_section_history WHERE student_id = $id");
        
        // Delete from exam_students
        $conn->query("DELETE FROM exam_students WHERE student_id = $id");
        
        // Delete from responses
        $conn->query("DELETE FROM responses WHERE student_id = $id");
        
        // Finally delete the student
        $delete_sql = "DELETE FROM students WHERE id = $id";
        $conn->query($delete_sql);
        
        // Commit transaction
        $conn->commit();
            $_SESSION['success'] = "Student deleted successfully";
        
        header('Location: ../instructor/view-student.php');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header('Location: ../instructor/view-student.php?error=' . urlencode($e->getMessage()));
    }
    
    exit();
}
?>