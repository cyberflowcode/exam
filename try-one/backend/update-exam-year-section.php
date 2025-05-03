// update-exam-year-section.php
<?php
require '../db/db.php';

if (isset($_POST['id']) && isset($_POST['year_section_id'])) {
    $id = $_POST['id'];
    $year_section_id = $_POST['year_section_id'];

    $update_sql = "UPDATE exams SET year_section_id = '$year_section_id' WHERE id = '$id'";
    $conn->query($update_sql);
}
?>