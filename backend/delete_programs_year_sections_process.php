<?php
require '../db/db.php'; // Ensure this is the correct path

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['delete_program'])) {
        $program_id = (int)$_POST['delete_program'];
        $conn->query("DELETE FROM programs WHERE id = $program_id");
    }

    if (!empty($_POST['delete_year_section'])) {
        $year_section_id = (int)$_POST['delete_year_section'];
        $conn->query("DELETE FROM year_sections WHERE id = $year_section_id");
    }

    // Redirect back to the form or a success page
    header("Location: ../instructor/programandyrsection.php"); // Change to your success page
    exit();
}