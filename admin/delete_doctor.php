<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$doctor_id) {
    $_SESSION['error_message'] = "No doctor ID specified";
    header("Location: doctors_list.php");
    exit();
}

// Check if doctor exists
$check_query = "SELECT * FROM doctors WHERE doctor_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "Doctor not found";
    header("Location: doctors_list.php");
    exit();
}

// Delete doctor and all related records
try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. First delete treatments related to this doctor's appointments
    $delete_treatments = "DELETE t FROM treatment t 
                         JOIN appointment a ON t.appointment_id = a.appointment_id 
                         WHERE a.doctor_id = ?";
    $stmt = $conn->prepare($delete_treatments);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // 2. Delete prescriptions related to this doctor's appointments
    $delete_prescriptions = "DELETE p FROM prescriptions p
                           JOIN appointment a ON p.appointment_id = a.appointment_id
                           WHERE a.doctor_id = ?";
    $stmt = $conn->prepare($delete_prescriptions);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // 3. Delete appointments for this doctor
    $delete_appointments = "DELETE FROM appointment WHERE doctor_id = ?";
    $stmt = $conn->prepare($delete_appointments);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // 4. Delete from doctor_specialties if exists
    $delete_specialties = "DELETE FROM doctor_specialties WHERE doctor_id = ?";
    $stmt = $conn->prepare($delete_specialties);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // 5. Delete from permanent_doctors or visiting_doctors
    $delete_permanent = "DELETE FROM permanent_doctors WHERE user_id = ?";
    $stmt = $conn->prepare($delete_permanent);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    $delete_visiting = "DELETE FROM visiting_doctors WHERE user_id = ?";
    $stmt = $conn->prepare($delete_visiting);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // 6. Finally delete from doctors table
    $delete_doctor = "DELETE FROM doctors WHERE doctor_id = ?";
    $stmt = $conn->prepare($delete_doctor);
    $stmt->bind_param("s", $doctor_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Doctor and all related records deleted successfully!";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting doctor: " . $e->getMessage();
}

$conn->close();
header("Location: doctors_list.php");
exit();
?>