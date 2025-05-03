<?php
require '../db/db.php';

// Check user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Fetch existing programs and year sections for deletion
$programs = $conn->query("SELECT id, program_name FROM programs")->fetch_all(MYSQLI_ASSOC);
$year_sections = $conn->query("SELECT id, year, section FROM year_sections")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs and Year Sections</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/instructor-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../sidebar/sidebar.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">Programs and Year Sections</h1>
                
                <div class="row">
                    <!-- Add Program or Year/Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Add Program or Year/Section</h5>
                            </div>
                            <div class="card-body">
                                <form id="programForm" action="../backend/add_programs_year_sections_process.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="program_name" class="form-label">Program</label>
                                                <input type="text" class="form-control" id="program_name" name="program_name" maxlength="5" oninput="validateInput(event)">
                                                <small class="text-muted">e.g., BSIT, BSCS</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="year" class="form-label">Year</label>
                                                <input type="number" class="form-control" id="year" name="year"  min="1" max="5" oninput="if (this.value.length > 1) this.value = this.value.slice(0, 1);">
                                                <small class="text-muted">e.g., 1, 2, 3, 4</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="section" class="form-label">Section</label>
                                                <input type="text" class="form-control" id="section" name="section" maxlength="1" oninput="validateInput(event)">
                                                <small class="text-muted">e.g., A, B, C</small>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-3">
                                        <i class="bi bi-plus-circle me-2"></i> Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delete Programs or Year/Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Delete Programs or Year/Section</h5>
                            </div>
                            <div class="card-body">
                                <form action="../backend/delete_programs_year_sections_process.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="delete_program" class="form-label">Select Program</label>
                                                <select class="form-select" id="delete_program" name="delete_program">
                                                    <option value="">Select Program</option>
                                                    <?php foreach ($programs as $program): ?>
                                                        <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="delete_year_section" class="form-label">Select Year/Section</label>
                                                <select class="form-select" id="delete_year_section" name="delete_year_section">
                                                    <option value="">Select Year/Section</option>
                                                    <?php foreach ($year_sections as $year_section): ?>
                                                        <option value="<?php echo $year_section['id']; ?>"><?php echo htmlspecialchars($year_section['year']) . ' - ' . htmlspecialchars($year_section['section']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
                                        <i class="bi bi-trash me-2"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Programs and Year Sections Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Programs and Year Sections List</h5>
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="tableSearch" class="form-control" placeholder="Search...">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Program Name</th>
                                    <th>Year</th>
                                    <th>Section</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $maxCount = max(count($programs), count($year_sections));
                                if ($maxCount > 0):
                                    for ($i = 0; $i < $maxCount; $i++) {
                                        $program = isset($programs[$i]) ? $programs[$i] : null;
                                        $year_section = isset($year_sections[$i]) ? $year_sections[$i] : null;

                                        $program_name = $program ? htmlspecialchars($program['program_name']) : '';
                                        $year = $year_section ? htmlspecialchars($year_section['year']) : '';
                                        $section = $year_section ? htmlspecialchars($year_section['section']) : '';

                                        echo "<tr>";
                                        echo "<td>{$program_name}</td>";
                                        echo "<td>{$year}</td>";
                                        echo "<td>{$section}</td>";
                                        echo "</tr>";
                                    }
                                else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No programs or year sections found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        function validateInput(event) {
            const input = event.target;
            input.value = input.value.replace(/[^A-Za-z]/g, '').toUpperCase();
        }
        
        document.getElementById('programForm').onsubmit = function(event) {
            event.preventDefault();

            const programName = document.getElementById('program_name').value;
            const year = document.getElementById('year').value;
            const section = document.getElementById('section').value;

            if (!programName && (!year || !section)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Please enter a Program, or both Year and Section.',
                });
                return;
            }
            
            const data = new FormData();
            data.append('program_name', programName);
            data.append('year', year);
            data.append('section', section);

            fetch('../backend/check_programs_year_sections.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: result.error,
                    });
                } else {
                    this.submit();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        };
        
        $(document).ready(function() {
            $("#tableSearch").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
</body>
</html>