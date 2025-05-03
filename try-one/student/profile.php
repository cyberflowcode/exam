<?php
require '../db/db.php';

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

// Check if student_id is set in the session
if (!isset($_SESSION['student_id'])) {
    die("User ID is not set in session.");
}

// Fetch student data with program and year-section information
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT s.*, p.program_name, ys.year, ys.section 
    FROM students s 
    LEFT JOIN programs p ON s.program_id = p.id 
    LEFT JOIN year_sections ys ON s.year_section_id = ys.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($conn->real_escape_string($_POST['full_name']));
    $phone = trim($conn->real_escape_string($_POST['phone']));
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if full name and phone are provided
    if (empty($full_name) || empty($phone)) {
        $error = "Full name and phone number are required.";
    } else {
        // Check if current password is correct
        if (!empty($current_password)) {
            if (password_verify($current_password, $student['password'])) {
                // Check if new password fields match
                if (!empty($new_password) && $new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE students SET full_name = ?, phone = ?, password = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssi", $full_name, $phone, $hashed_password, $student_id);
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        } else {
            // If no current password is provided, only update name and phone
            $update_sql = "UPDATE students SET full_name = ?, phone = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $full_name, $phone, $student_id);
        }
    }

    // Execute the update if there are no errors
    if (empty($error)) {
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            // Refresh student data
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">My Profile</h1>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-center">
                                        <div class="stat-icon" style="width: 100px; height: 100px; font-size: 40px; background-color: rgba(42, 33, 133, 0.15); color: var(--primary-color);">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    </div>
                                </div>
                                <h3 class="mb-0"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                <p class="text-muted"><?php echo htmlspecialchars($student['program_name']); ?></p>
                                <div class="badge badge-active mb-3">
                                    <?php echo htmlspecialchars($student['year'] . ' - ' . $student['section']); ?>
                                </div>
                                
                                <hr class="my-3">
                                
                                <div class="text-start">
                                    <div class="mb-2">
                                        <strong><i class="bi bi-card-list me-2"></i>Student ID:</strong>
                                        <span class="float-end"><?php echo htmlspecialchars($student['school_id']); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="bi bi-telephone me-2"></i>Phone:</strong>
                                        <span class="float-end"><?php echo htmlspecialchars($student['phone']); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="bi bi-person-badge me-2"></i>Username:</strong>
                                        <span class="float-end"><?php echo htmlspecialchars($student['username']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Form -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Edit Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="11" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                                    </div>
                                    
                                    <hr class="my-4">
                                    <h5>Change Password</h5>
                                    <p class="text-muted small">Leave these fields empty if you don't want to change your password.</p>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        <div id="password-feedback" class="form-text"></div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-2"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const feedbackElement = document.getElementById('password-feedback');
        
        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                feedbackElement.textContent = '';
                feedbackElement.className = 'form-text';
            } else if (newPassword.value === confirmPassword.value) {
                feedbackElement.textContent = 'Passwords match!';
                feedbackElement.className = 'form-text text-success';
            } else {
                feedbackElement.textContent = 'Passwords do not match!';
                feedbackElement.className = 'form-text text-danger';
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        // Auto close alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const closeButton = new bootstrap.Alert(alert);
                closeButton.close();
            });
        }, 5000);
    </script>
</body>
</html>