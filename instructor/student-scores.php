<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get filter parameters
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : null;
$year_section_id = isset($_GET['year_section_id']) ? intval($_GET['year_section_id']) : null;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch programs
$programs_sql = "SELECT * FROM programs ORDER BY program_name ASC";
$programs_result = $conn->query($programs_sql);

// Fetch year sections
$year_sections_sql = "SELECT * FROM year_sections ORDER BY year ASC, section ASC";
$year_sections_result = $conn->query($year_sections_sql);

// Fetch exams
$exams_sql = "SELECT * FROM exams ORDER BY created_at DESC";
$exams_result = $conn->query($exams_sql);

// Build the base query for student scores
$scores_sql = "
    SELECT 
        s.id as student_id,
        s.full_name as student_name,
        s.school_id,
        p.program_name,
        CONCAT(ys.year, '-', ys.section) as year_section,
        e.exam_name,
        e.item as total_questions,
        COUNT(DISTINCT r.id) as answered_questions,
        SUM(CASE WHEN r.response_text = q.correct_answer THEN 1 ELSE 0 END) as correct_answers,
        es.completed,
        e.created_at as exam_date
    FROM students s
    LEFT JOIN programs p ON s.program_id = p.id
    LEFT JOIN year_sections ys ON s.year_section_id = ys.id
    LEFT JOIN exam_students es ON s.id = es.student_id
    LEFT JOIN exams e ON es.exam_id = e.id
    LEFT JOIN questions q ON e.id = q.exam_id
    LEFT JOIN responses r ON q.id = r.question_id AND r.student_id = s.id
    WHERE 1=1
";

// Add filters
$params = [];
$types = "";

if ($program_id) {
    $scores_sql .= " AND s.program_id = ?";
    $params[] = $program_id;
    $types .= "i";
}

if ($year_section_id) {
    $scores_sql .= " AND s.year_section_id = ?";
    $params[] = $year_section_id;
    $types .= "i";
}

if ($exam_id) {
    $scores_sql .= " AND e.id = ?";
    $params[] = $exam_id;
    $types .= "i";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $scores_sql .= " AND (s.full_name LIKE ? OR s.school_id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Group by and order
$scores_sql .= " GROUP BY s.id, e.id ORDER BY s.full_name ASC, e.created_at DESC";

// Execute query
$stmt = $conn->prepare($scores_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$scores_result = $stmt->get_result();

// Calculate statistics
$total_students = 0;
$total_exams = 0;
$total_score = 0;
$total_attempts = 0;
$passing_count = 0;

$student_scores = [];
while ($score = $scores_result->fetch_assoc()) {
    if (!isset($student_scores[$score['student_id']])) {
        $total_students++;
        $student_scores[$score['student_id']] = [
            'exams' => 0,
            'total_score' => 0
        ];
    }
    
    if ($score['total_questions'] > 0) {
        $total_exams++;
        $student_scores[$score['student_id']]['exams']++;
        
        $score_percentage = ($score['correct_answers'] / $score['total_questions']) * 100;
        $student_scores[$score['student_id']]['total_score'] += $score_percentage;
        $total_score += $score_percentage;
        $total_attempts++;
        
        if ($score_percentage >= 75) {
            $passing_count++;
        }
    }
}

// Calculate averages
$overall_average = $total_attempts > 0 ? $total_score / $total_attempts : 0;
$passing_rate = $total_attempts > 0 ? ($passing_count / $total_attempts) * 100 : 0;

// Reset result pointer for display
$stmt->execute();
$scores_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Scores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <link rel="stylesheet" href="../assets/css/student-scores-styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">Student Scores</h1>
                
                <!-- Statistics Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <i class="bi bi-people-fill text-primary mb-3" style="font-size: 2rem;"></i>
                            <div class="value"><?php echo number_format($total_students); ?></div>
                            <div class="label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <i class="bi bi-journal-text text-success mb-3" style="font-size: 2rem;"></i>
                            <div class="value"><?php echo number_format($total_exams); ?></div>
                            <div class="label">Total Exam Attempts</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <i class="bi bi-graph-up text-warning mb-3" style="font-size: 2rem;"></i>
                            <div class="value"><?php echo number_format($overall_average, 1); ?>%</div>
                            <div class="label">Average Score</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <i class="bi bi-award text-danger mb-3" style="font-size: 2rem;"></i>
                            <div class="value"><?php echo number_format($passing_rate, 1); ?>%</div>
                            <div class="label">Passing Rate</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="program_id" class="form-label">Program</label>
                                <select class="form-select" id="program_id" name="program_id">
                                    <option value="">All Programs</option>
                                    <?php while ($program = $programs_result->fetch_assoc()): ?>
                                        <option value="<?php echo $program['id']; ?>" <?php echo ($program_id == $program['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="year_section_id" class="form-label">Year/Section</label>
                                <select class="form-select" id="year_section_id" name="year_section_id">
                                    <option value="">All Year/Sections</option>
                                    <?php while ($year_section = $year_sections_result->fetch_assoc()): ?>
                                        <option value="<?php echo $year_section['id']; ?>" <?php echo ($year_section_id == $year_section['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year_section['year'] . '-' . $year_section['section']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="exam_id" class="form-label">Exam</label>
                                <select class="form-select" id="exam_id" name="exam_id">
                                    <option value="">All Exams</option>
                                    <?php while ($exam = $exams_result->fetch_assoc()): ?>
                                        <option value="<?php echo $exam['id']; ?>" <?php echo ($exam_id == $exam['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($exam['exam_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                    placeholder="Name or ID" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter me-2"></i> Apply Filters
                                </button>
                                <a href="student-scores.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i> Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Scores Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Year-Section</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($scores_result->num_rows > 0): ?>
                                        <?php while ($score = $scores_result->fetch_assoc()): 
                                            $score_percentage = 0;
                                            $grade = 'N/A';
                                            $grade_class = '';
                                            
                                            if ($score['total_questions'] > 0) {
                                                $score_percentage = ($score['correct_answers'] / $score['total_questions']) * 100;
                                                
                                                if ($score_percentage >= 90) {
                                                    $grade = 'A';
                                                    $grade_class = 'score-badge-a';
                                                } elseif ($score_percentage >= 80) {
                                                    $grade = 'B';
                                                    $grade_class = 'score-badge-b';
                                                } elseif ($score_percentage >= 70) {
                                                    $grade = 'C';
                                                    $grade_class = 'score-badge-c';
                                                } elseif ($score_percentage >= 60) {
                                                    $grade = 'D';
                                                    $grade_class = 'score-badge-d';
                                                } else {
                                                    $grade = 'F';
                                                    $grade_class = 'score-badge-f';
                                                }
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($score['school_id']); ?></td>
                                                <td><?php echo htmlspecialchars($score['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($score['program_name']); ?></td>
                                                <td><?php echo htmlspecialchars($score['year_section']); ?></td>
                                                <td>
                                                    <?php if ($score['exam_name']): ?>
                                                        <?php echo htmlspecialchars($score['exam_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No exam taken</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($score['total_questions'] > 0): ?>
                                                        <?php echo $score['correct_answers']; ?>/<?php echo $score['total_questions']; ?>
                                                        (<?php echo number_format($score_percentage, 1); ?>%)
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($score['total_questions'] > 0): ?>
                                                        <span class="score-badge <?php echo $grade_class; ?>">
                                                            <?php echo $grade; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($score['completed']): ?>
                                                        <span class="badge badge-success">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <p class="text-muted mb-0">No scores found</p>
                                            </td>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                document.querySelector('form').submit();
            });
        });
    </script>
</body>
</html>