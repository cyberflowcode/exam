<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get teacher ID from session
$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher data
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    header('Location: ../index.php');
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if current password is correct
    if (!empty($current_password)) {
        if (password_verify($current_password, $teacher['password'])) {
            // Check if new password fields match
            if (!empty($new_password) && $new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE teachers SET full_name = ?, email = ?, password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssi", $full_name, $email, $hashed_password, $teacher_id);
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        // Only update name and email if no password change
        $update_sql = "UPDATE teachers SET full_name = ?, email = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $full_name, $email, $teacher_id);
    }

    // Execute the update if there are no errors
    if (empty($error)) {
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            // Refresh teacher data
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher = $result->fetch_assoc();
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
    <title>Instructor Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        .profile-header {
            background-color: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }
        
        .profile-icon {
            width: 120px;
            height: 120px;
            background-color: rgba(42, 33, 133, 0.15);
            color: var(--primary-color);
            font-size: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .profile-details {
            margin-bottom: 20px;
        }
        
        .profile-details p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .profile-details i {
            width: 24px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .form-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
        }
        
        @media (max-width: 768px) {
            .profile-icon {
                width: 100px;
                height: 100px;
                font-size: 40px;
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
                    <!-- Profile Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="profile-icon mx-auto">
                                    <i class="bi bi-person"></i>
                                </div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($teacher['full_name']); ?></h3>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($teacher['role']); ?></p>
                                
                                <div class="profile-details text-start mt-4">
                                    <p>
                                        <i class="bi bi-person-badge"></i>
                                        <span>Username: <?php echo htmlspecialchars($teacher['username']); ?></span>
                                    </p>
                                    <p>
                                        <i class="bi bi-envelope"></i>
                                        <span>Email: <?php echo htmlspecialchars($teacher['email']); ?></span>
                                    </p>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <a href="../backend/logout.php" class="btn btn-outline-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
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
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($teacher['full_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    <h5>Change Password</h5>
                                    <p class="text-muted small">Leave these fields empty if you don't want to change your password.</p>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
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