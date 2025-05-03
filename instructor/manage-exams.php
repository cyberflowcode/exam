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
        .dropdown-menu {
            min-width: 200px;
            padding: 0.5rem 0;
            margin: 0;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .status-toggle {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #dee2e6;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        .badge-inactive {
            background-color: #dc3545;
            color: white;
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
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="program-filter" class="form-label">Filter by Program:</label>
                                <select class="form-select" id="program-filter">
                                    <option value="">All Programs</option>
                                    <?php while ($program = $programs_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($program['program_name']); ?>">
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="year-section-filter" class="form-label">Filter by Year/Section:</label>
                                <select class="form-select" id="year-section-filter">
                                    <option value="">All Year and Section</option>
                                    <?php while ($year_section = $year_sections_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($year_section['year'] . ' ' . $year_section['section']); ?>">
                                            <?php echo htmlspecialchars($year_section['year'] . ' ' . $year_section['section']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="status-filter" class="form-label">Filter by Status:</label>
                                <select class="form-select" id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search-exam" class="form-label">Search Subject:</label>
                                <input type="text" id="search-exam" class="form-control" placeholder="Enter subject name...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exams Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
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
                                                    <div class="dropdown">
                                                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item" href="edit-exams.php?id=<?php echo $exam['id']; ?>">
                                                                <i class="bi bi-pencil-fill"></i> Edit
                                                            </a>
                                                            <a class="dropdown-item" href="add-take-to-exams.php?id=<?php echo $exam['id']; ?>">
                                                                <i class="bi bi-person-plus-fill"></i> Add Students
                                                            </a>
                                                            <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $exam['id']; ?>)">
                                                                <i class="bi bi-trash-fill"></i> Delete
                                                            </a>
                                                            <div class="status-toggle">
                                                                <span>Status:</span>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                        <?php echo $exam['status'] === 'active' ? 'checked' : ''; ?>
                                                                        onchange="updateStatus(<?php echo $exam['id']; ?>, this.checked)">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No exams found</td>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        // Add event listeners to filters
        document.getElementById('program-filter').addEventListener('change', filterTable);
        document.getElementById('year-section-filter').addEventListener('change', filterTable);
        document.getElementById('status-filter').addEventListener('change', filterTable);
        document.getElementById('search-exam').addEventListener('input', filterTable);

        // Confirm delete
        function confirmDelete(examId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete all exam questions and student results. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../backend/delete-exams.php?id=${examId}`;
                }
            });
        }

        // Update exam status
        function updateStatus(examId, isActive) {
            const status = isActive ? 'active' : 'inactive';
            window.location.href = `../backend/update-exam-status.php?id=${examId}&status=${status}`;
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.remove('show');
                }, 5000);
            });
        });
    </script>
</body>
</html>