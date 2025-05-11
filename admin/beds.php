<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}
// Database connection
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

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_bed"])) {
        // Update bed status
        $bed_id = $conn->real_escape_string($_POST["bed_id"]);
        $new_status = $conn->real_escape_string($_POST["new_status"]);
        
        $sql = "UPDATE beds SET status='$new_status' WHERE bed_id='$bed_id'";
        
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-success'>Bed status updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST["assign_nurse"])) {
        // Assign nurse to bed
        $bed_id = $conn->real_escape_string($_POST["bed_id"]);
        $user_id = $conn->real_escape_string($_POST["user_id"]);
        
        $sql = "UPDATE beds SET assigned_nurse_id=" . ($user_id ? "'$user_id'" : "NULL") . " WHERE bed_id='$bed_id'";
        
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-success'>Nurse assignment updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST["delete_bed"])) {
        // Delete bed
        $bed_id = $conn->real_escape_string($_POST["bed_id"]);
        
        $sql = "DELETE FROM beds WHERE bed_id='$bed_id'";
        
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-success'>Bed deleted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// Get all beds grouped by type with nurse names
$sql = "SELECT b.*, CONCAT(u.firstname, ' ', u.lastname) as nurse_name 
        FROM beds b 
        LEFT JOIN nurses u ON b.assigned_nurse_id = u.user_id
        ORDER BY bed_type, bed_id";
$result = $conn->query($sql);

// Get all ACTIVE nurses for dropdown
$nurses = [];
$nurses_result = $conn->query("SELECT user_id, CONCAT(firstname, ' ', lastname) as name 
                              FROM nurses
                              WHERE status = 'active'
                              ORDER BY firstname, lastname");
if ($nurses_result->num_rows > 0) {
    while($row = $nurses_result->fetch_assoc()) {
        $nurses[$row["user_id"]] = $row["name"];
    }
}

// Organize beds by type for the table view
$beds_by_type = [
    'General Bed' => [],
    'Non-AC Cabin' => [],
    'AC Cabin' => [],
    'ICU' => []
];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $beds_by_type[$row["bed_type"]][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Management - HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .status-available { color: #28a745; }
        .status-occupied { color: #dc3545; }
        .status-maintenance { color: #ffc107; }
        .status-reserved { color: #17a2b8; }
        .bed-table {
            margin-bottom: 30px;
        }
        .bed-table th {
            background-color: #f8f9fa;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .badge-type-general { background-color: #6c757d; }
        .badge-type-nonac { background-color: #007bff; }
        .badge-type-ac { background-color: #17a2b8; }
        .badge-type-icu { background-color: #dc3545; }
        .action-form {
            display: inline-block;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Back to Dashboard Button -->
        <a href="admin-dashboard.php" class="btn btn-secondary dashboard-btn">Back to Dashboard</a>
        
        <h2 class="text-center mb-4">Bed Management</h2>
        
        <?php if (isset($message)) echo $message; ?>
        
        <!-- Link to add new bed -->
        <div class="text-end mb-3">
            <a href="bed-manage.php" class="btn btn-primary">Add New Bed</a>
        </div>
        
        <!-- Bed Availability Tables -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4>Bed Availability</h4>
            </div>
            <div class="card-body">
                <?php foreach ($beds_by_type as $type => $beds): ?>
                    <?php if (!empty($beds)): ?>
                        <div class="bed-table mb-4">
                            <h5>
                                <span class="badge 
                                    <?php 
                                        switch($type) {
                                            case 'General Bed': echo 'badge-type-general'; break;
                                            case 'Non-AC Cabin': echo 'badge-type-nonac'; break;
                                            case 'AC Cabin': echo 'badge-type-ac'; break;
                                            case 'ICU': echo 'badge-type-icu'; break;
                                        }
                                    ?>">
                                    <?php echo $type; ?>
                                </span>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bed ID</th>
                                            <th>Status</th>
                                            <th>Rate/Day</th>
                                            <th>Assigned Nurse</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($beds as $bed): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bed["bed_id"]); ?></td>
                                                <td>
                                                    <span class="status-<?php echo strtolower($bed["status"]); ?>">
                                                        <?php echo htmlspecialchars($bed["status"]); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($bed["rate_per_day"]): ?>
                                                        ₹<?php echo number_format($bed["rate_per_day"], 2); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($bed["nurse_name"]): ?>
                                                        <?php echo htmlspecialchars($bed["nurse_name"]); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <form method="post" class="action-form">
                                                            <input type="hidden" name="bed_id" value="<?php echo htmlspecialchars($bed["bed_id"]); ?>">
                                                            <select class="form-select form-select-sm" name="new_status" onchange="this.form.submit()">
                                                                <option value="Available" <?php echo $bed["status"] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                                                <option value="Occupied" <?php echo $bed["status"] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                                                                <option value="Maintenance" <?php echo $bed["status"] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                                <option value="Reserved" <?php echo $bed["status"] == 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                                                            </select>
                                                            <input type="hidden" name="update_bed">
                                                        </form>
                                                        
                                                        <form method="post" class="action-form">
                                                            <input type="hidden" name="bed_id" value="<?php echo htmlspecialchars($bed["bed_id"]); ?>">
                                                            <select class="form-select form-select-sm" name="user_id" onchange="this.form.submit()">
                                                                <option value="">-- Select Nurse --</option>
                                                                <?php foreach ($nurses as $id => $name): ?>
                                                                    <option value="<?php echo htmlspecialchars($id); ?>" <?php echo $bed["assigned_nurse_id"] == $id ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($name); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                                <option value="">-- Remove Nurse --</option>
                                                            </select>
                                                            <input type="hidden" name="assign_nurse">
                                                        </form>
                                                        
                                                        <form method="post" class="action-form">
                                                            <input type="hidden" name="bed_id" value="<?php echo htmlspecialchars($bed["bed_id"]); ?>">
                                                            <button type="submit" name="delete_bed" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this bed?')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($result->num_rows == 0): ?>
                    <div class="alert alert-warning">No beds found in the system. <a href="bed-manage.php" class="alert-link">Add a new bed</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>