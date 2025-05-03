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

    if ($program_name) {
        $check_program_sql = "SELECT * FROM programs WHERE program_name = '$program_name'";
        $program_result = $conn->query($check_program_sql);

        if ($program_result->num_rows > 0) {
            echo json_encode(['error' => 'This program already exists!']);
            exit();
        }

        $sql = "INSERT INTO programs (program_name) VALUES ('$program_name')";
        if ($conn->query($sql) === TRUE) {
            // Redirect on success
            header("Location: ../instructor/programandyrsection.php");
            exit();
        } else {
            echo json_encode(['error' => $conn->error]); 
            exit();
        }
    }

    if ($year && $section) {
        $check_year_section_sql = "SELECT * FROM year_sections WHERE year = '$year' AND section = '$section'";
        $year_section_result = $conn->query($check_year_section_sql);

        if ($year_section_result->num_rows > 0) {
            echo json_encode(['error' => 'This year-section combination already exists!']);
            exit();
        }

        $sql_year_section = "INSERT INTO year_sections (year, section) VALUES ('$year', '$section')";
        if ($conn->query($sql_year_section) === TRUE) {
            header("Location: ../instructor/programandyrsection.php");
            exit();
        } else {
            echo json_encode(['error' => $conn->error]); 
            exit();
        }
    } else {
        echo json_encode(['error' => 'Please enter a program name, or both year and section.']);
        exit();
    }
}