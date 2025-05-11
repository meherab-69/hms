<!-- Include this full version of nurse-dashboard.php -->

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login-nurse.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$nurse_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, contact, shift, status FROM nurses WHERE user_id = ?");
$stmt->bind_param("s", $nurse_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    session_destroy();
    header("Location: login-nurse.php");
    exit();
}
$nurse = $result->fetch_assoc();
$stmt->close();

$contact = json_decode($nurse['contact'], true) ?? [];
$phone = $contact['phone'] ?? 'Not provided';
$email = $contact['email'] ?? 'Not provided';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] == 'active' ? 'active' : 'inactive';
    $stmt = $conn->prepare("UPDATE nurses SET status = ? WHERE user_id = ?");
    $stmt->bind_param("ss", $new_status, $nurse_id);
    if ($stmt->execute()) {
        $_SESSION['status'] = $new_status;
        $nurse['status'] = $new_status;
    }
    $stmt->close();
}

$assigned_beds = [];
$stmt = $conn->prepare("SELECT b.bed_id, b.bed_type, b.status as bed_status, b.rate_per_day 
                       FROM beds b WHERE b.assigned_nurse_id = ?");
$stmt->bind_param("s", $nurse_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $assigned_beds[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nurse Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border-radius: 12px;
        }
        .status-available { color: #198754; }
        .status-occupied { color: #dc3545; }
        .status-maintenance { color: #ffc107; font-weight: 600; }
        .status-reserved { color: #0dcaf0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">HMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nurseNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nurseNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="nurse-dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="patient-list.php">Patients</a></li>
                <li class="nav-item"><a class="nav-link" href="shift-schedule.php">Schedule</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <?php echo htmlspecialchars($nurse['firstname']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="update-profile.php">Update Profile</a></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Nurse Info Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h3 class="card-title">
                <?php echo htmlspecialchars($nurse['firstname'] . ' ' . $nurse['lastname']); ?>
                <span class="badge bg-<?php echo $nurse['status'] == 'active' ? 'success' : 'danger'; ?>">
                    <?php echo ucfirst($nurse['status']); ?>
                </span>
            </h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($nurse_id); ?></p>
            <p><strong>Shift:</strong> <?php echo htmlspecialchars($nurse['shift'] ?? 'Not assigned'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?> |
               <strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <form method="post" class="d-flex align-items-center gap-2">
                <select name="status" class="form-select w-auto">
                    <option value="active" <?php echo $nurse['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $nurse['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>

    <!-- Assigned Beds -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Assigned Beds</div>
        <div class="card-body">
            <?php if (empty($assigned_beds)): ?>
                <p class="text-muted">No beds assigned currently.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($assigned_beds as $bed): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-start border-primary border-4 h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Bed #<?php echo $bed['bed_id']; ?></h5>
                                    <p class="mb-1"><strong>Type:</strong> <?php echo $bed['bed_type']; ?></p>
                                    <p class="mb-1"><strong>Status:</strong>
                                        <span class="status-<?php echo strtolower($bed['bed_status']); ?>">
                                            <?php echo $bed['bed_status']; ?>
                                        </span>
                                    </p>
                                    <p><strong>Rate:</strong> ₹<?php echo number_format($bed['rate_per_day'], 2); ?>/day</p>
                                </div>
                                <div class="card-footer text-end bg-transparent border-0">
                                    <a href="bed-details.php?id=<?php echo $bed['bed_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
