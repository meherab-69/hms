<?php
session_start();
if (!isset($_SESSION['recep_id'])) {
    header("Location: login-receptionist.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

// Handle patient admission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['admit_patient'])) {
        $patient_id = $_POST['patient_id'];
        $bed_id = $_POST['bed_id'];
        $reason = $_POST['reason'];
        
        // Check if patient exists
        $patient_check = $conn->prepare("SELECT patient_id FROM patient WHERE patient_id = ?");
        $patient_check->bind_param("i", $patient_id);
        $patient_check->execute();
        $patient_check->store_result();
        
        if ($patient_check->num_rows == 0) {
            $error = "Patient ID does not exist.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert admission record
                $stmt = $conn->prepare("INSERT INTO admission (patient_id, bed_id, admission_date, reason, status) VALUES (?, ?, NOW(), ?, 'Admitted')");
                $stmt->bind_param("iis", $patient_id, $bed_id, $reason);
                $stmt->execute();
                
                // Update bed status to Occupied
                $update_bed = $conn->prepare("UPDATE beds SET status = 'Occupied' WHERE bed_id = ?");
                $update_bed->bind_param("i", $bed_id);
                $update_bed->execute();
                
                $conn->commit();
                $success = "Patient successfully admitted.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Admission failed: " . $e->getMessage();
            }
            
            $stmt->close();
            $update_bed->close();
        }
        $patient_check->close();
    }
    
    // Handle patient discharge
    if (isset($_POST['discharge_patient'])) {
        $admission_id = $_POST['admission_id'];
        $bed_id = $_POST['bed_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update admission record with discharge date and status
            $stmt = $conn->prepare("UPDATE admission SET discharge_date = NOW(), status = 'Discharged' WHERE admission_id = ?");
            $stmt->bind_param("i", $admission_id);
            $stmt->execute();
            
            // Update bed status back to Available
            $update_bed = $conn->prepare("UPDATE beds SET status = 'Available' WHERE bed_id = ?");
            $update_bed->bind_param("i", $bed_id);
            $update_bed->execute();
            
            $conn->commit();
            $success = "Patient successfully discharged.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Discharge failed: " . $e->getMessage();
        }
        
        $stmt->close();
        $update_bed->close();
    }
}

// Get available beds
$available_beds = [];
$bed_query = "SELECT bed_id, bed_type FROM beds WHERE status = 'Available'";
$bed_result = $conn->query($bed_query);
while ($row = $bed_result->fetch_assoc()) {
    $available_beds[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Patient Admission | MediVance HMS</title>

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
        
        .admit-card {
            border-left: 4px solid #17a2b8;
        }
        
        .admissions-card {
            border-left: 4px solid #28a745;
        }

        .badge-admitted {
            background-color: #28a745;
        }
        
        .badge-discharged {
            background-color: #6c757d;
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
            <a href="register-patient.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-user-plus"></i></div>
                <div>Register Patient</div>
            </a>
            <a href="admission.php" class="menu-item active">
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
            <h1 class="page-title">Patient Admission</h1>
            <p class="page-subtitle">MediVance Hospital Management System</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Admission Form -->
        <div class="card admit-card mb-4">
            <div class="card-header">
                <h4><i class="fas fa-procedures me-2"></i>Patient Admission Form</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="patient_id" class="form-label required-field">Patient ID</label>
                            <input type="number" class="form-control" id="patient_id" name="patient_id" required>
                            <small class="text-muted">Enter the patient's ID number</small>
                        </div>
                        <div class="col-md-6">
                            <label for="bed_id" class="form-label required-field">Select Bed</label>
                            <select class="form-select" id="bed_id" name="bed_id" required>
                                <option value="">-- Select Bed --</option>
                                <?php foreach ($available_beds as $bed): ?>
                                    <option value="<?= htmlspecialchars($bed['bed_id']) ?>">
                                        <?= htmlspecialchars($bed['bed_type'] . " (ID: " . $bed['bed_id'] . ")") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="reason" class="form-label required-field">Reason for Admission</label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">

                        <button type="submit" name="admit_patient" class="btn btn-primary">
                            <i class="fas fa-procedures me-2"></i>Admit Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Admissions Table -->
        <div class="card admissions-card">
            <div class="card-header">
                <h4><i class="fas fa-list me-2"></i>Current Admissions</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Admission ID</th>
                                <th>Patient ID</th>
                                <th>Bed ID</th>
                                <th>Admission Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admissions_query = "SELECT a.admission_id, a.patient_id, a.bed_id, a.admission_date, a.discharge_date, a.status 
                                                 FROM admission a 
                                                 ORDER BY a.admission_date DESC";
                            $admissions_result = $conn->query($admissions_query);
                            
                            if ($admissions_result->num_rows > 0) {
                                while ($row = $admissions_result->fetch_assoc()) {
                                    $status_class = ($row['status'] == 'Admitted') ? 'badge-admitted' : 'badge-discharged';
                                    echo "<tr>
                                            <td>{$row['admission_id']}</td>
                                            <td>{$row['patient_id']}</td>
                                            <td>{$row['bed_id']}</td>
                                            <td>" . date('M d, Y H:i', strtotime($row['admission_date'])) . "</td>
                                            <td><span class='badge {$status_class}'>{$row['status']}</span></td>
                                            <td>";
                                    
                                    if ($row['status'] == 'Admitted') {
                                        echo "<form method='POST' action='' class='d-inline'>
                                                <input type='hidden' name='admission_id' value='{$row['admission_id']}'>
                                                <input type='hidden' name='bed_id' value='{$row['bed_id']}'>
                                                <button type='submit' name='discharge_patient' class='btn btn-sm btn-danger'>
                                                    <i class='fas fa-sign-out-alt me-1'></i>Discharge
                                                </button>
                                              </form>";
                                    } else {
                                        echo "<span class='text-muted'>" . date('M d, Y H:i', strtotime($row['discharge_date'])) . "</span>";
                                    }
                                    
                                    echo "</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-4'>No admissions found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
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
        
        // Confirm before discharging a patient
        document.querySelectorAll('button[name="discharge_patient"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to discharge this patient?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>