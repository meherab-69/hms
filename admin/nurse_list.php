<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

// Fetch nurses from database
$query = "SELECT user_id, firstname, lastname, contact, shift, status FROM nurses";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: scale(1.03);
        }
        .status-active {
            color: #198754;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header text-center">
        <h1>Nurse Management</h1>
        <p>Hospital Management System - Admin Panel</p>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <h3>All Nurses</h3>
            </div>
            <div class="col-md-6 text-end">
                <a href="add_nurse.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Nurse
                </a>
                <a href="admin-dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Nurse Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Shift</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($nurse = $result->fetch_assoc()): 
                                $contact = json_decode($nurse['contact'], true);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($nurse['user_id']) ?></td>
                                <td><?= htmlspecialchars($nurse['firstname'] . ' ' . $nurse['lastname']) ?></td>
                                <td><?= htmlspecialchars($nurse['shift']) ?></td>
                                <td>
                                    <?php if ($contact): ?>
                                        <?= isset($contact['email']) ? htmlspecialchars($contact['email']) . '<br>' : '' ?>
                                        <?= isset($contact['phone']) ? htmlspecialchars($contact['phone']) : '' ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-<?= htmlspecialchars($nurse['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($nurse['status'])) ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="edit_nurse.php?id=<?= $nurse['user_id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php if ($nurse['status'] == 'active'): ?>
                                        <a href="deactivate_nurse.php?id=<?= $nurse['user_id'] ?>" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-circle"></i> Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="activate_nurse.php?id=<?= $nurse['user_id'] ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Activate
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>
<?php
// Close database connection
$conn->close();
?>