<?php
session_start();
if (!isset($_SESSION['recep_id'])) {
    header("Location: login-receptionist.php");
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

// Initialize variables
$message = "";
$is_existing = false;

// Check if patient exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_patient"])) {
    $search_mobile = $conn->real_escape_string($_POST["search_mobile"]);
    $search_first_name = $conn->real_escape_string($_POST["search_first_name"]);
    $search_last_name = $conn->real_escape_string($_POST["search_last_name"]);
    
    $sql = "SELECT * FROM patient WHERE 
            (mobile = '$search_mobile' OR secondary_mobile = '$search_mobile')
            AND first_name = '$search_first_name'
            AND last_name = '$search_last_name'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-success alert-dismissible fade show'>Patient found in our records. <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        $is_existing = true;
    } else {
        $message = "<div class='alert alert-warning alert-dismissible fade show'>No patient found with these details. <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}

// Process registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register_patient"])) {
    $conn->begin_transaction();
    
    try {
        // Basic patient info
        $first_name = $conn->real_escape_string($_POST["first_name"]);
        $last_name = $conn->real_escape_string($_POST["last_name"]);
        $age = $conn->real_escape_string($_POST["age"]);
        $gender = $conn->real_escape_string($_POST["gender"]);
        $mobile = $conn->real_escape_string($_POST["mobile"]);
        $secondary_mobile = $conn->real_escape_string($_POST["secondary_mobile"]);
        $present_address = $conn->real_escape_string($_POST["present_address"]);
        $permanent_address = $conn->real_escape_string($_POST["permanent_address"]);
        
        // Insert patient
        $sql = "INSERT INTO patient (
                first_name, last_name, age, gender, mobile, 
                secondary_mobile, present_address, permanent_address
            ) VALUES (
                '$first_name', '$last_name', '$age', '$gender', '$mobile',
                '$secondary_mobile', '$present_address', '$permanent_address'
            )";
        
        if (!$conn->query($sql)) {
            throw new Exception("Patient registration failed: " . $conn->error);
        }
        
        $patient_id = $conn->insert_id;
        $conn->commit();
        $message = "<div class='alert alert-success alert-dismissible fade show'>Patient registered successfully! ID: $patient_id <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger alert-dismissible fade show'>Error: " . $e->getMessage() . " <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register Patient | MediVance HMS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #ff4d5e;
            --background-color: #f8f9fa;
            --light-color: #ffffff;
            --dark-color: #212529;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--light-color);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
        }

        .sidebar-header h4 {
            margin-bottom: 0;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: rgba(220, 53, 69, 0.1);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }

        .menu-icon {
            width: 24px;
            margin-right: 12px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background-color: var(--light-color);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .card-body {
            padding: 20px;
        }

        /* Form Styles */
        .required-field::after {
            content: " *";
            color: var(--primary-color);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .search-card {
            border-left: 4px solid #17a2b8;
        }
        
        .register-card {
            border-left: 4px solid #28a745;
        }

        /* Responsive Sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>Receptionist Panel</h4>
        </div>
        <div class="sidebar-menu">
            <a href="receptionist-dashboard.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-tachometer-alt"></i></div>
                <div>Dashboard</div>
            </a>
            <a href="register-patient.php" class="menu-item active">
                <div class="menu-icon"><i class="fas fa-user-plus"></i></div>
                <div>Register Patient</div>
            </a>
            <a href="admission.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-procedures"></i></div>
                <div>Admit Patient</div>
            </a>
            <a href="make_appointment.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                <div>Make Appointment</div>
            </a>
            <a href="bill_list.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>Billing</div>
            </a>
            <a href="logout.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                <div>Logout</div>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Toggle Sidebar Button for Mobile -->
        <button class="btn btn-outline-danger d-lg-none mb-3" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="page-header">
            <h1 class="page-title">Patient Registration</h1>
            <p class="page-subtitle">MediVance Hospital Management System</p>
        </div>

        <?php echo $message; ?>

        <!-- Existing Patient Check -->
        <div class="card search-card mb-4">
            <div class="card-header">
                <h4><i class="fas fa-search me-2"></i>Check Existing Patient</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required-field">First Name</label>
                            <input type="text" class="form-control" name="search_first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required-field">Last Name</label>
                            <input type="text" class="form-control" name="search_last_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required-field">Mobile Number</label>
                            <input type="text" class="form-control" name="search_mobile" required>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" name="check_patient" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Check Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="card register-card">
            <div class="card-header">
                <h4><i class="fas fa-user-plus me-2"></i>Register New Patient</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required-field">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="last_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required-field">Age</label>
                            <input type="number" class="form-control" name="age" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required-field">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required-field">Mobile</label>
                            <input type="text" class="form-control" name="mobile" id="mobile" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Secondary Mobile</label>
                            <input type="text" class="form-control" name="secondary_mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Present Address</label>
                            <textarea class="form-control" name="present_address" id="present_address" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Permanent Address</label>
                            <textarea class="form-control" name="permanent_address" id="permanent_address" rows="3"></textarea>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="same_as_present">
                                <label class="form-check-label" for="same_as_present">Same as present address</label>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" name="register_patient" class="btn btn-success btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Register Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        }
        
        document.getElementById('same_as_present').addEventListener('change', function () {
            document.getElementById('permanent_address').value = this.checked ? 
                document.getElementById('present_address').value : '';
        });

        <?php if ($is_existing): ?>
            document.getElementById('first_name').value = '<?php echo isset($_POST["search_first_name"]) ? htmlspecialchars($_POST["search_first_name"]) : "" ?>';
            document.getElementById('last_name').value = '<?php echo isset($_POST["search_last_name"]) ? htmlspecialchars($_POST["search_last_name"]) : "" ?>';
            document.getElementById('mobile').value = '<?php echo isset($_POST["search_mobile"]) ? htmlspecialchars($_POST["search_mobile"]) : "" ?>';
        <?php endif; ?>
    </script>
</body>
</html>