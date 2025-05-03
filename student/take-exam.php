<?php
require '../db/db.php';
require '../backend/check_exam_access.php';
require '../backend/exam_session.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$student_id = $_SESSION['student_id'] ?? null;
$exam_id = $_GET['id'] ?? null;

if (!$student_id || !$exam_id) {
    header('Location: dashboard.php');
    exit();
}

$access_check = checkExamAccess($exam_id, $student_id);

if (!$access_check['success']) {
    header('Location: dashboard.php?error=' . urlencode($access_check['message']));
    exit();
}

$exam = $access_check['exam'];

// Start or get exam session
$exam_session = startExamSession($exam_id, $student_id);

// Get questions for this exam
$questions_sql = "SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC";
$questions_stmt = $conn->prepare($questions_sql);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$total_questions = $questions_result->num_rows;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $conn->begin_transaction();
    
    try {
        $insert_sql = "INSERT INTO responses (student_id, question_id, response_text) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        foreach ($_POST['answer'] as $question_id => $answer) {
            $insert_stmt->bind_param("iis", $student_id, $question_id, $answer);
            $insert_stmt->execute();
        }
        
        // Mark exam as completed
        $complete_sql = "UPDATE exam_students SET completed = TRUE WHERE exam_id = ? AND student_id = ?";
        $complete_stmt = $conn->prepare($complete_sql);
        $complete_stmt->bind_param("ii", $exam_id, $student_id);
        $complete_stmt->execute();
        
        // Delete exam session
        $delete_session_sql = "DELETE FROM exam_sessions WHERE exam_id = ? AND student_id = ?";
        $delete_session_stmt = $conn->prepare($delete_session_sql);
        $delete_session_stmt->bind_param("ii", $exam_id, $student_id);
        $delete_session_stmt->execute();
        
        $conn->commit();
        
        header("Location: dashboard.php?message=" . urlencode("Exam submitted successfully!"));
        
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error submitting exam: " . $e->getMessage();
    }
}

