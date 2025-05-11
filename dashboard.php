<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Welcome, <?php echo $_SESSION['username']; ?></h1>
        <h3>Your Role: <?php echo $role; ?></h3>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <?php if ($role == 'admin'): ?>
            <p>You have admin privileges.</p>
            <!-- Admin-specific content goes here -->
        <?php elseif ($role == 'receptionist'): ?>
            <p>You are logged in as a receptionist.</p>
            <!-- Receptionist-specific content goes here -->
        <?php elseif ($role == 'nurse'): ?>
            <p>You are logged in as a nurse.</p>
            <!-- Nurse-specific content goes here -->
        <?php elseif ($role == 'doctor'): ?>
            <p>You are logged in as a doctor.</p>
            <!-- Doctor-specific content goes here -->
        <?php endif; ?>
    </div>
</body>
</html>
