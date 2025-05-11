<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Authentication check
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login-doctor.php");
    exit();
}

// Parameter validation
if (!isset($_GET['appointment_id']) || !isset($_GET['patient_id'])) {
    header("Location: doctor-dashboard.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get doctor info
$doctor_id = (string)$_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT firstname, lastname FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$_SESSION['doctor_name'] = $doctor['firstname'] . ' ' . $doctor['lastname'];

// Get patient info
$patient_id = (int)$_GET['patient_id'];
$stmt = $conn->prepare("SELECT * FROM patient WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription']);

    if (empty($diagnosis) || empty($prescription)) {
        $error = "Both diagnosis and prescription are required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO treatment (
                appointment_id, patient_id, doctor_id, 
                treatment_name, diagnosis, prescription, 
                cost, treatment_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $appointment_id = (int)$_GET['appointment_id'];
        $treatment_name = "Consultation";
        $cost = 0.00;
        $treatment_date = date('Y-m-d');

        $stmt->bind_param(
            "iissssds",
            $appointment_id,
            $patient_id,
            $doctor_id,
            $treatment_name,
            $diagnosis,
            $prescription,
            $cost,
            $treatment_date
        );

        $conn->begin_transaction();
        try {
            $inserted = $stmt->execute();
            $updated = $conn->query("
                UPDATE appointment 
                SET status = 'Completed' 
                WHERE appointment_id = $appointment_id
            ");

            if ($inserted && $updated) {
                $conn->commit();
                $success = "Treatment recorded successfully!";
            } else {
                $conn->rollback();
                $error = "Failed to save treatment: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get treatment history
$stmt = $conn->prepare("
    SELECT t.*, d.firstname, d.lastname 
    FROM treatment t
    JOIN doctors d ON t.doctor_id = d.doctor_id
    WHERE t.patient_id = ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$treatment_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Treatment</title>
    
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
        
        /* Medical Fields */
        .medical-field {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
        }
        
        /* History Cards */
        .history-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .history-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.1);
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
            <a href="doctor-dashboard.php" class="menu-item">
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

            <a href="#" class="menu-item active">
                <div class="menu-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div>Treat Patient</div>
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
            <h1 class="page-title">Patient Treatment Record</h1>
            <p class="page-subtitle">Dr. <?= htmlspecialchars($_SESSION['doctor_name'] ?? '') ?></p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Patient Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Patient Information</h5>
            </div>
            <div class="card-body">
                <?php if ($patient): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></p>
                            <p><strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?></p>
                            <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Contact:</strong> <?= htmlspecialchars($patient['mobile']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($patient['present_address']) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-danger">Patient information not found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Treatment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">New Treatment Entry</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="5" required><?= htmlspecialchars($_POST['diagnosis'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="prescription" class="form-label">Prescription</label>
                        <textarea class="form-control" id="prescription" name="prescription" rows="5" required><?= htmlspecialchars($_POST['prescription'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Treatment
                            </button>
                            <a href="doctor-dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                        <div>
                            <span class="text-muted">Appointment ID: <?= htmlspecialchars($_GET['appointment_id']) ?></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Treatment History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Treatment History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($treatment_history)): ?>
                    <div class="alert alert-info">No treatment history found</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($treatment_history as $treatment): ?>
                            <div class="list-group-item history-card mb-3">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="fas fa-calendar-day me-2"></i>
                                        <?= date('M j, Y', strtotime($treatment['treatment_date'])) ?> - 
                                        <?= htmlspecialchars($treatment['treatment_name']) ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user-md me-1"></i>
                                        Dr. <?= htmlspecialchars($treatment['firstname'] . ' ' . $treatment['lastname']) ?>
                                    </small>
                                </div>

                                <div class="mt-3">
                                    <h6><i class="fas fa-diagnoses me-2"></i>Diagnosis:</h6>
                                    <div class="medical-field">
                                        <?= nl2br(htmlspecialchars($treatment['diagnosis'])) ?>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <h6><i class="fas fa-prescription-bottle-alt me-2"></i>Prescription:</h6>
                                    <div class="medical-field">
                                        <?= nl2br(htmlspecialchars($treatment['prescription'])) ?>
                                    </div>
                                </div>

                                <div class="mt-2 text-end">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Recorded: <?= date('M j, Y g:i A', strtotime($treatment['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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