// Reset questions result pointer
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam - <?= htmlspecialchars($exam['exam_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <link rel="stylesheet" href="assets/css/take-exam-styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Exam Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="mb-0"><?= htmlspecialchars($exam['exam_name']); ?></h1>
                        <p class="text-muted">
                            <?= htmlspecialchars($exam['program_name']); ?> | 
                            <?= htmlspecialchars($exam['year'] . '-' . $exam['section']); ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Timer and Exam Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                                <h5 class="mb-0">Total Questions: <?= $total_questions; ?></h5>
                                <p class="text-muted mb-0">Multiple Choice Questions</p>
                            </div>
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div id="timer-container">
                                    <h5 class="mb-0">Time Remaining:</h5>
                                    <p class="mb-0" id="timer"></p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center text-md-end">
                                <h5 class="mb-0">Duration: <?= $exam['duration']; ?> minutes</h5>
                                <p class="text-muted mb-0">Answer all questions</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="progress-indicator">
                    <span>Completion: <span id="progress-text">0%</span></span>
                    <div class="progress w-100 mx-3">
                        <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span id="answered-count">0/<?= $total_questions; ?></span>
                </div>
                
                <div class="question-navigation" id="question-navigation">
                    <!-- Question navigation buttons will be inserted here by JavaScript -->
                </div>
                
                <!-- Exam Questions -->
                <form method="POST" id="exam-form">
                    <div id="questions-container">
                        <?php 
                        $question_number = 1;
                        while ($question = $questions_result->fetch_assoc()): 
                            $question_id = $question['id'];
                        ?>
                            <div class="question-card" id="question-<?= $question_id; ?>" data-question-id="<?= $question_id; ?>" style="display: <?= $question_number === 1 ? 'block' : 'none'; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <span class="question-number"><?= $question_number; ?></span>
                                        Question <?= $question_number; ?> of <?= $total_questions; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="question-text"><?= htmlspecialchars($question['question_text']); ?></p>
                                    
                                    <div class="answers">
                                        <?php 
                                        $options = [
                                            'A' => $question['choice_a'],
                                            'B' => $question['choice_b'],
                                            'C' => $question['choice_c'],
                                            'D' => $question['choice_d']
                                        ];
                                        
                                        foreach ($options as $option_key => $option_text): 
                                        ?>
                                            <div class="answer-option" onclick="selectAnswer(this, '<?= $question_id; ?>', '<?= $option_key; ?>')">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answer[<?= $question_id; ?>]" 
                                                            id="option-<?= $question_id; ?>-<?= $option_key; ?>" value="<?= $option_key; ?>">
                                                    <label class="form-check-label w-100" for="option-<?= $question_id; ?>-<?= $option_key; ?>">
                                                        <strong><?= $option_key; ?>.</strong> <?= htmlspecialchars($option_text); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            $question_number++;
                        endwhile; 
                        ?>
                    </div>
                    
                    <!-- Exam Navigation -->
                    <div class="exam-navigation">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <button type="button" id="prev-btn" class="btn btn-outline-primary" onclick="navigateQuestions('prev')" disabled>
                                    <i class="bi bi-arrow-left me-2"></i> Previous
                                </button>
                                <button type="button" id="next-btn" class="btn btn-outline-primary ms-2" onclick="navigateQuestions('next')" <?= ($total_questions <= 1) ? 'disabled' : ''; ?>>
                                    Next <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <button type="button" class="btn btn-success" id="submit-btn" onclick="confirmSubmission()">
                                    <i class="bi bi-check-circle me-2"></i> Submit Exam
                                </button>
                                <input type="hidden" name="submit_exam" value="1">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let endTime = <?php echo $exam_session['end_time']; ?>;
        let timeRemaining = endTime - Math.floor(Date.now() / 1000);
        
        function checkExamTime() {
            fetch('../backend/check_exam_time.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.expired) {
                    Swal.fire({
                        title: 'Time\'s Up!',
                        text: 'Your exam has been automatically submitted.',
                        icon: 'warning',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false
                    }).then(() => {
                        window.location.href = 'dashboard.php?message=' + encodeURIComponent('Exam time expired. Your answers have been submitted.');
                    });
                } else {
                    timeRemaining = data.time_remaining;
                    updateTimer();
                }
            });
        }
        
        function updateTimer() {
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                document.getElementById('exam-form').submit();
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeRemaining <= 300) {
                document.getElementById('timer').classList.add('warning');
            }
            
            timeRemaining--;
        }
        
        // Check time every 30 seconds
        setInterval(checkExamTime, 30000);
        
        // Update timer every second
        const timerInterval = setInterval(updateTimer, 1000);
        
        // Previous JavaScript functions
        let currentQuestion = 1;
        const totalQuestions = <?= $total_questions; ?>;
        let answeredQuestions = {};
        
        function navigateQuestions(direction) {
            const questionCards = document.querySelectorAll('.question-card');
            let newIndex = currentQuestion;
            
            if (direction === 'next' && currentQuestion < totalQuestions) {
                newIndex = currentQuestion + 1;
            } else if (direction === 'prev' && currentQuestion > 1) {
                newIndex = currentQuestion - 1;
            } else if (typeof direction === 'number' && direction >= 1 && direction <= totalQuestions) {
                newIndex = direction;
            }
            
            if (newIndex !== currentQuestion) {
                questionCards[currentQuestion - 1].style.display = 'none';
                questionCards[newIndex - 1].style.display = 'block';
                currentQuestion = newIndex;
                updateNavigationButtons();
                updateQuestionNavigation();
            }
        }
        
        function updateNavigationButtons() {
            document.getElementById('prev-btn').disabled = currentQuestion === 1;
            document.getElementById('next-btn').disabled = currentQuestion === totalQuestions;
        }
        
        function selectAnswer(element, questionId, answer) {
            const radioButton = document.getElementById(`option-${questionId}-${answer}`);
            radioButton.checked = true;
            
            const questionOptions = document.querySelectorAll(`[id^="option-${questionId}-"]`);
            questionOptions.forEach(option => {
                option.closest('.answer-option').classList.remove('selected');
            });
            
            element.classList.add('selected');
            answeredQuestions[questionId] = true;
            
            updateProgressBar();
            updateQuestionNavigation();
        }
        
        function updateProgressBar() {
            const answeredCount = Object.keys(answeredQuestions).length;
            const progressPercentage = Math.round((answeredCount / totalQuestions) * 100);
            
            document.getElementById('progress-bar').style.width = `${progressPercentage}%`;
            document.getElementById('progress-bar').setAttribute('aria-valuenow', progressPercentage);
            document.getElementById('progress-text').textContent = `${progressPercentage}%`;
            document.getElementById('answered-count').textContent = `${answeredCount}/${totalQuestions}`;
        }
        
        function createQuestionNavigation() {
            const navigationContainer = document.getElementById('question-navigation');
            
            for (let i = 1; i <= totalQuestions; i++) {
                const navButton = document.createElement('button');
                navButton.className = 'question-nav-btn';
                navButton.textContent = i;
                navButton.setAttribute('type', 'button');
                navButton.onclick = () => navigateQuestions(i);
                navButton.id = `nav-btn-${i}`;
                
                navigationContainer.appendChild(navButton);
            }
            
            document.getElementById('nav-btn-1').classList.add('active');
        }
        
        function updateQuestionNavigation() {
            document.querySelectorAll('.question-nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(`nav-btn-${currentQuestion}`).classList.add('active');
            
            Object.keys(answeredQuestions).forEach(questionId => {
                const index = Array.from(document.querySelectorAll('.question-card'))
                    .findIndex(card => card.dataset.questionId === questionId) + 1;
                
                document.getElementById(`nav-btn-${index}`).classList.add('answered');
            });
        }
        
        function confirmSubmission() {
            const answeredCount = Object.keys(answeredQuestions).length;
            const unansweredCount = totalQuestions - answeredCount;
            
            if (unansweredCount > 0) {
                Swal.fire({
                    title: 'Unanswered Questions',
                    text: `You have ${unansweredCount} unanswered question${unansweredCount > 1 ? 's' : ''}. Do you want to submit anyway?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2bda60',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit anyway',
                    cancelButtonText: 'No, let me review'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('exam-form').submit();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Submit Exam?',
                    text: 'Once submitted, you cannot change your answers.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2bda60',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit',
                    cancelButtonText: 'No, review answers'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('exam-form').submit();
                    }
                });
            }
        }
        
        function checkPrefilledAnswers() {
            const questionCards = document.querySelectorAll('.question-card');
            
            questionCards.forEach((card, index) => {
                const questionId = card.dataset.questionId;
                const selectedOption = document.querySelector(`input[name="answer[${questionId}]"]:checked`);
                
                if (selectedOption) {
                    const optionKey = selectedOption.value;
                    const optionElement = document.getElementById(`option-${questionId}-${optionKey}`).closest('.answer-option');
                    
                    optionElement.classList.add('selected');
                    answeredQuestions[questionId] = true;
                }
            });
            
            updateProgressBar();
            updateQuestionNavigation();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            createQuestionNavigation();
            checkPrefilledAnswers();
            updateNavigationButtons();
            
            window.addEventListener('beforeunload', function(e) {
                const confirmationMessage = 'Are you sure you want to leave this page? Your exam progress will be lost.';
                e.returnValue = confirmationMessage;
                return confirmationMessage;
            });
        });
    </script>
</body>
</html>