<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: login-doctor.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$doctor_id = $_SESSION['doctor_id'];
$firstname = $_SESSION['firstname']; // Assuming these are set in the session
$lastname = $_SESSION['lastname'];

$all_appointments = [];
$appointments_query = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.status,
           p.patient_id, p.first_name, p.last_name, p.mobile, p.age, p.gender,
           (SELECT COUNT(*) FROM treatment WHERE patient_id = p.patient_id) AS treatment_count
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$appointments_query->bind_param("s", $doctor_id);
$appointments_query->execute();
$result = $appointments_query->get_result();
while ($row = $result->fetch_assoc()) $all_appointments[] = $row;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

        /* Appointments Card */
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

        .appointment-card {
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.1);
        }

        .patient-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .patient-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .appointment-meta {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-upcoming {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .badge-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .badge-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .badge-history {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
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

            <a href="appointments.php" class="menu-item active">
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

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Appointments</h1>
            <p class="page-subtitle">Manage your scheduled appointments</p>
        </div>

        <div class="card">
            <div class="card-header">
                All Appointments
            </div>
            <div class="card-body">
                <?php if (empty($all_appointments)): ?>
                    <div class="alert alert-info">No appointments found.</div>
                <?php else: ?>
                    <?php foreach ($all_appointments as $appointment): ?>
                        <div class="card appointment-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="patient-name">
                                            <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?>
                                        </h5>
                                        <div class="patient-meta">
                                            <?= $appointment['gender'] ?>, <?= $appointment['age'] ?> years
                                            <?php if ($appointment['treatment_count'] > 0): ?>
                                                • <?= $appointment['treatment_count'] ?> previous treatments
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge-status
                                            <?= $appointment['status'] == 'Completed' ? 'badge-completed' :
                                                ($appointment['status'] == 'Cancelled' ? 'badge-cancelled' : 'badge-upcoming') ?>">
                                            <?= htmlspecialchars($appointment['status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="appointment-meta">
                                    <strong>Date:</strong> <?= date('F j, Y', strtotime($appointment['appointment_date'])) ?>
                                    <strong class="ms-3">Time:</strong> <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                </div>

                                <p class="appointment-meta"><strong>Reason:</strong> <?= htmlspecialchars($appointment['reason']) ?></p>
                                <p class="appointment-meta"><strong>Contact:</strong> <?= htmlspecialchars($appointment['mobile']) ?></p>

                                <div class="mt-3">
                                    <a href="treatment.php?appointment_id=<?= $appointment['appointment_id'] ?>&patient_id=<?= $appointment['patient_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-stethoscope me-1"></i> Treat Patient
                                    </a>

                                    </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js"></script>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        }
    </script>
</body>
</html>