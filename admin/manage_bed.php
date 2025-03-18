<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); // Redirect to login if not authenticated
    exit();
}
?>

<?php include 'admin_header.php'; ?>

<!-- Manage Bed Content -->
<div class="container my-5">
    <h2 class="text-center mb-4">Manage Bed</h2>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="manage_doctors.php" class="list-group-item list-group-item-action">Manage Doctors</a>
                <a href="manage_receptionist.php" class="list-group-item list-group-item-action">Manage Receptionist</a>
                <a href="manage_nurse.php" class="list-group-item list-group-item-action">Manage Nurse</a>
                <a href="manage_bed.php" class="list-group-item list-group-item-action active">Manage Bed</a>
                <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Bed List</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bed Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>101</td>
                                <td>Occupied</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="#" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>102</td>
                                <td>Available</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="#" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <a href="add_bed.php" class="btn btn-primary">Add New Bed</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>