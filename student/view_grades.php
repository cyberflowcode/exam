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

$student_id = $_SESSION['student_id'];

// Get the student's information
$student_sql = "
    SELECT s.*, p.program_name, ys.year, ys.section
    FROM students s
    LEFT JOIN programs p ON s.program_id = p.id
    LEFT JOIN year_sections ys ON s.year_section_id = ys.id
    WHERE s.id = ?
";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

// Get the student's grades for all exams
$grades_sql = "
    SELECT e.id, e.exam_name, e.created_at, e.item as total_questions,
           COUNT(DISTINCT r.question_id) as answered_questions,
           SUM(CASE WHEN r.response_text = q.correct_answer THEN 1 ELSE 0 END) as correct_answers
    FROM exams e
    JOIN exam_students es ON e.id = es.exam_id
    LEFT JOIN questions q ON e.id = q.exam_id
    LEFT JOIN responses r ON q.id = r.question_id AND r.student_id = ?
    WHERE es.student_id = ?
    GROUP BY e.id
    ORDER BY e.created_at DESC
";
$grades_stmt = $conn->prepare($grades_sql);
$grades_stmt->bind_param("ii", $student_id, $student_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();

// Calculate overall statistics
$total_exams = 0;
$total_score = 0;
$overall_percentage = 0;
$exams_passed = 0;
$exams_failed = 0;

// Create a copy of the result to process for statistics
$grades_for_stats = [];
while ($grade = $grades_result->fetch_assoc()) {
    $grades_for_stats[] = $grade;
    $total_exams++;
    
    $score_percentage = 0;
    if ($grade['total_questions'] > 0 && $grade['answered_questions'] > 0) {
        $score_percentage = ($grade['correct_answers'] / $grade['total_questions']) * 100;
        $total_score += $score_percentage;
        
        if ($score_percentage >= 75) {
            $exams_passed++;
        } else {
            $exams_failed++;
        }
    } else {
        $exams_failed++;
    }
}

// Calculate overall average
if ($total_exams > 0) {
    $overall_percentage = $total_score / $total_exams;
}

// Reset result pointer for display
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        .grade-card {
            text-align: center;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }
        
        .grade-card:hover {
            transform: translateY(-5px);
        }
        
        .grade-card .grade {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .grade-card .subject {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .grade-card .score {
            font-size: 1.1rem;
            color: var(--text-light);
        }
        
        .grade-card.grade-a {
            background-color: rgba(43, 218, 96, 0.1);
            border-left: 5px solid var(--success-color);
        }
        
        .grade-card.grade-b {
            background-color: rgba(52, 211, 153, 0.1);
            border-left: 5px solid #34D399;
        }
        
        .grade-card.grade-c {
            background-color: rgba(255, 183, 43, 0.1);
            border-left: 5px solid var(--warning-color);
        }
        
        .grade-card.grade-d {
            background-color: rgba(251, 146, 60, 0.1);
            border-left: 5px solid #FB923C;
        }
        
        .grade-card.grade-f {
            background-color: rgba(255, 77, 109, 0.1);
            border-left: 5px solid var(--danger-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">My Grades</h1>
                
                <!-- Student Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?= htmlspecialchars($student['full_name']); ?></h5>
                                <p class="mb-1">
                                    <strong>ID:</strong> <?= htmlspecialchars($student['school_id']); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Program:</strong> <?= htmlspecialchars($student['program_name']); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1">
                                    <strong>Class:</strong> <?= htmlspecialchars($student['year'] . '-' . $student['section']); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Overall Grade:</strong> 
                                    <span class="badge <?= $overall_percentage >= 60 ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?= number_format($overall_percentage, 1); ?>%
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Summary -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(42, 33, 133, 0.15); color: var(--primary-color);">
                                    <i class="bi bi-journals"></i>
                                </div>
                                <div class="stat-value"><?= $total_exams; ?></div>
                                <div class="text-muted">Total Exams</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(43, 218, 96, 0.15); color: var(--success-color);">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stat-value"><?= $exams_passed; ?></div>
                                <div class="text-muted">Exams Passed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(255, 77, 109, 0.15); color: var(--danger-color);">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="stat-value"><?= $exams_failed; ?></div>
                                <div class="text-muted">Exams Failed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="stat-icon mx-auto mb-3" style="background-color: rgba(255, 183, 43, 0.15); color: var(--warning-color);">
                                    <i class="bi bi-percent"></i>
                                </div>
                                <div class="stat-value"><?= number_format($overall_percentage, 1); ?>%</div>
                                <div class="text-muted">Average Score</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Grades -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detailed Grade Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($grades_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($grade = $grades_result->fetch_assoc()):
                                    $score = 0;
                                    $letter_grade = 'F';
                                    $grade_class = 'grade-f';
                                    
                                    if ($grade['total_questions'] > 0 && $grade['answered_questions'] > 0) {
                                        $score = ($grade['correct_answers'] / $grade['total_questions']) * 100;
                                        
                                        if ($score >= 90) {
                                            $letter_grade = 'A';
                                            $grade_class = 'grade-a';
                                        } else if ($score >= 80) {
                                            $letter_grade = 'B';
                                            $grade_class = 'grade-b';
                                        } else if ($score >= 70) {
                                            $letter_grade = 'C';
                                            $grade_class = 'grade-c';
                                        } else if ($score >= 60) {
                                            $letter_grade = 'D';
                                            $grade_class = 'grade-d';
                                        }
                                    }
                                ?>
                                    <div class="col-md-4 col-sm-6 mb-4">
                                        <div class="grade-card <?= $grade_class; ?>">
                                            <div class="grade"><?= $letter_grade; ?></div>
                                            <div class="subject"><?= htmlspecialchars($grade['exam_name']); ?></div>
                                            <div class="score">
                                                <?= $grade['correct_answers']; ?>/<?= $grade['total_questions']; ?> 
                                                (<?= number_format($score, 1); ?>%)
                                            </div>
                                            <div class="date small text-muted mt-2">
                                                <?= date("F j, Y", strtotime($grade['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">No grade records found.</p>
                                <p class="text-muted small">
                                    You haven't completed any exams yet or your instructor hasn't assigned you to any exams.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>