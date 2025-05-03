<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$history_result = require '../backend/get_exam_history.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <link rel="stylesheet" href="assets/css/exam-history-styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../sidebar/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">My Exam History</h1>
                
                <?php if ($history_result && $history_result->num_rows > 0): ?>
                    <?php while ($history = $history_result->fetch_assoc()): ?>
                        <div class="history-card">
                            <div class="card-header">
                                <h5 class="mb-0"><?= htmlspecialchars($history['exam_name']); ?></h5>
                                <div>
                                    <?php if ($history['completed']): ?>
                                        <span class="badge badge-active">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="exam-meta">
                                    <div class="exam-meta-item">
                                        <i class="bi bi-building"></i>
                                        <span><?= htmlspecialchars($history['program_name']); ?></span>
                                    </div>
                                    <div class="exam-meta-item">
                                        <i class="bi bi-people"></i>
                                        <span><?= htmlspecialchars($history['class']); ?></span>
                                    </div>
                                    <div class="exam-meta-item">
                                        <i class="bi bi-person"></i>
                                        <span>Instructor: <?= htmlspecialchars($history['teacher_name']); ?></span>
                                    </div>
                                    <div class="exam-meta-item">
                                        <i class="bi bi-list-ol"></i>
                                        <span>Total Questions: <?= htmlspecialchars($history['total_questions']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($history['completed']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                Completed on: <?= date("F j, Y", strtotime($history['enrolled_date'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                Enrolled on: <?= date("F j, Y", strtotime($history['enrolled_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if (!$history['completed']): ?>
                                            <a href="take-exam.php?id=<?= $history['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil-square me-1"></i> Take Exam
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body empty-state">
                            <i class="bi bi-clock-history"></i>
                            <h4>No Exam History Found</h4>
                            <p>You haven't been enrolled in any exams yet. Once your instructor assigns you to an exam, it will appear here.</p>
                            <a href="dashboard.php" class="btn btn-primary mt-3">
                                <i class="bi bi-house-door me-2"></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_GET['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?= htmlspecialchars($_GET['error']); ?>',
            confirmButtonColor: '#2a2185'
        });
    </script>
    <?php endif; ?>
</body>
</html>