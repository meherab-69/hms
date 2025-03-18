<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); // Redirect to login if not authenticated
    exit();
}

// Include the database connection
include '../includes/db_connection.php';
?>

<?php include 'admin_header.php'; ?>

<!-- Manage Doctors Content -->
<div class="container my-5">
    <h2 class="text-center mb-4">Manage Doctors</h2>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="manage_doctors.php" class="list-group-item list-group-item-action active">Manage Doctors</a>
                <a href="manage_receptionist.php" class="list-group-item list-group-item-action">Manage Receptionist</a>
                <a href="manage_nurse.php" class="list-group-item list-group-item-action">Manage Nurse</a>
                <a href="manage_bed.php" class="list-group-item list-group-item-action">Manage Bed</a>
                <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Doctors List</h5>
                </div>
                <div class="card-body">
                    <!-- Add New Doctor Button -->
                    <a href="add_doctor.php" class="btn btn-primary mb-3">Add New Doctor</a>

                    <!-- Doctors Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch doctors from the database
                            $sql = "SELECT * FROM doctors"; // Assuming your table is named `doctors`
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td>{$row['name']}</td>
                                            <td>{$row['specialization']}</td>
                                            <td>
                                                <a href='edit_doctor.php?id={$row['id']}' class='btn btn-sm btn-primary'>Edit</a>
                                                <a href='delete_doctor.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this doctor?\");'>Delete</a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No doctors found.</td></tr>";
                            }

                            // Close the database connection
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>