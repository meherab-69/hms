<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

// Get nurse ID from URL
$nurse_id = $_GET['id'] ?? '';

if ($nurse_id) {
    // Delete query
    $query = "DELETE FROM nurses WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $nurse_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Nurse deleted successfully";
    } else {
        $_SESSION['error_message'] = "Error deleting nurse: " . $conn->error;
    }
    
    $stmt->close();
}

// Redirect back to nurse list
header("Location: nurse_list.php");
exit();
?>