<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Fetch programs from the database
$programs = [];
$result = $conn->query("SELECT id, program_name FROM programs");
if ($result) {
    $programs = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch year sections from the database
$year_sections = [];
$result = $conn->query("SELECT id, CONCAT(year, ' - ', section) AS year_section FROM year_sections");
if ($result) {
    $year_sections = $result->fetch_all(MYSQLI_ASSOC);
}

// Get form data if it exists (from failed validation)
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // Clear stored form data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
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
        <?php include '../sidebar/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Add New Student</h1>
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
                        <form action="../backend/add_student_process.php" method="POST" id="addStudentForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="number" class="form-control" id="username" name="username" value="<?php echo $form_data['username'] ?? ''; ?>" required>
                                        </div>
                                        <small class="text-muted">Student ID number</small>
                                        <div id="username-feedback" class="error-feedback"></div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $form_data['full_name'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <div id="passwordMatch" class="form-text"></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="number" class="form-control" id="phone" name="phone" value="<?php echo $form_data['phone'] ?? ''; ?>" oninput="javascript: if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="school_id" class="form-label">School ID</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-card-list"></i></span>
                                            <input type="text" class="form-control" id="school_id" name="school_id" value="<?php echo $form_data['school_id'] ?? ''; ?>" required>
                                        </div>
                                        <div id="schoolid-feedback" class="error-feedback"></div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="program_id" class="form-label">Program</label>
                                        <select class="form-select" id="program_id" name="program_id" required>
                                            <option value="">Select Program</option>
                                            <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo $program['id']; ?>" <?php echo (isset($form_data['program_id']) && $form_data['program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="year_section_id" class="form-label">Year/Section</label>
                                        <select class="form-select" id="year_section_id" name="year_section_id" required>
                                            <option value="">Select Year/Section</option>
                                            <?php foreach ($year_sections as $year_section): ?>
                                                <option value="<?php echo $year_section['id']; ?>" <?php echo (isset($form_data['year_section_id']) && $form_data['year_section_id'] == $year_section['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($year_section['year_section']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="view-student.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Students
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus-fill me-2"></i> Add Student
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
        
        // Check password match
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        
        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-text';
            } else if (password.value === confirmPassword.value) {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.className = 'form-text text-success';
            } else {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.className = 'form-text text-danger';
            }
        }
        
        password.addEventListener('keyup', checkPasswordMatch);
        confirmPassword.addEventListener('keyup', checkPasswordMatch);
        
        // Form validation
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Please make sure your passwords match!'
                });
            }
        });

        // Check for duplicate username with AJAX
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            if (username) {
                fetch('../backend/check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'check=username&value=' + encodeURIComponent(username)
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
            if (schoolId) {
                fetch('../backend/check_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'check=school_id&value=' + encodeURIComponent(schoolId)
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