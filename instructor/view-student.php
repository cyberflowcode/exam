<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$students_sql = "SELECT s.*, p.program_name, ys.year, ys.section 
                 FROM students s 
                 LEFT JOIN programs p ON s.program_id = p.id 
                 LEFT JOIN year_sections ys ON s.year_section_id = ys.id
                 ORDER BY s.full_name ASC";
$students_result = $conn->query($students_sql);

$programs_sql = "SELECT * FROM programs";
$programs_result = $conn->query($programs_sql);

$year_sections_sql = "SELECT * FROM year_sections";
$year_sections_result = $conn->query($year_sections_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
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
                    <h1>Student Management</h1>
                    
                </div>
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group mb-0">
                                    <label for="program-filter" class="form-label">Filter by Program:</label>
                                    <select class="form-select" id="program-filter">
                                        <option value="">All Programs</option>
                                        <?php while ($program = $programs_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $program['program_name']; ?>"><?php echo $program['program_name']; ?></option>
                                        <?php } 
                                        $programs_result->data_seek(0);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-0">
                                    <label for="year-section-filter" class="form-label">Filter by Year/Section:</label>
                                    <select class="form-select" id="year-section-filter">
                                        <option value="">All Year and Section</option>
                                        <?php while ($year_section = $year_sections_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $year_section['year'] . ' ' . $year_section['section']; ?>"><?php echo $year_section['year'] . ' ' . $year_section['section']; ?></option>
                                        <?php } 
                                        $year_sections_result->data_seek(0);
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label for="search" class="form-label">Search:</label>
                                    <input type="text" id="search" class="form-control" placeholder="Search name...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Full Name</th>
                                        <th>Program</th>
                                        <th>Year-Section</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($students_result->num_rows > 0):
                                        while ($student = $students_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['school_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['year'] . ' ' . $student['section']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td>
                                                <a href="update-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger delete-student" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['full_name']); ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No students found</td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Filter table functionality
            function filterTable() {
                var program = $('#program-filter').val().toLowerCase();
                var yearSection = $('#year-section-filter').val().toLowerCase();
                var search = $('#search').val().toLowerCase();
                
                $('table tbody tr').each(function() {
                    var name = $(this).find('td:eq(1)').text().toLowerCase();
                    var programText = $(this).find('td:eq(2)').text().toLowerCase();
                    var yearSectionText = $(this).find('td:eq(3)').text().toLowerCase();
                    
                    if ((program === '' || programText === program) && 
                        (yearSection === '' || yearSectionText === yearSection) &&
                        (search === '' || name.includes(search))) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            $('#program-filter, #year-section-filter, #search').on('change keyup', filterTable);
            
            // Delete student confirmation
            $('.delete-student').on('click', function() {
                const studentId = $(this).data('id');
                const studentName = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${studentName}. This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff4d6d',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete student'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `../backend/delete-student.php?id=${studentId}`;
                    }
                });
            });
            
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatically close alerts after 5 seconds
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(() => {
                    const closeButton = new bootstrap.Alert(successAlert);
                    closeButton.close();
                }, 5000);
            }
        });
    </script>
</body>
</html>