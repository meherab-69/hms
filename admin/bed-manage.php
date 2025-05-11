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

// Handle form submission for adding new bed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_bed"])) {
    // Add new bed
    $bed_type = $conn->real_escape_string($_POST["bed_type"]);
    $status = $conn->real_escape_string($_POST["status"]);
    $rate = $conn->real_escape_string($_POST["rate_per_day"]);
    $assigned_nurse = isset($_POST["assigned_nurse"]) ? $conn->real_escape_string($_POST["assigned_nurse"]) : null;
    
    $sql = "INSERT INTO beds (bed_type, status, rate_per_day, assigned_nurse_id)
            VALUES ('$bed_type', '$status', '$rate', " . ($assigned_nurse ? "'$assigned_nurse'" : "NULL") . ")";
    
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-success'>Bed added successfully! Bed ID: " . $conn->insert_id . "</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Bed - HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Back to Dashboard Button -->
        <a href="admin-dashboard.php" class="btn btn-secondary dashboard-btn">Back to Dashboard</a>
        
        <h2 class="text-center mb-4">Add New Bed</h2>
        
        <?php if (isset($message)) echo $message; ?>
        
        <!-- Add New Bed Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Add New Bed</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="bed_type">Bed Type</label>
                                <select class="form-control" id="bed_type" name="bed_type" required>
                                    <option value="General Bed">General Bed</option>
                                    <option value="Non-AC Cabin">Non-AC Cabin</option>
                                    <option value="AC Cabin">AC Cabin</option>
                                    <option value="ICU">ICU</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Occupied">Occupied</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Reserved">Reserved</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="rate_per_day">Rate/Day (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="rate_per_day" name="rate_per_day">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="assigned_nurse">Assign Nurse</label>
                                <select class="form-control" id="assigned_nurse" name="assigned_nurse">
                                    <option value="">-- Select Nurse --</option>
                                    <?php foreach ($nurses as $id => $name): ?>
                                        <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <button type="submit" name="add_bed" class="btn btn-success">Add Bed</button>
                            <a href="beds.php" class="btn btn-info">View All Beds</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>