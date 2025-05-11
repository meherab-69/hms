<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 20px;
            margin-bottom: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }
        
        .dashboard-header h1 {
            font-weight: 600;
            position: relative;
        }
        
        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }
        
        .card {
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            background-color: white;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            border-bottom: none;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .btn i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-warning {
            background-color: #f39c12;
            border-color: #f39c12;
            color: white;
        }
        
        .btn-info {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .logout-btn {
            padding: 10px 25px;
            font-size: 1rem;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(231, 76, 60, 0.2);
        }
        
        .welcome-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 15px;
            }
            
            .dashboard-header h1 {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header text-center">
        <div class="welcome-badge">
            <i class="fas fa-user-shield me-2"></i>Admin Access
        </div>
        <h1>Welcome, <?php echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname']; ?></h1>
        <p>Hospital Management System - Admin Panel</p>
    </div>

    <div class="container">
        <div class="row">
            <!-- Staff Management -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-users-cog me-2"></i>Staff Management</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <a href="doctors_list.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Doctors List
                        </a>
                        <a href="nurse_list.php" class="btn btn-primary">
                            <i class="fas fa-user-nurse"></i> Nurses List
                        </a>
                        <a href="receptionist_list.php" class="btn btn-primary">
                            <i class="fas fa-headset"></i> Receptionist List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bed Management -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-bed me-2"></i>Bed Management</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <a href="bed-manage.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Add Bed
                        </a>
                        <a href="remove-bed.php" class="btn btn-danger">
                            <i class="fas fa-minus-circle"></i> Remove Bed
                        </a>
                        <a href="beds.php" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Bed Status
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="fas fa-chart-line me-2"></i>Reports</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <a href="financial-report.php" class="btn btn-warning">
                            <i class="fas fa-money-bill-wave"></i> Financial Report
                        </a>
                        <a href="patient-report.php" class="btn btn-warning">
                            <i class="fas fa-user-injured"></i> Patient Report
                        </a>
                        <a href="staff-report.php" class="btn btn-warning">
                            <i class="fas fa-users"></i> Staff Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="logout-admin.php" class="btn btn-danger logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>