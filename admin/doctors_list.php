<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search and filter variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$type_filter = isset($_GET['type_filter']) ? $conn->real_escape_string($_GET['type_filter']) : '';
$speciality_filter = isset($_GET['speciality_filter']) ? $conn->real_escape_string($_GET['speciality_filter']) : '';

// Base query
$query = "SELECT * FROM doctors WHERE 1=1";

// Add search condition
if (!empty($search)) {
    $query .= " AND (firstname LIKE '%$search%' OR lastname LIKE '%$search%' OR doctor_id LIKE '%$search%')";
}

// Add type filter
if (!empty($type_filter)) {
    $query .= " AND user_type = '$type_filter'";
}

// Add speciality filter
if (!empty($speciality_filter)) {
    $query .= " AND speciality = '$speciality_filter'";
}

// Get total records for pagination
$total_result = $conn->query($query);
$total_records = $total_result->num_rows;
$total_pages = ceil($total_records / $limit);

// Add pagination to query
$query .= " LIMIT $start, $limit";

// Execute final query
$result = $conn->query($query);

// Get unique specialities for filter dropdown
$specialities_query = "SELECT DISTINCT speciality FROM doctors ORDER BY speciality";
$specialities_result = $conn->query($specialities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors List</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .badge-permanent {
            background-color: #198754;
        }
        .badge-visiting {
            background-color: #fd7e14;
        }
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 2px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .filter-section {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-user-md me-2"></i>Doctors List</h3>
                        <a href="add_doctors.php" class="btn btn-light">
                            <i class="fas fa-plus me-1"></i> Add New Doctor
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Filter Section -->
                        <div class="filter-section mb-4">
                            <form method="GET" action="doctors_list.php">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or ID" value="<?php echo htmlspecialchars($search); ?>">
                                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="type_filter" class="form-label">Doctor Type</label>
                                        <select class="form-select" id="type_filter" name="type_filter">
                                            <option value="">All Types</option>
                                            <option value="Permanent" <?php echo $type_filter == 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                            <option value="Temporary" <?php echo $type_filter == 'Temporary' ? 'selected' : ''; ?>>Visiting</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="speciality_filter" class="form-label">Speciality</label>
                                        <select class="form-select" id="speciality_filter" name="speciality_filter">
                                            <option value="">All Specialities</option>
                                            <?php while ($row = $specialities_result->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($row['speciality']); ?>" <?php echo $speciality_filter == $row['speciality'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($row['speciality']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                                        <a href="doctors_list.php" class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Doctors Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Speciality</th>
                                        <th>Qualification</th>
                                        <th>Type</th>
                                        <th>Contact</th>
                                        <th>Working Days</th>
                                        <th>Working Hours</th>
                                        <th>Appt Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): 
                                            $contact = json_decode($row['contact'], true);
                                            $working_days = explode(',', $row['working_days']);
                                            $days_map = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'];
                                            $display_days = array_map(function($day) use ($days_map) {
                                                return $days_map[$day] ?? $day;
                                            }, $working_days);
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['doctor_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                                <td><?php echo htmlspecialchars($row['speciality']); ?></td>
                                                <td><?php echo htmlspecialchars($row['qualification']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['user_type'] == 'Permanent' ? 'badge-permanent' : 'badge-visiting'; ?> rounded-pill">
                                                        <?php echo htmlspecialchars($row['user_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($contact['email'] ?? 'N/A'); ?></div>
                                                    <div><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($contact['phone'] ?? 'N/A'); ?></div>
                                                </td>
                                                <td><?php echo implode(', ', $display_days); ?></td>
                                                <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['appointment_duration']) . ' mins'; ?></td>
                                                <td class="action-btns">
                                                    <a href="view_doctor.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_doctor.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-sm btn-warning" title="Update Info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete_doctor.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-sm btn-danger" title="Remove Doctor" onclick="return confirm('Are you sure you want to delete this doctor?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No doctors found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="doctors_list.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>&speciality_filter=<?php echo urlencode($speciality_filter); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="doctors_list.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>&speciality_filter=<?php echo urlencode($speciality_filter); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="doctors_list.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>&speciality_filter=<?php echo urlencode($speciality_filter); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('table').DataTable({
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                searching: false,
                paging: false,
                info: false,
                responsive: true
            });

            // Reset filters button
            $('.reset-filters').click(function() {
                window.location.href = 'doctors_list.php';
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>