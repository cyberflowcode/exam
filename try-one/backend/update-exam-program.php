<?php
require '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $type = key($_POST); // This will get 'program' or 'year_section'
    $value = $_POST[$type];

    if ($type === 'program') {
        $update_sql = "UPDATE exams SET program_id = (SELECT id FROM programs WHERE program_name = ?) WHERE id = ?";
    } else {
        list($year, $section) = explode(' ', $value);
        $update_sql = "UPDATE exams SET year_section_id = (SELECT id FROM year_sections WHERE year = ? AND section = ?) WHERE id = ?";
    }

    $stmt = $conn->prepare($update_sql);
    if ($type === 'program') {
        $stmt->bind_param("si", $value, $id);
    } else {
        $stmt->bind_param("ssi", $year, $section, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
}
?>