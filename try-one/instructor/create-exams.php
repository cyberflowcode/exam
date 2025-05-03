<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    echo "Error: You don't have permission to create exams.";
    exit();
}

$programs = $conn->query("SELECT * FROM programs ORDER BY program_name ASC");
$year_sections = $conn->query("SELECT * FROM year_sections ORDER BY year ASC, section ASC");

if (!$programs || !$year_sections) {
    echo "Error fetching data: " . $conn->error;
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['teacher_id'])) {
        echo "Error: Teacher ID not found in session.";
        exit();
    }

    $exam_name = $conn->real_escape_string($_POST['exam_name']);
    $teacher_id = $_SESSION['teacher_id'];
    $duration = $conn->real_escape_string($_POST['duration']);
    $program_id = $conn->real_escape_string($_POST['program_id']);
    $year_section_id = $conn->real_escape_string($_POST['year_section_id']);

    $sql = "INSERT INTO exams (exam_name, teacher_id, duration, program_id, year_section_id) VALUES ('$exam_name', '$teacher_id', '$duration', '$program_id', '$year_section_id')";
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Examination added successfully";
        header('Location: manage-exams.php');
        exit();
    } else {
        $_SESSION['error'] = "Failed to add examination";
        header('Location: manage-exams.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Create New Exam</h1>
                    <a href="manage-exams.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Exams
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="post" id="createExamForm">
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="exam_name" class="form-label">Subject Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-journal-text"></i>
                                            </span>
                                            <input type="text" 
                                                   id="exam_name" 
                                                   name="exam_name" 
                                                   class="form-control" 
                                                   placeholder="Enter subject name"
                                                   required>
                                        </div>
                                        <small class="text-muted">Example: Mathematics, English, Science</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="program_id" class="form-label">Program</label>
                                        <select id="program_id" 
                                                name="program_id" 
                                                class="form-select" 
                                                required>
                                            <option value="">Select Program</option>
                                            <?php while ($row = $programs->fetch_assoc()) { ?>
                                                <option value="<?php echo $row['id']; ?>">
                                                    <?php echo htmlspecialchars($row['program_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="year_section_id" class="form-label">Year and Section</label>
                                        <select id="year_section_id" 
                                                name="year_section_id" 
                                                class="form-select" 
                                                required>
                                            <option value="">Select Year & Section</option>
                                            <?php while ($row = $year_sections->fetch_assoc()) { ?>
                                                <option value="<?php echo $row['id']; ?>">
                                                    <?php echo htmlspecialchars($row['year'] . '-' . $row['section']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="duration" class="form-label">Duration (minutes)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-clock"></i>
                                            </span>
                                            <input type="number" 
                                                   id="duration" 
                                                   name="duration" 
                                                   class="form-control" 
                                                   min="1" 
                                                   max="180" 
                                                   placeholder="Enter duration"
                                                   required>
                                            <span class="input-group-text">minutes</span>
                                        </div>
                                        <small class="text-muted">Maximum duration: 180 minutes</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                After creating the exam, you'll be able to add questions and manage student access.
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle-fill me-2"></i> Create Exam
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
        document.getElementById('createExamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const duration = document.getElementById('duration').value;
            if (duration > 180) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Duration',
                    text: 'Maximum exam duration is 180 minutes.'
                });
                return;
            }
            
            Swal.fire({
                title: 'Create Exam?',
                text: "You'll be redirected to add questions after creating the exam.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2a2185',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, create it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // Validate duration input
        document.getElementById('duration').addEventListener('input', function() {
            if (this.value > 180) {
                this.value = 180;
            } else if (this.value < 1) {
                this.value = 1;
            }
        });
    </script>
</body>
</html>