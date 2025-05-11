<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

$error = '';
$success = '';

// Get receptionist data if ID is provided
$receptionist = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT user_id, firstname, lastname, contact, shift FROM receptionists WHERE user_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receptionist = $result->fetch_assoc();
    
    if ($receptionist) {
        $contact = json_decode($receptionist['contact'], true);
        $receptionist['email'] = $contact['email'] ?? '';
        $receptionist['phone'] = $contact['phone'] ?? '';
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $shift = $_POST['shift'];

    // Prepare contact info as JSON
    $contact = json_encode(['email' => $email, 'phone' => $phone]);

    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE receptionists SET firstname=?, lastname=?, contact=?, shift=?, password=? WHERE user_id=?");
        $stmt->bind_param("ssssss", $firstname, $lastname, $contact, $shift, $password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE receptionists SET firstname=?, lastname=?, contact=?, shift=? WHERE user_id=?");
        $stmt->bind_param("sssss", $firstname, $lastname, $contact, $shift, $user_id);
    }

    if ($stmt->execute()) {
        $success = "Receptionist updated successfully!";
        // Refresh the data
        $receptionist = [
            'user_id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'phone' => $phone,
            'shift' => $shift
        ];
    } else {
        $error = "Error updating receptionist: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Receptionist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="fas fa-user-edit me-2"></i>Edit Receptionist</h1>
            <p>Hospital Management System - Admin Panel</p>
        </div>Opivhai
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="receptionist_list.php">Receptionists</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Edit Receptionist Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$receptionist): ?>
                            <div class="alert alert-danger">Receptionist not found!</div>
                            <a href="receptionist_list.php" class="btn btn-secondary">Back to List</a>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($receptionist['user_id']); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="user_id" class="form-label">Staff ID</label>
                                        <input type="text" class="form-control" id="user_id" value="<?php echo htmlspecialchars($receptionist['user_id']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="shift" class="form-label">Shift</label>
                                        <select class="form-select" id="shift" name="shift" required>
                                            <option value="Morning" <?php echo ($receptionist['shift'] == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                                            <option value="Afternoon" <?php echo ($receptionist['shift'] == 'Afternoon') ? 'selected' : ''; ?>>Afternoon</option>
                                            <option value="Night" <?php echo ($receptionist['shift'] == 'Night') ? 'selected' : ''; ?>>Night</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="firstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($receptionist['firstname']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($receptionist['lastname']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($receptionist['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($receptionist['phone']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary me-2">Update Receptionist</button>
                                        <a href="receptionist_list.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>