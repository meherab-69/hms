<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 0;
        }
        
        .system-card {
            background-color: var(--light-color);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }
        
        .system-row {
            display: flex;
            flex-wrap: wrap;
        }
        
        .system-image {
            flex: 1;
            min-height: 300px;
            background: linear-gradient(rgba(220, 53, 69, 0.7), rgba(220, 53, 69, 0.9));
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
        }
        
        .system-info {
            flex: 1;
            padding: 40px;
        }
        
        .system-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .system-subtitle {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        .login-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .login-option {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-color);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .login-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            background-color: #e9ecef;
        }
        
        .login-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .login-label {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Footer */
        footer {
            background-color: var(--light-color);
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .system-row {
                flex-direction: column;
            }
            
            .system-image {
                min-height: 200px;
            }
        }
        
        @media (max-width: 575.98px) {
            .login-options {
                grid-template-columns: 1fr;
            }
            
            .system-info, .system-image {
                padding: 30px;
            }
            
            .system-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <div class="system-card">
                <div class="system-row">
                    <div class="system-image">
                        <h2>Hospital Management System</h2>
                        <p>Streamlining Healthcare Operations</p>
                    </div>
                    
                    <div class="system-info">
                        <h1 class="system-title">Welcome to HMS</h1>
                        <p class="system-subtitle">Access the hospital management system by selecting your role below:</p>
                        
                        <div class="login-options">
                            <a href="doctor/login-doctor.php" class="login-option">
                                <div class="login-icon">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="login-label">I am a Doctor</div>
                            </a>
                            
                            <a href="recep/login-receptionist.php" class="login-option">
                                <div class="login-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="login-label">I am a Receptionist</div>
                            </a>
                            
                            <a href="admin/login-admin.php" class="login-option">
                                <div class="login-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="login-label">I am an Admin</div>
                            </a>
                            
                            <a href="nurse/login-nurse.php" class="login-option">
                                <div class="login-icon">
                                    <i class="fas fa-user-nurse"></i>
                                </div>
                                <div class="login-label">I am a Nurse</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; All Rights Reserved By Team No. 06</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>