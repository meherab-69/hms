<?php
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

// Fetch doctor data
$doctor_query = "SELECT d.*, 
                IFNULL(p.salary, 'N/A') AS salary, 
                IFNULL(p.consultation_fee, v.consultation_fee) AS consultation_fee,
                IFNULL(v.contract_end_date, 'N/A') AS contract_end_date
                FROM doctors d
                LEFT JOIN permanent_doctors p ON d.doctor_id = p.user_id
                LEFT JOIN visiting_doctors v ON d.doctor_id = v.user_id
                WHERE d.doctor_id = ?";

$stmt = $conn->prepare($doctor_query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    $_SESSION['error_message'] = "Doctor not found";
    header("Location: doctors_list.php");
    exit();
}

// Decode contact information
$contact = json_decode($doctor['contact'], true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $speciality = $_POST['speciality'];
    $qualification = $_POST['qualification'];
    $user_type = $_POST['user_type'];
    $working_days = implode(',', $_POST['working_days']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $appointment_duration = $_POST['appointment_duration'];
    
    // Contact information
    $contact = json_encode([
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address']
    ]);
    
    try {
        // Update doctors table
        $update_query = "UPDATE doctors SET 
                        firstname = ?, 
                        lastname = ?, 
                        contact = ?, 
                        speciality = ?, 
                        qualification = ?, 
                        user_type = ?, 
                        working_days = ?, 
                        start_time = ?, 
                        end_time = ?, 
                        appointment_duration = ?
                        WHERE doctor_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssssssis", 
            $firstname, $lastname, $contact, $speciality, $qualification, 
            $user_type, $working_days, $start_time, $end_time, 
            $appointment_duration, $doctor_id);
        $stmt->execute();
        
        // Update specific doctor type table
        if ($user_type == 'Permanent') {
            $salary = $_POST['salary'];
            $consultation_fee = $_POST['consultation_fee'];
            
            // Check if record exists
            $check_query = "SELECT * FROM permanent_doctors WHERE user_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $update_query = "UPDATE permanent_doctors SET 
                                salary = ?, 
                                consultation_fee = ? 
                                WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("dds", $salary, $consultation_fee, $doctor_id);
            } else {
                // Insert new record
                $update_query = "INSERT INTO permanent_doctors 
                                (user_id, salary, consultation_fee) 
                                VALUES (?, ?, ?)";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sdd", $doctor_id, $salary, $consultation_fee);
                
                // Delete from visiting_doctors if exists
                $delete_query = "DELETE FROM visiting_doctors WHERE user_id = ?";
                $del_stmt = $conn->prepare($delete_query);
                $del_stmt->bind_param("s", $doctor_id);
                $del_stmt->execute();
            }
            $stmt->execute();
        } else {
            $consultation_fee = $_POST['consultation_fee'];
            $contract_end_date = $_POST['contract_end_date'];
            
            // Check if record exists
            $check_query = "SELECT * FROM visiting_doctors WHERE user_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $update_query = "UPDATE visiting_doctors SET 
                                consultation_fee = ?, 
                                contract_end_date = ? 
                                WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("dss", $consultation_fee, $contract_end_date, $doctor_id);
            } else {
                // Insert new record
                $update_query = "INSERT INTO visiting_doctors 
                                (user_id, consultation_fee, contract_end_date) 
                                VALUES (?, ?, ?)";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sds", $doctor_id, $consultation_fee, $contract_end_date);
                
                // Delete from permanent_doctors if exists
                $delete_query = "DELETE FROM permanent_doctors WHERE user_id = ?";
                $del_stmt = $conn->prepare($delete_query);
                $del_stmt->bind_param("s", $doctor_id);
                $del_stmt->execute();
            }
            $stmt->execute();
        }
        
        $_SESSION['success_message'] = "Doctor updated successfully!";
        header("Location: doctors_list.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating doctor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .day-checkbox {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0"><i class="fas fa-user-md me-2"></i>Edit Doctor</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="edit_doctor.php?id=<?php echo $doctor_id; ?>">
                            <!-- Basic Information Section -->
                            <div class="form-section active">
                                <h4 class="mb-4">Basic Information</h4>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="doctor_id" class="form-label">Doctor ID</label>
                                        <input type="text" class="form-control" id="user_id" value="<?php echo htmlspecialchars($doctor['user_id']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="user_type" class="form-label required-field">Doctor Type</label>
                                        <select class="form-select" id="user_type" name="user_type" required>
                                            <option value="Permanent" <?php echo $doctor['user_type'] == 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                            <option value="Temporary" <?php echo $doctor['user_type'] == 'Temporary' ? 'selected' : ''; ?>>Visiting</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="firstname" class="form-label required-field">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($doctor['firstname']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastname" class="form-label required-field">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($doctor['lastname']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="speciality" class="form-label required-field">Speciality</label>
                                        <input type="text" class="form-control" id="speciality" name="speciality" value="<?php echo htmlspecialchars($doctor['speciality']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="qualification" class="form-label required-field">Qualification</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="email" class="form-label required-field">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="phone" class="form-label required-field">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($contact['address'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label required-field">Working Days</label>
                                        <div class="d-flex flex-wrap">
                                            <?php 
                                            $days = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'];
                                            $working_days = explode(',', $doctor['working_days']);
                                            foreach ($days as $key => $day): ?>
                                                <div class="form-check day-checkbox">
                                                    <input class="form-check-input" type="checkbox" name="working_days[]" id="day<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $working_days) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="day<?php echo $key; ?>"><?php echo $day; ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="start_time" class="form-label required-field">Start Time</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($doctor['start_time']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_time" class="form-label required-field">End Time</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($doctor['end_time']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="appointment_duration" class="form-label required-field">Appointment Duration (minutes)</label>
                                        <input type="number" class="form-control" id="appointment_duration" name="appointment_duration" value="<?php echo htmlspecialchars($doctor['appointment_duration']); ?>" min="5" max="60" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Specific Information Section -->
                            <div id="permanentFields" style="display: <?php echo $doctor['user_type'] == 'Permanent' ? 'block' : 'none'; ?>;">
                                <h4 class="mb-4">Permanent Doctor Information</h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="salary" class="form-label required-field">Salary</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0" value="<?php echo htmlspecialchars($doctor['salary'] != 'N/A' ? $doctor['salary'] : ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="consultation_fee" class="form-label required-field">Consultation Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? 0); ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="visitingFields" style="display: <?php echo $doctor['user_type'] == 'Temporary' ? 'block' : 'none'; ?>;">
                                <h4 class="mb-4">Visiting Doctor Information</h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="v_consultation_fee" class="form-label required-field">Consultation Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="v_consultation_fee" name="consultation_fee" value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? 0); ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contract_end_date" class="form-label required-field">Contract End Date</label>
                                        <input type="date" class="form-control" id="contract_end_date" name="contract_end_date" value="<?php echo htmlspecialchars($doctor['contract_end_date'] != 'N/A' ? $doctor['contract_end_date'] : ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="doctors_list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Doctor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide specific fields based on doctor type
        document.getElementById('user_type').addEventListener('change', function() {
            const type = this.value;
            document.getElementById('permanentFields').style.display = type === 'Permanent' ? 'block' : 'none';
            document.getElementById('visitingFields').style.display = type === 'Temporary' ? 'block' : 'none';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
