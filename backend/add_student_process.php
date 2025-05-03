<?php
require '../db/db.php'; // Ensure this is the correct path

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $conn->real_escape_string($_POST['username']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $phone = $conn->real_escape_string($_POST['phone']);
    $school_id = $conn->real_escape_string($_POST['school_id']);
    $program_id = (int)$_POST['program_id'];
    $year_section_id = (int)$_POST['year_section_id'];

    // Check if username already exists
    $check_username_sql = "SELECT * FROM students WHERE username = '$username'";
    $username_result = $conn->query($check_username_sql);
    
    // Check if school_id already exists
    $check_school_id_sql = "SELECT * FROM students WHERE school_id = '$school_id'";
    $school_id_result = $conn->query($check_school_id_sql);
    
    if ($username_result->num_rows > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        $_SESSION['form_data'] = $_POST; // Store form data for repopulating the form
        header("Location: ../instructor/add-student.php");
        exit();
    }
    
    if ($school_id_result->num_rows > 0) {
        $_SESSION['error'] = "School ID already exists. Please check and try again.";
        $_SESSION['form_data'] = $_POST; // Store form data for repopulating the form
        header("Location: ../instructor/add-student.php");
        exit();
    }

    // Insert into database
    $sql = "INSERT INTO students (username, full_name, password, phone, school_id, program_id, year_section_id) 
            VALUES ('$username', '$full_name', '$password', '$phone', '$school_id', $program_id, $year_section_id)";

    if ($conn->query($sql) === TRUE) {
        // Set success message
        $_SESSION['success'] = "Student added successfully!";
        // Redirect on success
        header("Location: ../instructor/add-student.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
        $_SESSION['form_data'] = $_POST; // Store form data for repopulating the form
        header("Location: ../instructor/add-student.php");
        exit();
    }
}
?>