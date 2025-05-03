<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get student count
$student_count_sql = "SELECT COUNT(*) as count FROM students";
$student_count_result = $conn->query($student_count_sql);
$student_count = $student_count_result->fetch_assoc()['count'];

// Get exam count
$exam_count_sql = "SELECT COUNT(*) as count FROM exams";
$exam_count_result = $conn->query($exam_count_sql);
$exam_count = $exam_count_result->fetch_assoc()['count'];

// Get program count
$program_count_sql = "SELECT COUNT(*) as count FROM programs";
$program_count_result = $conn->query($program_count_sql);
$program_count = $program_count_result->fetch_assoc()['count'];

// Get recent exams
$recent_exams_sql = "SELECT e.*, p.program_name, ys.year, ys.section 
                     FROM exams e
                     LEFT JOIN programs p ON e.program_id = p.id
                     LEFT JOIN year_sections ys ON e.year_section_id = ys.id
                     ORDER BY e.created_at DESC LIMIT 5";
$recent_exams_result = $conn->query($recent_exams_sql);

// Get recent students
$recent_students_sql = "SELECT s.*, p.program_name, ys.year, ys.section 
                        FROM students s 
                        LEFT JOIN programs p ON s.program_id = p.id 
                        LEFT JOIN year_sections ys ON s.year_section_id = ys.id
                        ORDER BY s.id DESC LIMIT 5";
$recent_students_result = $conn->query($recent_students_sql);
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>
        
            <div class="content-wrapper">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h1 class="mb-3">Welcome, Instructor</h1>
                            <p class="text-muted">Here's an overview of your examination system</p>
                        </div>
                    </div>
                    
                    <!-- Stats Section -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="stat-card stat-students">
                                    <div class="stat-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $student_count; ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="stat-card stat-exams">
                                    <div class="stat-icon">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $exam_count; ?></h3>
                                        <p>Total Exams</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="stat-card stat-programs">
                                    <div class="stat-icon">
                                        <i class="bi bi-journal-bookmark"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?php echo $program_count; ?></h3>
                                        <p>Programs</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <a href="create-exams.php" class="btn btn-primary w-100">
                                                <i class="bi bi-plus-circle-fill btn-icon"></i> Create Exam
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="add-student.php" class="btn btn-primary w-100">
                                                <i class="bi bi-person-plus-fill btn-icon"></i> Add Student
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="view-student.php" class="btn btn-primary w-100">
                                                <i class="bi bi-people-fill btn-icon"></i> View Students
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="manage-exams.php" class="btn btn-primary w-100">
                                                <i class="bi bi-journal-text btn-icon"></i> Manage Exams
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Data -->
                    <div class="row">
                        <!-- Recent Exams -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Recent Exams</span>
                                    <a href="manage-exams.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Program</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_exams_result->num_rows > 0): ?>
                                                <?php while ($exam = $recent_exams_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($exam['program_name']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $exam['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                                <?php echo ucfirst($exam['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No exams found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Students -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Recent Students</span>
                                    <a href="view-student.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Program</th>
                                                <th>Year-Section</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_students_result->num_rows > 0): ?>
                                                <?php while ($student = $recent_students_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['year'] . '-' . $student['section']); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No students found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>