<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $student_sql = "SELECT * FROM students WHERE id = '$id'";
    $student_result = $conn->query($student_sql);
    $student = $student_result->fetch_assoc();

    $programs_sql = "SELECT * FROM programs";
    $programs_result = $conn->query($programs_sql);

    $year_sections_sql = "SELECT * FROM year_sections";
    $year_sections_result = $conn->query($year_sections_sql);
}

if (isset($_POST['update'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $school_id = $_POST['school_id'];
    $program_id = $_POST['program_id'];
    $year_section_id = $_POST['year_section_id'];
    $id = $_POST['student_id'];
    
    // Check if username already exists (excluding current student)
    $check_username_sql = "SELECT * FROM students WHERE username = '$username' AND id != $id";
    $username_result = $conn->query($check_username_sql);
    
    // Check if school_id already exists (excluding current student)
    $check_school_id_sql = "SELECT * FROM students WHERE school_id = '$school_id' AND id != $id";
    $school_id_result = $conn->query($check_school_id_sql);
    
    if ($username_result->num_rows > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        header("Location: update-student.php?id=$id");
        exit();
    }
    
    if ($school_id_result->num_rows > 0) {
        $_SESSION['error'] = "School ID already exists. Please check and try again.";
        header("Location: update-student.php?id=$id");
        exit();
    }

    // Update student with or without password change
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_sql = "UPDATE students SET username = '$username', full_name = '$full_name', password = '$password', phone = '$phone', school_id = '$school_id', program_id = '$program_id', year_section_id = '$year_section_id' WHERE id = '$id'";
    } else {
        $update_sql = "UPDATE students SET username = '$username', full_name = '$full_name', phone = '$phone', school_id = '$school_id', program_id = '$program_id', year_section_id = '$year_section_id' WHERE id = '$id'";
    }
    
    if ($conn->query($update_sql)) {
        $_SESSION['success'] = "Student updated successfully!";
        header('Location: view-student.php');
        exit();
    } else {
        $_SESSION['error'] = "Error updating student: " . $conn->error;
        header("Location: update-student.php?id=$id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        .error-feedback {
            color: #dc3545;
            font-size: 80%;
            margin-top: 0.25rem;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .alert {
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            animation: fadeInDown 0.5s ease-out;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="text-white vh-100 position-fixed" style="width: 190px;">
            <?php include '../sidebar/sidebar.php'; ?>
        </div>

        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Update Student</h1>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="updateStudentForm">
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $student['username']; ?>" required>
                                        <div id="username-feedback" class="error-feedback"></div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $student['full_name']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Leave blank if you don't want to change the password.</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $student['phone']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="school_id" class="form-label">School ID</label>
                                        <input type="text" class="form-control" id="school_id" name="school_id" value="<?php echo $student['school_id']; ?>" required>
                                        <div id="schoolid-feedback" class="error-feedback"></div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="program_id" class="form-label">Program</label>
                                        <select class="form-select" id="program_id" name="program_id" required>
                                            <?php while ($program = $programs_result->fetch_assoc()) { ?>
                                                <option value="<?php echo $program['id']; ?>" <?php if ($program['id'] == $student['program_id']) { echo 'selected'; } ?>><?php echo $program['program_name']; ?></option>
                                            <?php } 
                                            $programs_result->data_seek(0);
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="year_section_id" class="form-label">Year and Section</label>
                                        <select class="form-select" id="year_section_id" name="year_section_id" required>
                                            <?php while ($year_section = $year_sections_result->fetch_assoc()) { ?>
                                                <option value="<?php echo $year_section['id']; ?>" <?php if ($year_section['id'] == $student['year_section_id']) { echo 'selected'; } ?>><?php echo $year_section['year'] . ' ' . $year_section['section']; ?></option>
                                            <?php } 
                                            $year_sections_result->data_seek(0);
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="view-student.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Students
                                </a>
                                <button type="submit" class="btn btn-primary" name="update">
                                    <i class="bi bi-save me-2"></i> Update Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Check for duplicate username with AJAX
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            const studentId = <?php echo $student['id']; ?>;
            if (username) {
                fetch('../backend/check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'check=username&value=' + encodeURIComponent(username) + '&exclude_id=' + studentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        document.getElementById('username-feedback').textContent = 'Username already exists. Please choose a different username.';
                        document.getElementById('username').classList.add('is-invalid');
                    } else {
                        document.getElementById('username-feedback').textContent = '';
                        document.getElementById('username').classList.remove('is-invalid');
                    }
                });
            }
        });

        // Check for duplicate school ID with AJAX
        document.getElementById('school_id').addEventListener('blur', function() {
            const schoolId = this.value;
            const studentId = <?php echo $student['id']; ?>;
            if (schoolId) {
                fetch('../backend/check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'check=school_id&value=' + encodeURIComponent(schoolId) + '&exclude_id=' + studentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        document.getElementById('schoolid-feedback').textContent = 'School ID already exists. Please check and try again.';
                        document.getElementById('school_id').classList.add('is-invalid');
                    } else {
                        document.getElementById('schoolid-feedback').textContent = '';
                        document.getElementById('school_id').classList.remove('is-invalid');
                    }
                });
            }
        });
        
        // Form validation on submit
        document.getElementById('updateStudentForm').addEventListener('submit', function(e) {
            const usernameInvalid = document.getElementById('username').classList.contains('is-invalid');
            const schoolIdInvalid = document.getElementById('school_id').classList.contains('is-invalid');
            
            if (usernameInvalid || schoolIdInvalid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix the highlighted errors before submitting.'
                });
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>