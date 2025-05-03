<?php
require '../db/db.php'; // Adjust path as necessary

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = !empty($_POST['program_name']) ? $conn->real_escape_string($_POST['program_name']) : null;
    $year = !empty($_POST['year']) ? $conn->real_escape_string($_POST['year']) : null;
    $section = !empty($_POST['section']) ? $conn->real_escape_string($_POST['section']) : null;

    // Check for existing program
    if ($program_name) {
        $check_program_sql = "SELECT * FROM programs WHERE program_name = '$program_name'";
        $program_result = $conn->query($check_program_sql);

        if ($program_result->num_rows > 0) {
            echo json_encode(['error' => 'This Program already exists!']);
            exit();
        }
    }

    // Check for existing year-section combination
    if ($year && $section) {
        $check_year_section_sql = "SELECT * FROM year_sections WHERE year = '$year' AND section = '$section'";
        $year_section_result = $conn->query($check_year_section_sql);

        if ($year_section_result->num_rows > 0) {
            echo json_encode(['error' => 'This Year and Section already exists!']);
            exit();
        }
    } elseif ($year && !$section) {
        echo json_encode(['error' => 'You must enter both year and section together.']);
        exit();
    }

    // If no errors, return a success message
    echo json_encode(['success' => true]);
}