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

// Get available exams for this student based on program and year_section
$available_exams_sql = "
    SELECT e.*, p.program_name, ys.year, ys.section,
           es.id as enrollment_id, es.completed,
           (SELECT COUNT(*) FROM responses r JOIN questions q ON r.question_id = q.id 
            WHERE q.exam_id = e.id AND r.student_id = ?) as response_count
    FROM exams e
    JOIN exam_students es ON e.id = es.exam_id AND es.student_id = ?
    LEFT JOIN programs p ON e.program_id = p.id
    LEFT JOIN year_sections ys ON e.year_section_id = ys.id
    WHERE e.status = 'active' 
      AND e.program_id = ? 
      AND e.year_section_id = ?
      AND (es.completed = 0)
      AND NOT EXISTS (
          SELECT 1 FROM responses r 
          JOIN questions q ON r.question_id = q.id 
          WHERE q.exam_id = e.id AND r.student_id = ?
      )
    ORDER BY e.created_at DESC
";
$available_exams_stmt = $conn->prepare($available_exams_sql);
$available_exams_stmt->bind_param("iiiii", $student_id, $student_id, $student['program_id'], $student['year_section_id'], $student_id);
$available_exams_stmt->execute();
$available_exams_result = $available_exams_stmt->get_result();

// Get recently completed exams
$completed_exams_sql = "
    SELECT e.*, p.program_name, ys.year, ys.section,
           es.completed, es.created_at as enrollment_date
    FROM exam_students es
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN programs p ON e.program_id = p.id
    LEFT JOIN year_sections ys ON e.year_section_id = ys.id
    WHERE es.student_id = ? AND (
        es.completed = 1 OR
        EXISTS (
            SELECT 1 FROM responses r 
            JOIN questions q ON r.question_id = q.id 
            WHERE q.exam_id = e.id AND r.student_id = ?
        )
    )
    ORDER BY es.completed DESC, e.created_at DESC
    LIMIT 3
";
$completed_exams_stmt = $conn->prepare($completed_exams_sql);
$completed_exams_stmt->bind_param("ii", $student_id, $student_id);
$completed_exams_stmt->execute();
$completed_exams_result = $completed_exams_stmt->get_result();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <link rel="stylesheet" href="assets/css/dashboard-design-card.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
            <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="error-alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
                
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="dashboard-welcome">
                        <h2>Welcome, <?= htmlspecialchars($student['full_name']); ?></h2>
                        <p>Access your exams and track your academic progress</p>
                    </div>
                    <div class="student-info">
                        <div class="student-info-item">
                            <i class="bi bi-person-badge"></i>
                            <span><?= htmlspecialchars($student['school_id']); ?></span>
                        </div>
                        <div class="student-info-item">
                            <i class="bi bi-book"></i>
                            <span><?= htmlspecialchars($student['program_name']); ?></span>
                        </div>
                        <div class="student-info-item">
                            <i class="bi bi-people"></i>
                            <span>Year <?= htmlspecialchars($student['year']); ?> - Section <?= htmlspecialchars($student['section']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Available Exams Section -->
                <div class="section-header">
                    <h5 class="section-title">Available Exams</h5>
                    <a href="exam_history.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i> View All History
                    </a>
                </div>
                
                <div class="row">
                    <?php if ($available_exams_result->num_rows > 0): ?>
                        <?php while ($exam = $available_exams_result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="exam-card">
                                    <div class="exam-card-header">
                                        <h3><?= htmlspecialchars($exam['exam_name']); ?></h3>
                                        <span class="badge badge-active pulse">Active</span>
                                    </div>
                                    <div class="exam-card-body">
                                        <div class="exam-meta">
                                            <div class="exam-meta-item">
                                                <i class="bi bi-list-ol"></i>
                                                <span><?= htmlspecialchars($exam['item'] ?? 0); ?> Items</span>
                                            </div>
                                            <div class="exam-meta-item">
                                                <i class="bi bi-clock"></i>
                                                <span><?= htmlspecialchars($exam['duration']); ?> min</span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <a href="take-exam.php?id=<?= $exam['id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil-square me-2"></i> Take Exam
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <h4>No Active Exams</h4>
                                    <p>You don't have any active exams to take at the moment. Check back later or contact your instructor.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity Section -->
                <div class="section-header mt-5">
                    <h5 class="section-title">Recent Activity</h5>
                </div>
                
                <div class="row">
                    <?php if ($completed_exams_result->num_rows > 0): ?>
                        <?php while ($exam = $completed_exams_result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="exam-card">
                                    <div class="exam-card-header">
                                        <h3><?= htmlspecialchars($exam['exam_name']); ?></h3>
                                        <span class="badge badge-inactive">Completed</span>
                                    </div>
                                    <div class="exam-card-body">
                                        <div class="exam-meta">
                                            <div class="exam-meta-item">
                                                <i class="bi bi-building"></i>
                                                <span><?= htmlspecialchars($exam['program_name']); ?></span>
                                            </div>
                                            <div class="exam-meta-item">
                                                <i class="bi bi-people"></i>
                                                <span><?= htmlspecialchars($exam['year'] . '-' . $exam['section']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <small class="text-muted d-block mb-3">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            Completed on: <?= date("F j, Y", strtotime($exam['enrollment_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body empty-state">
                                    <i class="bi bi-activity"></i>
                                    <h4>No Recent Activity</h4>
                                    <p>You haven't completed any exams yet. Your recent exam activity will appear here.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatically close alerts after 5 seconds
            setTimeout(function() {
                const successAlert = document.getElementById('success-alert');
                const errorAlert = document.getElementById('error-alert');

                if (successAlert) {
                    const closeButton = new bootstrap.Alert(successAlert);
                    closeButton.close();
                }
                if (errorAlert) {
                    const closeButton = new bootstrap.Alert(errorAlert);
                    closeButton.close();
                }
                
            }, 5000);
        });
    </script>
</body>
</html>