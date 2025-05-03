<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check = $_POST['check'] ?? '';
    $value = $_POST['value'] ?? '';
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;
    
    if (empty($check) || empty($value)) {
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }
    
    $exists = false;
    
    if ($check === 'username') {
        $sql = "SELECT * FROM students WHERE username = ?";
        $params = [$value];
        $types = "s";
        
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
    } elseif ($check === 'school_id') {
        $sql = "SELECT * FROM students WHERE school_id = ?";
        $params = [$value];
        $types = "s";
        
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
    }
    
    echo json_encode(['exists' => $exists]);
    exit();
}

echo json_encode(['error' => 'Invalid request method']);
?>