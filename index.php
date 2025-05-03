<?php
require 'db/db.php';

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("
            SELECT id, username, password, role FROM teachers WHERE username = ? 
            UNION SELECT id, username, password, role FROM students WHERE username = ?
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['teacher_id'] = $user['id'];
            } elseif ($user['role'] === 'student') {
                $_SESSION['student_id'] = $user['id']; 
            }
            $_SESSION['role'] = $user['role'];

            $redirectUrl = $user['role'] === 'admin' ? 'instructor/dashboard.php' : 'student/dashboard.php';
            header("Location: $redirectUrl");
            exit();
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/login/styles-login.css">
    <style>
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 20px;
            transition: opacity 0.5s ease;
        }
        .hide {
            opacity: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login</h1>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="error" id="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <script>
                setTimeout(function() {
                    var errorMessage = document.getElementById('error-message');
                    errorMessage.classList.add('hide');
                }, 2000);
            </script>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>