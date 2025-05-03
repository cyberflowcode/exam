<?php
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page_name) {
    global $current_page;
    return ($current_page == $page_name) ? 'active' : '';
}
?>

<button class="mobile-toggle" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>
<style>
.sidebar {
    height: 100vh;
    background: linear-gradient(180deg, #2a2185 0%, #1f1875 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.sidebar-header {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h2 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    letter-spacing: 1px;
}

.sidebar ul {
    list-style-type: none;
    padding: 20px 0;
    margin: 0;
}
.sidebar-content {
    width: 200px;
    flex-grow: 1;
}

.sidebar li {
    margin: 5px 15px;
}

.sidebar a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.sidebar a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateX(5px);
}

.sidebar a.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
}

.sidebar a i {
    margin-right: 12px;
    font-size: 18px;
    width: 20px;
    display: flex;
    justify-content: center;
}

.sidebar a span {
    font-size: 14px;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: inherit;
}

.sidebar-footer a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    padding-top: auto;
}

.sidebar-footer a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
}

.sidebar-footer i {
    margin-right: 10px;
}

/* Mobile Toggle Button */
.mobile-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

/* Responsive Design */
@media (max-width: 992px) {
    .sidebar {
        width: 200px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .mobile-toggle {
        display: block;
    }
    
    .content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        padding-top: 70px;
    }
}

/* Scrollbar Styling */
.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>SFXC</h2>
    </div>
    <div class="sidebar-content">
        <ul>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href="dashboard.php" class="<?= isActive('dashboard.php') ?>">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="view-student.php" class="<?= isActive('view-student.php') ?>">
                        <i class="bi bi-people-fill"></i>
                        <span>My Students</span>
                    </a>
                </li>
                <li>
                    <a href="add-student.php" class="<?= isActive('add-student.php') ?>">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Add Students</span>
                    </a>
                </li>
                <li>
                    <a href="manage-exams.php" class="<?= isActive('manage-exams.php') ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>Examinations</span>
                    </a>
                </li>
                <li>
                    <a href="student-scores.php" class="<?= isActive('student-scores.php') ?>">
                        <i class="bi bi-graph-up"></i>
                        <span>Student Scores</span>
                    </a>
                </li>
                <li>
                    <a href="programandyrsection.php" class="<?= isActive('programandyrsection.php') ?>">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>Programs/Sections</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?= isActive('profile.php') ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'student'): ?>
                <li>
                    <a href="dashboard.php" class="<?= isActive('dashboard.php') ?>">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="take-exam.php" class="<?= isActive('take-exam.php') ?>">
                        <i class="bi bi-pencil-square"></i>
                        <span>Take Exam</span>
                    </a>
                </li>
                <li>
                    <a href="exam_history.php" class="<?= isActive('exam_history.php') ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>History</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?= isActive('profile.php') ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="view_grades.php" class="<?= isActive('view_grades.php') ?>">
                        <i class="bi bi-card-checklist"></i>
                        <span>My Grades</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div> 
    <div class="sidebar-footer">
        <a href="../backend/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = sidebar.contains(event.target) || toggle.contains(event.target);
            
            if (!isClickInside && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }
});
</script>