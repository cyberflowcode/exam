<?php
header('Content-Type: application/json');

require '../db/db.php';
require 'exam_session.php';

if (!isset($_POST['exam_id']) || !isset($_POST['student_id'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$exam_id = $_POST['exam_id'];
$student_id = $_POST['student_id'];

$session = getExamSession($exam_id, $student_id);

if (!$session) {
    echo json_encode(['error' => 'No active session']);
    exit();
}

$current_time = time();
$time_remaining = $session['end_time'] - $current_time;

if ($time_remaining <= 0) {
    autoSubmitExpiredExam($exam_id, $student_id);
    echo json_encode(['expired' => true]);
    exit();
}

echo json_encode([
    'time_remaining' => $time_remaining,
    'end_time' => $session['end_time']
]);
?>