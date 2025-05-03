<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $exam_id = $_GET['id'];
    $exam_sql = "SELECT * FROM exams WHERE id = '$exam_id'";
    $exam_result = $conn->query($exam_sql);
    if ($exam_result->num_rows > 0) {
        $exam = $exam_result->fetch_assoc();

        $questions_sql = "SELECT * FROM questions WHERE exam_id = '$exam_id'";
        $questions_result = $conn->query($questions_sql);
    } else {
        header('Location: manage-exams.php');
        exit();
    }
} else {
    header('Location: manage-exams.php');
    exit();
}

// Fetch all programs
$programs_sql = "SELECT * FROM programs";
$programs_result = $conn->query($programs_sql);

// Fetch all year sections
$year_sections_sql = "SELECT * FROM year_sections";
$year_sections_result = $conn->query($year_sections_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_name = $conn->real_escape_string($_POST['exam_name']);
    $program_id = $_POST['program_id'];
    $year_section_id = $_POST['year_section_id'];
    $duration = $conn->real_escape_string($_POST['duration']);
    $total_questions = 0;

    if (isset($_POST['question_id'])) {
        $total_questions += count($_POST['question_id']);
    }
    if (isset($_POST['new_question_text'])) {
        foreach ($_POST['new_question_text'] as $key => $new_question_text) {
            if (!empty($new_question_text) && !empty($_POST['new_choice_a'][$key]) && !empty($_POST['new_choice_b'][$key]) && !empty($_POST['new_choice_c'][$key]) && !empty($_POST['new_choice_d'][$key]) && !empty($_POST['new_correct_answer'][$key])) {
                $total_questions++;
            }
        }
    }
    
    // Update exam details
    $sql = "UPDATE exams SET exam_name = '$exam_name', program_id = '$program_id', year_section_id = '$year_section_id', duration = '$duration', item = '$total_questions' WHERE id = '$exam_id'";
    if ($conn->query($sql)) {
        // Update questions
        if (isset($_POST['question_id'])) {
            foreach ($_POST['question_id'] as $key => $question_id) {
                $question_text = $conn->real_escape_string($_POST['question_text'][$key]);
                $choice_a = $conn->real_escape_string($_POST['choice_a'][$key]);
                $choice_b = $conn->real_escape_string($_POST['choice_b'][$key]);
                $choice_c = $conn->real_escape_string($_POST['choice_c'][$key]);
                $choice_d = $conn->real_escape_string($_POST['choice_d'][$key]);
                $correct_answer = $conn->real_escape_string($_POST['correct_answer'][$key]);

                $question_sql = "UPDATE questions SET question_text = '$question_text', choice_a = '$choice_a', choice_b = '$choice_b', choice_c = '$choice_c', choice_d = '$choice_d', correct_answer = '$correct_answer' WHERE id = '$question_id'";
                $conn->query($question_sql);
            }
        }

        // Add new questions
        if (isset($_POST['new_question_text'])) {
            foreach ($_POST['new_question_text'] as $key => $new_question_text) {
                if (!empty($new_question_text) && !empty($_POST['new_choice_a'][$key]) && !empty($_POST['new_choice_b'][$key]) && !empty($_POST['new_choice_c'][$key]) && !empty($_POST['new_choice_d'][$key]) && !empty($_POST['new_correct_answer'][$key])) {
                    $new_choice_a = $conn->real_escape_string($_POST['new_choice_a'][$key]);
                    $new_choice_b = $conn->real_escape_string($_POST['new_choice_b'][$key]);
                    $new_choice_c = $conn->real_escape_string($_POST['new_choice_c'][$key]);
                    $new_choice_d = $conn->real_escape_string($_POST['new_choice_d'][$key]);
                    $new_correct_answer = $conn->real_escape_string($_POST['new_correct_answer'][$key]);

                    $new_question_sql = "INSERT INTO questions (exam_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer) VALUES ('$exam_id', '$new_question_text', '$new_choice_a', '$new_choice_b', '$new_choice_c', '$new_choice_d', '$new_correct_answer')";
                    $conn->query($new_question_sql);
                    $total_questions++;
                }
            }
        }
        $_SESSION['success'] = "Exam updated successfully";
        header('Location: manage-exams.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container-fluid {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
            <?php include '../sidebar/sidebar.php'; ?>
        <div class="content-wrapper">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="card-title mb-0">Edit Exam</h1>
                        <a href="manage-exams.php" class="btn btn-danger"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                    <form method="post">
                        <!-- Exam name and program/year section dropdowns -->
                        <div class="mb-3">
                            <label for="exam_name" class="form-label">Subject:</label>
                            <input type="text" id="exam_name" name="exam_name" class="form-control" value="<?php echo $exam['exam_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="program_id" class="form-label">Program:</label>
                                    <select id="program_id" name="program_id" class="form-select" required>
                                        <option value="">Select Program</option>
                                        <?php while ($program = $programs_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $program['id']; ?>" <?php echo ($program['id'] == $exam['program_id']) ? 'selected' : ''; ?>>
                                                <?php echo $program['program_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="year_section_id" class="form-label">Year and Section:</label>
                                    <select id="year_section_id" name="year_section_id" class="form-select" required>
                                        <option value="">Select Year and Section</option>
                                        <?php while ($year_section = $year_sections_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $year_section['id']; ?>" <?php echo ($year_section['id'] == $exam['year_section_id']) ? 'selected' : ''; ?>>
                                                <?php echo $year_section['year'] . ' ' . $year_section['section']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="duration" class="form-label">Duration (minutes):</label>
                                    <input type="number" id="duration" name="duration" class="form-control" value="<?php echo $exam['duration']; ?>" required>
                                </div>
                            </div>
                        </div>
                        <h2>Questions:</h2>
                        <?php $item_number = 1; while ($question = $questions_result->fetch_assoc()) { ?>
                        <!-- Question card -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3>Question <?php echo $item_number; ?></h3>
                                    <button type="button" class="btn-close" aria-label="Close" onclick="deleteQuestion(<?php echo $question['id']; ?>, <?php echo $exam_id; ?>)"></button>
                                </div>
                                <div class="mb-3 d-flex align-items-center">
                                    <input type="text" id="question_text_<?php echo $question['id']; ?>" name="question_text[]" class="form-control" value="<?php echo $question['question_text']; ?>" required>
                                    <input type="hidden" name="question_id[]" value="<?php echo $question['id']; ?>">
                                </div>
                                <!-- Choices -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="choice_a_<?php echo $question['id']; ?>" class="form-label">A:</label>
                                            <input type="text" id="choice_a_<?php echo $question['id']; ?>" name="choice_a[]" class="form-control" value="<?php echo $question['choice_a']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="choice_b_<?php echo $question['id']; ?>" class="form-label">B:</label>
                                            <input type="text" id="choice_b_<?php echo $question['id']; ?>" name="choice_b[]" class="form-control" value="<?php echo $question['choice_b']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="choice_c_<?php echo $question['id']; ?>" class="form-label">C:</label>
                                            <input type="text" id="choice_c_<?php echo $question['id']; ?>" name="choice_c[]" class="form-control" value="<?php echo $question['choice_c']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="choice_d_<?php echo $question['id']; ?>" class="form-label">D:</label>
                                            <input type="text" id="choice_d_<?php echo $question['id']; ?>" name="choice_d[]" class="form-control" value="<?php echo $question['choice_d']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <!-- Correct answer -->
                                <div class="mb-3">
                                    <label for="correct_answer_<?php echo $question['id']; ?>" class="form-label">Correct Answer:</label>
                                    <select id="correct_answer_<?php echo $question['id']; ?>" name="correct_answer[]" class="form-control" required>
                                        <option value="A" <?php if ($question['correct_answer'] == 'A') echo 'selected'; ?>>A</option>
                                        <option value="B" <?php if ($question['correct_answer'] == 'B') echo 'selected'; ?>>B</option>
                                        <option value="C" <?php if ($question['correct_answer'] == 'C') echo 'selected'; ?>>C</option>
                                        <option value="D" <?php if ($question['correct_answer'] == 'D') echo 'selected'; ?>>D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php $item_number++; } ?>
                        <!-- Add new question -->
                        <div id="new_questions">
                            <div class="card">
                                <div class="card-body">
                                    <h3>New Question (Optional)</h3>
                                    <div class="mb-3">
                                        <label for="new_question_text_0" class="form-label">Question:</label>
                                        <input type="text" id="new_question_text_0" name="new_question_text[]" class="form-control">
                                    </div>
                                    <!-- Choices -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="new_choice_a_0" class="form-label">A:</label>
                                                <input type="text" id="new_choice_a_0" name="new_choice_a[]" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="new_choice_b_0" class="form-label">B:</label>
                                                <input type="text" id="new_choice_b_0" name="new_choice_b[]" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="new_choice_c_0" class="form-label">C:</label>
                                                <input type="text" id="new_choice_c_0" name="new_choice_c[]" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="new_choice_d_0" class="form-label">D:</label>
                                                <input type="text" id="new_choice_d_0" name="new_choice_d[]" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Correct answer -->
                                    <div class="mb-3">
                                        <label for="new_correct_answer_0" class="form-label">Correct Answer:</label>
                                        <select id="new_correct_answer_0" name="new_correct_answer[]" class="form-control">
                                            <option value="">Select answer</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add_question" class="btn btn-secondary">Add Question</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        let questionCount = 1;
        document.getElementById('add_question').addEventListener('click', function() {
            let newQuestionHtml = `
                <div class="card" id="new_question_${questionCount}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>New Question</h3>
                            <button type="button" class="btn-close" aria-label="Close" onclick="deleteNewQuestion(${questionCount})"></button>
                        </div>
                        <div class="mb-3">
                            <label for="new_question_text_${questionCount}" class="form-label">Question:</label>
                            <input type="text" id="new_question_text_${questionCount}" name="new_question_text[]" class="form-control">
                        </div>
                        <!-- Choices -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_choice_a_${questionCount}" class="form-label">A:</label>
                                    <input type="text" id="new_choice_a_${questionCount}" name="new_choice_a[]" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_choice_b_${questionCount}" class="form-label">B:</label>
                                    <input type="text" id="new_choice_b_${questionCount}" name="new_choice_b[]" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_choice_c_${questionCount}" class="form-label">C:</label>
                                    <input type="text" id="new_choice_c_${questionCount}" name="new_choice_c[]" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_choice_d_${questionCount}" class="form-label">D:</label>
                                    <input type="text" id="new_choice_d_${questionCount}" name="new_choice_d[]" class="form-control">
                                </div>
                            </div>
                        </div>
                        <!-- Correct answer -->
                        <div class="mb-3">
                            <label for="new_correct_answer_${questionCount}" class="form-label">Correct Answer:</label>
                            <select id="new_correct_answer_${questionCount}" name="new_correct_answer[]" class="form-control">
                                <option value="">Select answer</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('new_questions').insertAdjacentHTML('beforeend', newQuestionHtml);
            questionCount++;
        });

        function deleteNewQuestion(id) {
            document.getElementById(`new_question_${id}`).remove();
        }

        function deleteQuestion(questionId, examId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "../backend/delete-question-process.php",
                        data: { question_id: questionId, exam_id: examId },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'The question has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        }
                    });
                }
            });
        }

        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to save the changes?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>