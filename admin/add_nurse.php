<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

// Initialize variables
$errors = [];
$success = false;
$nurseData = [
    'user_id' => '',
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'shift' => '',
    'password' => '',
    'confirm_password' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $nurseData['user_id'] = trim($_POST['user_id']);
    $nurseData['firstname'] = trim($_POST['firstname']);
    $nurseData['lastname'] = trim($_POST['lastname']);
    $nurseData['email'] = trim($_POST['email']);
    $nurseData['phone'] = trim($_POST['phone']);
    $nurseData['address'] = trim($_POST['address']);
    $nurseData['shift'] = trim($_POST['shift']);
    $nurseData['password'] = trim($_POST['password']);
    $nurseData['confirm_password'] = trim($_POST['confirm_password']);

    // Validation
    if (empty($nurseData['user_id'])) {
        $errors['user_id'] = "Nurse ID is required";
    }

    if (empty($nurseData['firstname'])) {
        $errors['firstname'] = "First name is required";
    }

    if (empty($nurseData['lastname'])) {
        $errors['lastname'] = "Last name is required";
    }

    if (empty($nurseData['email'])) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($nurseData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($nurseData['phone'])) {
        $errors['phone'] = "Phone number is required";
    }

    if (empty($nurseData['shift'])) {
        $errors['shift'] = "Shift is required";
    }

    if (empty($nurseData['password'])) {
        $errors['password'] = "Password is required";
    } elseif (strlen($nurseData['password']) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    }

    if ($nurseData['password'] !== $nurseData['confirm_password']) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    // Check if user_id or email already exists
    if (empty($errors)) {
        $checkQuery = "SELECT user_id FROM nurses WHERE user_id = ? OR JSON_EXTRACT(contact, '$.email') = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $nurseData['user_id'], $nurseData['email']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors['general'] = "Nurse ID or Email already exists";
        }
        $stmt->close();
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Prepare contact JSON
        $contact = json_encode([
            'email' => $nurseData['email'],
            'phone' => $nurseData['phone'],
            'address' => $nurseData['address']
        ]);

        // Hash password
        $hashedPassword = password_hash($nurseData['password'], PASSWORD_DEFAULT);

        // Insert query
        $insertQuery = "INSERT INTO nurses (user_id, firstname, lastname, contact, password, shift, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssss", 
            $nurseData['user_id'],
            $nurseData['firstname'],
            $nurseData['lastname'],
            $contact,
            $hashedPassword,
            $nurseData['shift']
        );

        if ($stmt->execute()) {
            $success = true;
            // Clear form on success
            $nurseData = array_fill_keys(array_keys($nurseData), '');
        } else {
            $errors['general'] = "Error saving nurse: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Nurse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .dashboard-header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-header text-center">
        <h1>Add New Nurse</h1>
        <p>Hospital Management System - Admin Panel</p>
    </div>

    <div class="container form-container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Nurse Information</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        Nurse added successfully!
                    </div>
                <?php elseif (isset($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_nurse.php">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="user_id" class="form-label">Nurse ID</label>
                            <input type="text" class="form-control <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>" 
                                   id="user_id" name="user_id" value="<?= htmlspecialchars($nurseData['user_id']) ?>" required>
                            <?php if (isset($errors['user_id'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['user_id']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="shift" class="form-label">Shift</label>
                            <select class="form-select <?= isset($errors['shift']) ? 'is-invalid' : '' ?>" 
                                    id="shift" name="shift" required>
                                <option value="">Select Shift</option>
                                <option value="Morning" <?= $nurseData['shift'] === 'Morning' ? 'selected' : '' ?>>Morning</option>
                                <option value="Evening" <?= $nurseData['shift'] === 'Evening' ? 'selected' : '' ?>>Evening</option>
                                <option value="Night" <?= $nurseData['shift'] === 'Night' ? 'selected' : '' ?>>Night</option>
                            </select>
                            <?php if (isset($errors['shift'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['shift']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>" 
                                   id="firstname" name="firstname" value="<?= htmlspecialchars($nurseData['firstname']) ?>" required>
                            <?php if (isset($errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['firstname']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?= isset($errors['lastname']) ? 'is-invalid' : '' ?>" 
                                   id="lastname" name="lastname" value="<?= htmlspecialchars($nurseData['lastname']) ?>" required>
                            <?php if (isset($errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['lastname']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= htmlspecialchars($nurseData['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['email']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?= htmlspecialchars($nurseData['phone']) ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?= htmlspecialchars($nurseData['address']) ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['confirm_password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="nurse_list.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Nurse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>