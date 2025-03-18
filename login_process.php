<?php
session_start();
include 'includes/db_connection.php';

// Get form data
$email = $_POST['email'];
$password = $_POST['password'];

// Validate input
if (empty($email) || empty($password)) {
    header("Location: login.php?error=emptyfields");
    exit();
}

// Query the database for the admin
$sql = "SELECT * FROM admins WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind the email parameter
    $stmt->bind_param("s", $email);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if a row was returned
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $admin['password'])) {
            // Start a session and store admin data
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['name'] = $admin['name'];

            // Redirect to the admin dashboard
            header("Location: admin/dashboard.php");
            exit();
        } else {
            // Invalid password
            header("Location: login.php?error=invalidcredentials");
            exit();
        }
    } else {
        // No user found with the given email
        header("Location: login.php?error=invalidcredentials");
        exit();
    }

    // Close the statement
    $stmt->close();
} else {
    // Handle SQL statement preparation error
    die("Error preparing statement: " . $conn->error);
}

// Close the database connection
$conn->close();
?>