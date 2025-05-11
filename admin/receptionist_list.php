<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

// Fetch all receptionists from the database
$query = "SELECT user_id, firstname, lastname, contact, shift FROM receptionists";
$result = mysqli_query($conn, $query);

$receptionists = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode the JSON contact information
        $contact = json_decode($row['contact'], true);
        $row['email'] = $contact['email'] ?? '';
        $row['phone'] = $contact['phone'] ?? '';
        $receptionists[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist List</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }
        .badge-morning {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-afternoon {
            background-color: #fd7e14;
            color: white;
        }
        .badge-night {
            background-color: #212529;
            color: white;
        }
        .action-btns .btn {
            margin-right: 5px;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .shift-filter {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="fas fa-user-tie me-2"></i>Receptionist Management</h1>
            <p>Hospital Management System - Admin Panel</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Receptionists</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-end">
                <a href="add_receptionist.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Receptionist
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Receptionist List</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6 search-container">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search receptionists...">
                        </div>
                    </div>
                    <div class="col-md-6 shift-filter">
                        <select id="shiftFilter" class="form-select">
                            <option value="">All Shifts</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Night">Night</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Shift</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($receptionists)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No receptionists found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($receptionists as $receptionist): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($receptionist['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($receptionist['firstname'] . ' ' . $receptionist['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($receptionist['email']); ?></td>
                                        <td><?php echo htmlspecialchars($receptionist['phone']); ?></td>
                                        <td>
                                            <?php 
                                            $badgeClass = '';
                                            if ($receptionist['shift'] == 'Morning') {
                                                $badgeClass = 'badge-morning';
                                            } elseif ($receptionist['shift'] == 'Afternoon') {
                                                $badgeClass = 'badge-afternoon';
                                            } else {
                                                $badgeClass = 'badge-night';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($receptionist['shift']); ?>
                                            </span>
                                        </td>
                                        <td class="action-btns">
                                            <a href="view_receptionist.php?id=<?php echo $receptionist['user_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_receptionist.php?id=<?php echo $receptionist['user_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $receptionist['user_id']; ?>" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this receptionist? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Delete button click handler
            $('.delete-btn').click(function() {
                var receptionistId = $(this).data('id');
                $('#confirmDelete').attr('href', 'delete_receptionist.php?id=' + receptionistId);
                $('#deleteModal').modal('show');
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Shift filter functionality
            $('#shiftFilter').on('change', function() {
                var filter = $(this).val();
                if (filter === "") {
                    $('tbody tr').show();
                } else {
                    $('tbody tr').each(function() {
                        var shift = $(this).find('td:eq(4)').text().trim();
                        $(this).toggle(shift === filter);
                    });
                }
            });
        });
    </script>
</body>
</html>