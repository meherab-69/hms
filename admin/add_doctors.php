<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Database configuration
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required = ['user_id', 'firstname', 'lastname', 'speciality', 'qualification', 
                'password', 'user_type', 'email', 'phone'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error_message'] = "Please fill all required fields";
            header("Location: add_doctors.php");
            exit();
        }
    }
    
    // Check password match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error_message'] = "Passwords do not match";
        header("Location: add_doctors.php");
        exit();
    }
    
    // Check working days
    if (empty($_POST['working_days'])) {
        $_SESSION['error_message'] = "Please select at least one working day";
        header("Location: add_doctors.php");
        exit();
    }
    
    // Prepare data
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $speciality = $conn->real_escape_string($_POST['speciality']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $conn->real_escape_string($_POST['user_type']);
    $working_days = implode(',', $_POST['working_days']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $appointment_duration = (int)$_POST['appointment_duration'];
    
    // Contact information
    $contact = json_encode([
        'email' => $conn->real_escape_string($_POST['email']),
        'phone' => $conn->real_escape_string($_POST['phone']),
        'address' => $conn->real_escape_string($_POST['address'] ?? '')
    ]);
    
    // Start transaction
    $conn->begin_transaction();
    
    // In the form submission handling section:

try {
    // Insert into doctors table
    $stmt = $conn->prepare("INSERT INTO doctors (doctor_id, firstname, lastname, contact, speciality, qualification, password, user_type, working_days, start_time, end_time, appointment_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssi", $user_id, $firstname, $lastname, $contact, $speciality, $qualification, $password, $user_type, $working_days, $start_time, $end_time, $appointment_duration);
    
    if (!$stmt->execute()) {
        throw new Exception("Error inserting into doctors table: " . $stmt->error);
    }
    
    // Insert into specific doctor type table
    if ($user_type == 'Permanent') {
        if (empty($_POST['salary'])) {
            throw new Exception("Salary is required for permanent doctors");
        }
        
        $salary = (float)$_POST['salary'];
        $consultation_fee = (float)($_POST['consultation_fee'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO permanent_doctors (user_id, salary, consultation_fee) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $user_id, $salary, $consultation_fee);
    } else {
        if (empty($_POST['contract_end_date'])) {
            throw new Exception("Contract end date is required for visiting doctors");
        }
        
        $consultation_fee = (float)($_POST['consultation_fee'] ?? 0);
        $contract_end_date = $conn->real_escape_string($_POST['contract_end_date']);
        
        // Corrected table name and column name here
        $stmt = $conn->prepare("INSERT INTO visiting_doctors (user_id, consultation_fee, contract_end_date) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $user_id, $consultation_fee, $contract_end_date);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error inserting into doctor type table: " . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Doctor added successfully!";
    header("Location: add_doctors.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error adding doctor: " . $e->getMessage();
    header("Location: add_doctors.php");
    exit();
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor</title>
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
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .day-checkbox {
            margin-right: 10px;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            display: none;
        }
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0"><i class="fas fa-user-md me-2"></i>Add New Doctor</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="doctorForm" method="POST" action="add_doctors.php" novalidate>
                            <!-- Basic Information Section -->
                            <div class="form-section active" id="basicInfoSection">
                                <h4 class="mb-4">Basic Information</h4>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="user_id" class="form-label required-field">Doctor ID</label>
                                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                                        <div class="invalid-feedback">Please provide a doctor ID</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="user_type" class="form-label required-field">Doctor Type</label>
                                        <select class="form-select" id="user_type" name="user_type" required>
                                            <option value="">Select Type</option>
                                            <option value="Permanent">Permanent</option>
                                            <option value="Temporary">Visiting</option>
                                        </select>
                                        <div class="invalid-feedback">Please select doctor type</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="firstname" class="form-label required-field">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                                        <div class="invalid-feedback">Please provide first name</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastname" class="form-label required-field">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                                        <div class="invalid-feedback">Please provide last name</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="speciality" class="form-label required-field">Speciality</label>
                                        <input type="text" class="form-control" id="speciality" name="speciality" required>
                                        <div class="invalid-feedback">Please provide speciality</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="qualification" class="form-label required-field">Qualification</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification" required>
                                        <div class="invalid-feedback">Please provide qualification</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label required-field">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="invalid-feedback">Please provide password</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback">Passwords must match</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="email" class="form-label required-field">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback">Please provide valid email</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="phone" class="form-label required-field">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                        <div class="invalid-feedback">Please provide phone number</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label required-field">Working Days</label>
                                        <div class="d-flex flex-wrap">
                                            <?php 
                                            $days = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'];
                                            foreach ($days as $key => $day): ?>
                                                <div class="form-check day-checkbox">
                                                    <input class="form-check-input" type="checkbox" name="working_days[]" id="day<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo in_array($key, [1,2,3,4,5]) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="day<?php echo $key; ?>"><?php echo $day; ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="invalid-feedback">Please select at least one working day</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="start_time" class="form-label required-field">Start Time</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" value="09:00" required>
                                        <div class="invalid-feedback">Please provide start time</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_time" class="form-label required-field">End Time</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" value="17:00" required>
                                        <div class="invalid-feedback">Please provide end time</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="appointment_duration" class="form-label required-field">Appointment Duration (minutes)</label>
                                        <input type="number" class="form-control" id="appointment_duration" name="appointment_duration" value="10" min="5" max="60" required>
                                        <div class="invalid-feedback">Please provide duration (5-60 minutes)</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-primary next-btn" data-next="specificInfoSection">Next</button>
                                </div>
                            </div>
                            
                            <!-- Specific Information Section -->
                            <div class="form-section" id="specificInfoSection">
                                <h4 class="mb-4">Additional Information</h4>
                                
                                <!-- Permanent Doctor Fields -->
                                <div id="permanentFields" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="salary" class="form-label required-field">Salary</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0">
                                                <div class="invalid-feedback">Please provide salary</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="consultation_fee" class="form-label">Consultation Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" value="0.00" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Visiting Doctor Fields -->
                                <div id="visitingFields" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="v_consultation_fee" class="form-label">Consultation Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="v_consultation_fee" name="consultation_fee" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="contract_end_date" class="form-label required-field">Contract End Date</label>
                                            <input type="date" class="form-control" id="contract_end_date" name="contract_end_date">
                                            <div class="invalid-feedback">Please provide contract end date</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary prev-btn" data-prev="basicInfoSection">Previous</button>
                                    <button type="submit" class="btn btn-success">Add Doctor</button>
                                </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('doctorForm');
            const userTypeSelect = document.getElementById('user_type');
            const permanentFields = document.getElementById('permanentFields');
            const visitingFields = document.getElementById('visitingFields');
            
            // Show/hide specific fields based on doctor type
            userTypeSelect.addEventListener('change', function() {
                const type = this.value;
                permanentFields.style.display = type === 'Permanent' ? 'block' : 'none';
                visitingFields.style.display = type === 'Temporary' ? 'block' : 'none';
                
                // Set required fields based on type
                const permRequired = permanentFields.querySelectorAll('[required]');
                const visitRequired = visitingFields.querySelectorAll('[required]');
                
                if (type === 'Permanent') {
                    permRequired.forEach(field => field.required = true);
                    visitRequired.forEach(field => field.required = false);
                } else if (type === 'Temporary') {
                    permRequired.forEach(field => field.required = false);
                    visitRequired.forEach(field => field.required = true);
                }
            });
            
            // Form navigation
            document.querySelectorAll('.next-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const nextSection = this.getAttribute('data-next');
                    if (validateCurrentSection('basicInfoSection')) {
                        document.querySelector('.form-section.active').classList.remove('active');
                        document.getElementById(nextSection).classList.add('active');
                    }
                });
            });
            
            document.querySelectorAll('.prev-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const prevSection = this.getAttribute('data-prev');
                    document.querySelector('.form-section.active').classList.remove('active');
                    document.getElementById(prevSection).classList.add('active');
                });
            });
            
            // Form validation
            function validateCurrentSection(sectionId) {
                const section = document.getElementById(sectionId);
                const inputs = section.querySelectorAll('[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!input.value) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                // Special validation for password match
                if (sectionId === 'basicInfoSection') {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        document.getElementById('password').classList.add('is-invalid');
                        document.getElementById('confirm_password').classList.add('is-invalid');
                        document.querySelector('#confirm_password ~ .invalid-feedback').textContent = 'Passwords do not match';
                        isValid = false;
                    }
                    
                    // Check at least one working day is selected
                    const workingDays = document.querySelectorAll('input[name="working_days[]"]:checked');
                    if (workingDays.length === 0) {
                        document.querySelector('input[name="working_days[]"]').classList.add('is-invalid');
                        isValid = false;
                    }
                }
                
                return isValid;
            }
            
            // Real-time validation
            form.addEventListener('input', function(e) {
                const input = e.target;
                if (input.required && !input.value) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
                
                // Special handling for password confirmation
                if (input.id === 'confirm_password') {
                    const password = document.getElementById('password').value;
                    if (input.value !== password) {
                        input.classList.add('is-invalid');
                        document.querySelector('#confirm_password ~ .invalid-feedback').textContent = 'Passwords do not match';
                    } else {
                        input.classList.remove('is-invalid');
                    }
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                // Validate basic info section
                if (!validateCurrentSection('basicInfoSection')) {
                    e.preventDefault();
                    document.querySelector('.form-section.active').classList.remove('active');
                    document.getElementById('basicInfoSection').classList.add('active');
                    return false;
                }
                
                // Validate specific info section based on doctor type
                const doctorType = userTypeSelect.value;
                if (doctorType === 'Permanent') {
                    if (!validateCurrentSection('specificInfoSection')) {
                        e.preventDefault();
                        return false;
                    }
                } else if (doctorType === 'Temporary') {
                    if (!validateCurrentSection('specificInfoSection')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        });
    </script>
</body>
</html>