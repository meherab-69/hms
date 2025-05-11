<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Receptionist Dashboard</title>

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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            border-radius: 10px;
            padding: 25px 20px;
            text-align: center;
            color: white;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .action-card i {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .action-card h5 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .action-card p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
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
            <a href="receptionist-dashboard.php" class="menu-item active">
                <div class="menu-icon"><i class="fas fa-tachometer-alt"></i></div>
                <div>Dashboard</div>
            </a>
            <a href="register-patient.php" class="menu-item">
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
            <h1 class="page-title">Receptionist Dashboard</h1>
            <p class="page-subtitle">MediVance Hospital Management System</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="register-patient.php" class="action-card" style="background-color: #28a745;">
                <i class="fas fa-user-plus"></i>
                <h5>Register Patient</h5>
                <p>Add new patient to the system</p>
            </a>

            <a href="admission.php" class="action-card" style="background-color: #17a2b8;">
                <i class="fas fa-procedures"></i>
                <h5>Admit Patient</h5>
                <p>Process patient admission</p>
            </a>

            <a href="make_appointment.php" class="action-card" style="background-color: #ffc107;">
                <i class="fas fa-calendar-check"></i>
                <h5>Make Appointment</h5>
                <p>Schedule doctor appointments</p>
            </a>

            <a href="bill_list.php" class="action-card" style="background-color: #6f42c1;">
                <i class="fas fa-file-invoice-dollar"></i>
                <h5>Billing</h5>
                <p>Generate and manage bills</p>
            </a>
        </div>

        <!-- Recent Activities Card -->
        <div class="card">
            <div class="card-header">Recent Activities</div>
            <div class="card-body">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h5>Activity Log Coming Soon</h5>
                    <p>We're working on displaying your recent activities here</p>
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
    </script>
</body>
</html>
