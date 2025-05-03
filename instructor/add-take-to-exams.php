<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$exam_id = $_GET['id'] ?? null;

if (!$exam_id) {
    die("Exam ID is required.");
}

// Get exam details
$exam_sql = "
    SELECT e.*, p.program_name, ys.year, ys.section 
    FROM exams e
    LEFT JOIN programs p ON e.program_id = p.id
    LEFT JOIN year_sections ys ON e.year_section_id = ys.id
    WHERE e.id = ?
";
$exam_stmt = $conn->prepare($exam_sql);
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

if (!$exam) {
    header('Location: dashboard.php');
}

// Get eligible students based on program and year-section
$students_sql = "
    SELECT s.id, s.full_name, s.school_id
    FROM students s 
    WHERE s.program_id = ? AND s.year_section_id = ?
    ORDER BY s.full_name ASC
";
$stmt = $conn->prepare($students_sql);
$stmt->bind_param("ii", $exam['program_id'], $exam['year_section_id']);
$stmt->execute();
$students_result = $stmt->get_result();

// Get already enrolled students
$enrolled_sql = "
    SELECT es.student_id, es.completed,
           (SELECT COUNT(*) FROM responses r 
            JOIN questions q ON r.question_id = q.id 
            WHERE q.exam_id = ? AND r.student_id = es.student_id) as response_count,
           (SELECT COUNT(*) FROM responses r 
            JOIN questions q ON r.question_id = q.id 
            WHERE q.exam_id = ? AND r.student_id = es.student_id AND r.response_text = q.correct_answer) as correct_answers,
           (SELECT COUNT(*) FROM questions WHERE exam_id = ?) as total_questions
    FROM exam_students es
    WHERE es.exam_id = ?
";
$enrolled_stmt = $conn->prepare($enrolled_sql);
$enrolled_stmt->bind_param("iiii", $exam_id, $exam_id, $exam_id, $exam_id);
$enrolled_stmt->execute();
$enrolled_result = $enrolled_stmt->get_result();

