<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['nurse_id']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT user_id, firstname, lastname, contact, shift, status, password FROM nurses WHERE user_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['firstname'] = $row['firstname'];
                $_SESSION['lastname'] = $row['lastname'];
                $_SESSION['contact'] = $row['contact'];
                $_SESSION['shift'] = $row['shift'];
                
                // Redirect to the nurse's dashboard
                header("Location: nurse-dashboard.php");
                exit();
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "Invalid User ID";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Login</title>
    
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
        
        /* Side Menu */
        .side-menu {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        
        .menu-item {
            background-color: var(--background-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        
        .menu-icon {
            width: 30px;
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        /* Login Form */
        .login-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .error {
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
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
                        <p>Nurse Portal</p>
                        
                        <!-- Side Menu -->
                        <div class="side-menu">
                            <a href="/hms/index.php" class="menu-item">
                                <div class="menu-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div>Home</div>
                            </a>
                            <a href="#" class="menu-item">
                                <div class="menu-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div>About</div>
                            </a>
                            <a href="#" class="menu-item">
                                <div class="menu-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>Contact</div>
                            </a>
                        </div>
                    </div>
                    
                    <div class="system-info">
                        <h1 class="system-title">Nurse Login</h1>
                        <p class="system-subtitle">Please enter your credentials to access the nurse portal</p>
                        
                        <?php if (isset($error)): ?>
                            <div class="error"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                            <div class="form-group">
                                <label for="nurse_id">User ID</label>
                                <input type="text" class="form-control" id="nurse_id" name="nurse_id" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
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