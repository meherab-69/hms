<?php
session_start();
if (!isset($_SESSION['recep_id'])) {
    header("Location: login-receptionist.php");
    exit();
}

$servername = "localhost";
$username = "hms_user";
$password = "Opivhai@123";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success = $error = '';
$patients = $doctors = [];
$available_slots = [];

// Fetch all patients
$patient_result = $conn->query("SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name, mobile FROM patient");
if ($patient_result->num_rows > 0) {
    while($row = $patient_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch all doctors
$doctor_result = $conn->query("SELECT doctor_id, CONCAT(firstname, ' ', lastname) AS name, speciality, 
                             working_days, start_time, end_time, appointment_duration FROM doctors");
if ($doctor_result->num_rows > 0) {
    while($row = $doctor_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = $_POST['reason'];
    $status = "Scheduled";
    
    // Validate inputs
    if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $error = "Please fill all required fields";
    } else {
        // Check if doctor is available on this day
        $day_of_week = date('N', strtotime($appointment_date));
        $doctor_info = $conn->query("SELECT working_days, start_time, end_time FROM doctors WHERE user_id = '$doctor_id'")->fetch_assoc();
        $working_days = explode(',', $doctor_info['working_days']);
        
        if (!in_array($day_of_week, $working_days)) {
            $error = "Doctor is not available on the selected day";
        } else {
            // Check if time is within working hours
            $appointment_time_obj = strtotime($appointment_time);
            $start_time = strtotime($doctor_info['start_time']);
            $end_time = strtotime($doctor_info['end_time']);
            
            if ($appointment_time_obj < $start_time || $appointment_time_obj > $end_time) {
                $error = "Doctor is only available between " . date('h:i A', $start_time) . " and " . date('h:i A', $end_time);
            } else {
                // Check for existing appointment
                $existing = $conn->query("SELECT appointment_id FROM appointment 
                                        WHERE doctor_id = '$doctor_id' 
                                        AND appointment_date = '$appointment_date' 
                                        AND appointment_time = '$appointment_time'
                                        AND status != 'Cancelled'");
                
                if ($existing->num_rows > 0) {
                    $error = "Doctor already has an appointment at this time";
                } else {
                    // Create appointment
                    $stmt = $conn->prepare("INSERT INTO appointment (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status);
                    
                    if ($stmt->execute()) {
                        $success = "Appointment scheduled successfully for " . date('h:i A', strtotime($appointment_time)) . "!";
                    } else {
                        $error = "Error scheduling appointment: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Handle AJAX request for time slots
if (isset($_GET['get_slots']) && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    header('Content-Type: application/json');
    
    $doctor_id = $conn->real_escape_string($_GET['doctor_id']);
    $date = $conn->real_escape_string($_GET['date']);
    
    // Get doctor's schedule
    $doctor = $conn->query("SELECT working_days, start_time, end_time, appointment_duration 
                           FROM doctors WHERE user_id = '$doctor_id'")->fetch_assoc();
    
    $day_of_week = date('N', strtotime($date));
    $working_days = explode(',', $doctor['working_days']);
    
    if (!in_array($day_of_week, $working_days)) {
        die(json_encode(['error' => "Doctor not available on this day"]));
    }
    
    // Get booked slots
    $booked = $conn->query("SELECT appointment_time FROM appointment 
                           WHERE doctor_id = '$doctor_id' 
                           AND appointment_date = '$date'
                           AND status != 'Cancelled'");
    $booked_slots = [];
    while ($row = $booked->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }
    
    // Generate 10-minute time slots
    $start = strtotime($doctor['start_time']);
    $end = strtotime($doctor['end_time']);
    $duration = 10 * 60; // 10 minutes in seconds
    $slots = [];
    
    for ($time = $start; $time < $end; $time += $duration) {
        $slot_time = date('H:i:s', $time);
        $slots[] = [
            'time' => $slot_time,
            'display' => date('h:i A', $time),
            'booked' => in_array($slot_time, $booked_slots)
        ];
    }
    
    die(json_encode($slots));
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Scheduling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .appointment-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .form-title {
            color: #0d6efd;
            margin-bottom: 30px;
            text-align: center;
        }
        .time-slot {
            display: inline-block;
            padding: 5px 10px;
            margin: 5px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .time-slot:hover { background-color: #f8f9fa; }
        .time-slot.booked {
            background-color: #f8d7da;
            color: #721c24;
            cursor: not-allowed;
        }
        .time-slot.selected {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
        }
        .time-slot.available {
            background-color: #e7f1ff;
            color: #0a58ca;
        }
        .search-box {
            position: relative;
        }
        .search-results {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            z-index: 1000;
            display: none;
        }
        .search-item {
            padding: 8px 15px;
            cursor: pointer;
        }
        .search-item:hover {
            background-color: #f8f9fa;
        }
        .doctor-info {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="appointment-form">
                    <h2 class="form-title">Schedule New Appointment</h2>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php elseif($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <!-- Patient Selection -->
                        <div class="mb-3 search-box">
                            <label class="form-label">Patient</label>
                            <input type="text" class="form-control" id="patient-search" placeholder="Search patient..." autocomplete="off">
                            <input type="hidden" name="patient_id" id="patient_id">
                            <div class="search-results" id="patient-results">
                                <?php foreach($patients as $p): ?>
                                    <div class="search-item" data-id="<?= $p['patient_id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">
                                        <?= htmlspecialchars($p['name']) ?> (ID: <?= $p['patient_id'] ?>)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2" id="selected-patient"></div>
                        </div>
                        
                        <!-- Doctor Selection -->
                        <div class="mb-3 search-box">
                            <label class="form-label">Doctor</label>
                            <input type="text" class="form-control" id="doctor-search" placeholder="Search doctor..." autocomplete="off">
                            <input type="hidden" name="doctor_id" id="doctor_id">
                            <div class="search-results" id="doctor-results">
                                <?php foreach($doctors as $d): ?>
                                    <div class="search-item" data-id="<?= $d['user_id'] ?>" data-name="<?= htmlspecialchars($d['name']) ?>" 
                                         data-working-days="<?= $d['working_days'] ?>" 
                                         data-start-time="<?= date('h:i A', strtotime($d['start_time'])) ?>"
                                         data-end-time="<?= date('h:i A', strtotime($d['end_time'])) ?>">
                                        <?= htmlspecialchars($d['name']) ?> - <?= $d['speciality'] ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2" id="selected-doctor"></div>
                            <div class="doctor-info" id="doctor-info" style="display:none;">
                                <strong>Working Hours:</strong> <span id="working-hours"></span><br>
                                <strong>Available Days:</strong> <span id="working-days"></span>
                            </div>
                        </div>
                        
                        <!-- Date and Time -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="appointment_date" class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" name="appointment_date" id="appointment_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="appointment_time" class="form-label">Appointment Time</label>
                                <input type="time" class="form-control" name="appointment_time" id="appointment_time" step="600" required> <!-- 600 seconds = 10 minutes -->
                                <small class="text-muted">Select from available 10-minute slots below</small>
                                <div id="time-slots" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Reason -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Appointment</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Schedule Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set min date to today
            document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
            
            // Patient search
            const patientSearch = document.getElementById('patient-search');
            const patientResults = document.getElementById('patient-results');
            const patientId = document.getElementById('patient_id');
            const selectedPatient = document.getElementById('selected-patient');
            
            patientSearch.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const items = patientResults.querySelectorAll('.search-item');
                let hasMatches = false;
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(term)) {
                        item.style.display = 'block';
                        hasMatches = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                patientResults.style.display = hasMatches ? 'block' : 'none';
            });
            
            patientResults.addEventListener('click', function(e) {
                if (e.target.classList.contains('search-item')) {
                    const item = e.target;
                    patientId.value = item.getAttribute('data-id');
                    selectedPatient.innerHTML = `<strong>Selected:</strong> ${item.getAttribute('data-name')}`;
                    patientResults.style.display = 'none';
                    patientSearch.value = '';
                }
            });
            
            // Doctor search
            const doctorSearch = document.getElementById('doctor-search');
            const doctorResults = document.getElementById('doctor-results');
            const doctorId = document.getElementById('doctor_id');
            const selectedDoctor = document.getElementById('selected-doctor');
            const doctorInfo = document.getElementById('doctor-info');
            
            doctorSearch.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const items = doctorResults.querySelectorAll('.search-item');
                let hasMatches = false;
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(term)) {
                        item.style.display = 'block';
                        hasMatches = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                doctorResults.style.display = hasMatches ? 'block' : 'none';
            });
            
            doctorResults.addEventListener('click', function(e) {
                if (e.target.classList.contains('search-item')) {
                    const item = e.target;
                    doctorId.value = item.getAttribute('data-id');
                    selectedDoctor.innerHTML = `<strong>Selected:</strong> ${item.getAttribute('data-name')}`;
                    doctorResults.style.display = 'none';
                    doctorSearch.value = '';
                    
                    // Show doctor info
                    const daysMap = {
                        '1': 'Monday',
                        '2': 'Tuesday',
                        '3': 'Wednesday',
                        '4': 'Thursday',
                        '5': 'Friday',
                        '6': 'Saturday',
                        '7': 'Sunday'
                    };
                    const workingDays = item.getAttribute('data-working-days').split(',').map(d => daysMap[d]).join(', ');
                    
                    document.getElementById('working-hours').textContent = 
                        `${item.getAttribute('data-start-time')} to ${item.getAttribute('data-end-time')}`;
                    document.getElementById('working-days').textContent = workingDays;
                    doctorInfo.style.display = 'block';
                    
                    // Load time slots if date is selected
                    if (document.getElementById('appointment_date').value) {
                        loadTimeSlots();
                    }
                }
            });
            
            // Date change handler
            document.getElementById('appointment_date').addEventListener('change', function() {
                if (doctorId.value) {
                    loadTimeSlots();
                }
            });
            
            // Time slot selection
            document.getElementById('time-slots').addEventListener('click', function(e) {
                if (e.target.classList.contains('time-slot') && !e.target.classList.contains('booked')) {
                    // Remove selected class from all slots
                    document.querySelectorAll('.time-slot').forEach(slot => {
                        slot.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked slot
                    e.target.classList.add('selected');
                    
                    // Set the time input value
                    document.getElementById('appointment_time').value = e.target.getAttribute('data-time');
                }
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-box')) {
                    patientResults.style.display = 'none';
                    doctorResults.style.display = 'none';
                }
            });
            
            // Function to load time slots
            function loadTimeSlots() {
                const doctorId = document.getElementById('doctor_id').value;
                const date = document.getElementById('appointment_date').value;
                
                if (!doctorId || !date) return;
                
                const container = document.getElementById('time-slots');
                container.innerHTML = '<div class="text-center">Loading available 10-minute slots...</div>';
                
                fetch(`appointment.php?get_slots=1&doctor_id=${doctorId}&date=${date}`)
                    .then(response => response.json())
                    .then(slots => {
                        container.innerHTML = '';
                        
                        if (slots.error) {
                            container.innerHTML = `<div class="text-danger">${slots.error}</div>`;
                            return;
                        }
                        
                        if (slots.length === 0) {
                            container.innerHTML = '<div class="text-danger">No available slots for this day</div>';
                            return;
                        }
                        
                        slots.forEach(slot => {
                            const el = document.createElement('span');
                            el.className = `time-slot ${slot.booked ? 'booked' : 'available'}`;
                            el.textContent = slot.display;
                            el.setAttribute('data-time', slot.time);
                            if (slot.booked) {
                                el.title = 'Already booked';
                            } else {
                                el.title = 'Available slot';
                            }
                            container.appendChild(el);
                        });
                    })
                    .catch(error => {
                        container.innerHTML = '<div class="text-danger">Error loading time slots</div>';
                        console.error(error);
                    });
            }
        });
    </script>
</body>
</html>