$enrolled_students = [];
$student_scores = [];
while ($enrolled = $enrolled_result->fetch_assoc()) {
    $enrolled_students[] = $enrolled['student_id'];
    $student_scores[$enrolled['student_id']] = [
        'completed' => $enrolled['completed'],
        'response_count' => $enrolled['response_count'],
        'correct_answers' => $enrolled['correct_answers'],
        'total_questions' => $enrolled['total_questions']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_students = $_POST['students'] ?? [];

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Remove students who were unselected
        foreach ($enrolled_students as $student_id) {
            if (!in_array($student_id, $selected_students)) {
                $delete_sql = "DELETE FROM exam_students WHERE exam_id = ? AND student_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $exam_id, $student_id);
                $delete_stmt->execute();
            }
        }

        // Add newly selected students
        foreach ($selected_students as $student_id) {
            if (!in_array($student_id, $enrolled_students)) {
                $insert_sql = "INSERT INTO exam_students (exam_id, student_id, completed) VALUES (?, ?, 0)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ii", $exam_id, $student_id);
                $insert_stmt->execute();
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Students updated successfully"; // Combined message for clarity
        header("Location: manage-exams.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error updating students: " . $e->getMessage(); // Store error message
        header("Location: manage-exams.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students for Exam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        .exam-info {
            background-color: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .exam-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .exam-detail {
            flex: 1;
            min-width: 200px;
        }
        
        .exam-detail-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 4px;
        }
        
        .exam-detail-value {
            font-size: 16px;
            font-weight: 500;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .student-list {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .student-filter {
            position: relative;
            margin-bottom: 20px;
        }
        
        .student-filter input {
            padding-left: 38px;
            border-radius: var(--border-radius);
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 10px 15px 10px 38px;
        }
        
        .student-filter i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .score-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 14px;
            display: inline-block;
        }
        
        .score-badge-a {
            background-color: rgba(43, 218, 96, 0.15);
            color: var(--success-color);
        }
        
        .score-badge-b {
            background-color: rgba(52, 211, 153, 0.15);
            color: #34D399;
        }
        
        .score-badge-c {
            background-color: rgba(255, 183, 43, 0.15);
            color: var(--warning-color);
        }
        
        .score-badge-d {
            background-color: rgba(251, 146, 60, 0.15);
            color: #FB923C;
        }
        
        .score-badge-f {
            background-color: rgba(255, 77, 109, 0.15);
            color: var(--danger-color);
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
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
                    <h1 class="mb-0">Manage Students for Exam</h1>
                    <a href="manage-exams.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Exams
                    </a>
                </div>
                
                <!-- Exam information card -->
                <div class="exam-info">
                    <h4 class="mb-3"><?= htmlspecialchars($exam['exam_name']); ?></h4>
                    <div class="exam-details">
                        <div class="exam-detail">
                            <div class="exam-detail-label">Program</div>
                            <div class="exam-detail-value"><?= htmlspecialchars($exam['program_name']); ?></div>
                        </div>
                        <div class="exam-detail">
                            <div class="exam-detail-label">Year-Section</div>
                            <div class="exam-detail-value"><?= htmlspecialchars($exam['year'] . '-' . $exam['section']); ?></div>
                        </div>
                        <div class="exam-detail">
                            <div class="exam-detail-label">Total Items</div>
                            <div class="exam-detail-value"><?= htmlspecialchars($exam['item'] ?? 0); ?></div>
                        </div>
                        <div class="exam-detail">
                            <div class="exam-detail-label">Duration</div>
                            <div class="exam-detail-value"><?= htmlspecialchars($exam['duration']); ?> minutes</div>
                        </div>
                        <div class="exam-detail">
                            <div class="exam-detail-label">Status</div>
                            <div class="exam-detail-value">
                                <span class="badge <?= $exam['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?= ucfirst(htmlspecialchars($exam['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Select Students</h5>
                            <div class="student-filter" style="width: 300px;">
                                <i class="bi bi-search"></i>
                                <input type="text" id="studentSearch" class="form-control" placeholder="Search students...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <form method="POST" action="">
                            <div class="student-list">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Student ID</th>
                                            <th>Full Name</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($students_result->num_rows > 0): ?>
                                            <?php while ($student = $students_result->fetch_assoc()): 
                                                $is_enrolled = in_array($student['id'], $enrolled_students);
                                                $score_data = $student_scores[$student['id']] ?? null;
                                                $has_taken = $score_data && ($score_data['completed'] || $score_data['response_count'] > 0);
                                                
                                                $score_percentage = 0;
                                                $grade_letter = "";
                                                $grade_class = "";
                                                
                                                if ($has_taken && $score_data['total_questions'] > 0) {
                                                    $score_percentage = ($score_data['correct_answers'] / $score_data['total_questions']) * 100;
                                                    
                                                    if ($score_percentage >= 90) {
                                                        $grade_letter = "A";
                                                        $grade_class = "score-badge-a";
                                                    } elseif ($score_percentage >= 80) {
                                                        $grade_letter = "B";
                                                        $grade_class = "score-badge-b";
                                                    } elseif ($score_percentage >= 70) {
                                                        $grade_letter = "C";
                                                        $grade_class = "score-badge-c";
                                                    } elseif ($score_percentage >= 60) {
                                                        $grade_letter = "D";
                                                        $grade_class = "score-badge-d";
                                                    } else {
                                                        $grade_letter = "F";
                                                        $grade_class = "score-badge-f";
                                                    }
                                                }
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input student-checkbox" type="checkbox" 
                                                                   name="students[]" value="<?= $student['id']; ?>"
                                                                   <?= $is_enrolled ? 'checked' : ''; ?>>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($student['school_id']); ?></td>
                                                    <td><?= htmlspecialchars($student['full_name']); ?></td>
                                                    <td>
                                                        <?php if ($has_taken): ?>
                                                            <span class="badge badge-active">Completed</span>
                                                        <?php elseif ($is_enrolled): ?>
                                                            <span class="badge badge-warning">Enrolled</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-inactive">Not Enrolled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($has_taken): ?>
                                                            <span class="score-badge <?= $grade_class; ?>">
                                                                <?= $score_data['correct_answers']; ?>/<?= $score_data['total_questions']; ?> 
                                                                (<?= round($score_percentage); ?>%) - <?= $grade_letter; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not taken</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <p class="mb-0 text-muted">No students found for this program and year-section.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="action-buttons">
                                <div>
                                    <span class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Students who have completed the exam will still remain enrolled.
                                    </span>
                                </div>
                                <div>
                                    <a href="manage-exams.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle "Select All" checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                studentCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
            
            // Student search filter
            const searchInput = document.getElementById('studentSearch');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const studentName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const studentId = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    
                    if (studentName.includes(searchTerm) || studentId.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Check if all checkboxes are checked initially
            function updateSelectAllStatus() {
                const allChecked = Array.from(studentCheckboxes).every(checkbox => checkbox.checked);
                selectAllCheckbox.checked = allChecked && studentCheckboxes.length > 0;
            }
            
            // Listen for individual checkbox changes
            studentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllStatus);
            });
            
            // Initialize select all status
            updateSelectAllStatus();
        });
    </script>
</body>
</html>