<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "hms_user", "Opivhai@123", "hms");

// Check if ID is provided
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Delete the receptionist
    $stmt = $conn->prepare("DELETE FROM receptionists WHERE user_id = ?");
    $stmt->bind_param("s", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Receptionist deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting receptionist: " . $stmt->error;
    }
    $stmt->close();
}

// Redirect back to the list
header("Location: receptionist_list.php");
exit();
?>