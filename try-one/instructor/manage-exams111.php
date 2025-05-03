<?php
require '../db/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Fetch all exams with their associated programs and year sections
$exams_sql = "
    SELECT 
        e.*, 
        p.program_name, 
        ys.year, 
        ys.section 
    FROM 
        exams e
    LEFT JOIN 
        programs p ON e.program_id = p.id
    LEFT JOIN 
        year_sections ys ON e.year_section_id = ys.id
    ORDER BY e.created_at DESC
";
$exams_result = $conn->query($exams_sql);

// Fetch all programs
$programs_sql = "SELECT * FROM programs";
$programs_result = $conn->query($programs_sql);

// Fetch all year sections
$year_sections_sql = "SELECT * FROM year_sections";
$year_sections_result = $conn->query($year_sections_sql);

// Handle messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <style>
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        .badge-inactive {
            background-color: #dc3545;
            color: white;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include '../sidebar/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Manage Examinations</h1>
                    <a href="create-exams.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle-fill me-2"></i> Create New Exam
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Simple Filters -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Program:</label>
                            <select class="form-select" id="program-filter">
                                <option value="">All Programs</option>
                                <?php while ($program = $programs_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($program['program_name']); ?>">
                                        <?php echo htmlspecialchars($program['program_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Year-Section:</label>
                            <select class="form-select" id="year-section-filter">
                                <option value="">All Sections</option>
                                <?php while ($year_section = $year_sections_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($year_section['year'] . ' ' . $year_section['section']); ?>">
                                        <?php echo htmlspecialchars($year_section['year'] . ' ' . $year_section['section']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Status:</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Search:</label>
                            <input type="text" id="search-exam" class="form-control" placeholder="Search subject...">
                        </div>
                    </div>
                    <button class="btn btn-secondary" onclick="resetFilters()">Reset Filters</button>
                </div>

                <!-- Exams Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Items</th>
                                <th>Date Created</th>
                                <th>Program</th>
                                <th>Year-Section</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($exams_result->num_rows > 0): ?>
                                <?php while ($exam = $exams_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['item'] ?? 0); ?></td>
                                        <td><?php echo date("F j, Y", strtotime($exam['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($exam['program_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['year'] . ' ' . $exam['section']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['duration']); ?> min</td>
                                        <td>
                                            <span class="badge <?php echo $exam['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo ucfirst($exam['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-exams.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="add-take-to-exams.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success">Add Students</a>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $exam['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No exams found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple filter function
        function filterTable() {
            const program = document.getElementById('program-filter').value.toLowerCase();
            const yearSection = document.getElementById('year-section-filter').value.toLowerCase();
            const status = document.getElementById('status-filter').value.toLowerCase();
            const search = document.getElementById('search-exam').value.toLowerCase();
            
            const rows = document.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                if (cells.length) {
                    const subject = cells[0].textContent.toLowerCase();
                    const programText = cells[3].textContent.toLowerCase();
                    const yearSectionText = cells[4].textContent.toLowerCase();
                    const statusText = cells[6].textContent.toLowerCase().trim();
                    
                    const showRow = (!program || programText.includes(program)) &&
                                  (!yearSection || yearSectionText.includes(yearSection)) &&
                                  (!status || statusText === status) &&  // Changed to exact match
                                  (!search || subject.includes(search));
                    
                    row.style.display = showRow ? '' : 'none';
                }
            }
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('program-filter').value = '';
            document.getElementById('year-section-filter').value = '';
            document.getElementById('status-filter').value = '';
            document.getElementById('search-exam').value = '';
            filterTable();
        }

        // Add event listeners
        document.getElementById('program-filter').addEventListener('change', filterTable);
        document.getElementById('year-section-filter').addEventListener('change', filterTable);
        document.getElementById('status-filter').addEventListener('change', filterTable);
        document.getElementById('search-exam').addEventListener('input', filterTable);

        // Confirm delete
        function confirmDelete(examId) {
            if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
                window.location.href = `../backend/delete-exams.php?id=${examId}`;
            }
        }
    </script>
</body>
</html>