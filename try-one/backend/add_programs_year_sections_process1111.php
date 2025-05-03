<?php
require '../db/db.php'; // Adjust path as necessary

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = !empty($_POST['program_name']) ? $conn->real_escape_string($_POST['program_name']) : null; // Optional
    $year = !empty($_POST['year']) ? $conn->real_escape_string($_POST['year']) : null; // Optional
    $section = !empty($_POST['section']) ? $conn->real_escape_string($_POST['section']) : null; // Optional

    // Check for existing program
    if ($program_name) {
        $check_program_sql = "SELECT * FROM programs WHERE program_name = '$program_name'";
        $program_result = $conn->query($check_program_sql);

        if ($program_result->num_rows > 0) {
            echo json_encode(['error' => 'This program already exists!']);
            exit();
        }

        // Insert program into the database
        $sql = "INSERT INTO programs (program_name) VALUES ('$program_name')";
        if ($conn->query($sql) === TRUE) {
            // Redirect on success
            header("Location: ../instructor/programandyrsection.php"); // Change to your success page
            exit();
        } else {
            echo json_encode(['error' => $conn->error]); // Handle errors appropriately
            exit();
        }
    }

    // Check for existing year-section combination
    if ($year && $section) {
        $check_year_section_sql = "SELECT * FROM year_sections WHERE year = '$year' AND section = '$section'";
        $year_section_result = $conn->query($check_year_section_sql);

        if ($year_section_result->num_rows > 0) {
            echo json_encode(['error' => 'This year-section combination already exists!']);
            exit();
        }

        // Only insert if both year and section are provided
        $sql_year_section = "INSERT INTO year_sections (year, section) VALUES ('$year', '$section')";
        if ($conn->query($sql_year_section) === TRUE) {
            // Redirect on success
            header("Location: ../instructor/programandyrsection.php"); // Change to your success page
            exit();
        } else {
            echo json_encode(['error' => $conn->error]); // Handle errors appropriately
            exit();
        }
    } else {
        // Error message if validation fails
        echo json_encode(['error' => 'Please enter a program name, or both year and section.']);
        exit();
    }
}