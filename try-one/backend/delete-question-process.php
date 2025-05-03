// backend/delete-question.php
<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['question_id']) && isset($_POST['exam_id'])) {
    $question_id = $_POST['question_id'];
    $exam_id = $_POST['exam_id'];
    $sql = "DELETE FROM questions WHERE id = '$question_id'";
    if ($conn->query($sql)) {
        // Update item count in exams table
        $questions_sql = "SELECT * FROM questions WHERE exam_id = '$exam_id'";
        $questions_result = $conn->query($questions_sql);
        $total_questions = $questions_result->num_rows;
        $update_sql = "UPDATE exams SET item = '$total_questions' WHERE id = '$exam_id'";
        $conn->query($update_sql);
        echo "Question deleted successfully";
    } else {
        echo "Error deleting question";
    }
}
?>