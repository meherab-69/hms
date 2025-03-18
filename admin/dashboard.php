<?php
session_start();

// Redirect to login page if the admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the header
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .welcome-message {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .logout-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-message">
            Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
        </div>
        <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.</p>

        <!-- Dashboard Content -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage Users</h5>
                        <p class="card-text">View and manage all users in the system.</p>
                        <a href="manage_users.php" class="btn btn-primary">Go to Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage Appointments</h5>
                        <p class="card-text">View and manage all appointments.</p>
                        <a href="manage_appointments.php" class="btn btn-primary">Go to Appointments</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Settings</h5>
                        <p class="card-text">Update your profile and settings.</p>
                        <a href="settings.php" class="btn btn-primary">Go to Settings</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Link -->
        <div class="mt-4 text-center">
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Include the footer
include '../includes/footer.php';
?>