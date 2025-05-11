<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();


$required_vars = ['doctor_id', 'firstname', 'lastname', 'speciality', 'qualification', 'user_type'];
foreach ($required_vars as $var) {
    if (!isset($_SESSION[$var])) {
        header("Location: login-doctor.php");
        exit();
    }
}


$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$doctor_id = $_SESSION['doctor_id'];
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$speciality = $_SESSION['speciality'];
$qualification = $_SESSION['qualification'];
$user_type = $_SESSION['user_type'];

$extra_info = null;

if ($user_type === 'Permanent') {
    $perm_query = $conn->prepare("SELECT salary, consultation_fee FROM permanent_doctors WHERE user_id = ?");
    $perm_query->bind_param("s", $doctor_id);
    $perm_query->execute();
    $perm_result = $perm_query->get_result();
    if ($perm_row = $perm_result->fetch_assoc()) {
        $extra_info = $perm_row;
    }
}
elseif ($user_type === 'Temporary') {
    $stmt = $conn->prepare("SELECT  consultation_fee, contract_end_date FROM visiting_doctors WHERE user_id = ?");
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    $extra_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
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
        
        .menu-item:hover, .menu-item.active {
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
            border-left: 4px solid var(--primary-color);
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
        
        /* Profile Info */
        .profile-info p {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--dark-color);
            display: inline-block;
            width: 150px;
        }
        
        /* Responsive */
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
            <h4>Doctor Panel</h4>
        </div>
        <div class="sidebar-menu">
            <a href="#" class="menu-item active">
                <div class="menu-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div>Dashboard</div>
            </a>

            <a href="appoinment.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>Appointments</div>
            </a>

            <a href="logout.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div>Logout</div>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Welcome, Dr. <?= htmlspecialchars($firstname . ' ' . $lastname) ?></h1>
            
        </div>

        <!-- Profile Card -->
        <div class="card">
            <div class="card-header">
                Your Profile Information
            </div>
            <div class="card-body">
                <div class="row profile-info">
                    <div class="col-md-6">
                        <p><span class="info-label">First Name:</span> <?= htmlspecialchars($firstname) ?></p>
                        <p><span class="info-label">Last Name:</span> <?= htmlspecialchars($lastname) ?></p>
                        <p><span class="info-label">Speciality:</span> <?= htmlspecialchars($speciality) ?></p>
                        <p><span class="info-label">User Type:</span> <?= htmlspecialchars($user_type) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><span class="info-label">Qualification:</span> <?= htmlspecialchars($qualification) ?></p>
                        <?php if ($user_type === 'Permanent' && $extra_info): ?>
                            <p><span class="info-label">Salary:</span> $<?= number_format($extra_info['salary'], 2) ?></p>
                            <p><span class="info-label">Consultation Fee:</span> $<?= number_format($extra_info['consultation_fee'], 2) ?></p>
                        <?php elseif ($user_type === 'Temporary' && $extra_info): ?>
                            <p><span class="info-label">Consultation Fee:</span> $<?= number_format($extra_info['consultation_fee'], 2) ?></p>
                            <p><span class="info-label">Contract End Date:</span> <?= date('F j, Y', strtotime($extra_info['contract_end_date'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        }
    </script>
</body>
</html>