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
$bill_id = '';
$patient_id = '';
$admission_id = '';
$appointment_id = '';
$total_amount = 0;
$discount = 0;
$tax = 0;
$net_amount = 0;
$payment_status = 'Pending';
$bill_date = date('Y-m-d');
$payment_method = '';
$error = '';
$success = '';
$patients = [];
$admissions = [];
$appointments = [];
$treatments = [];
$bill_details = [];

// Fetch all patients
$sql = "SELECT * FROM patient ORDER BY first_name, last_name";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }
}

// Function to check if an item is already paid
function isItemAlreadyPaid($conn, $item_type, $item_id) {
    $sql = "SELECT b.payment_status 
            FROM bill_details d
            JOIN bills b ON d.bill_id = b.bill_id
            WHERE d.item_type = '$item_type' 
            AND d.item_id = $item_id
            AND b.payment_status = 'Paid'";
    
    $result = mysqli_query($conn, $sql);
    return (mysqli_num_rows($result) > 0);
}

// Function to calculate admission charges
function calculateAdmissionCharges($conn, $admission_id) {
    $charges = 0;
    
    $sql = "SELECT a.*, b.rate_per_day, DATEDIFF(IFNULL(a.discharge_date, NOW()), a.admission_date) AS days_stayed 
            FROM admission a 
            JOIN beds b ON a.bed_id = b.bed_id 
            WHERE a.admission_id = $admission_id";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $admission = mysqli_fetch_assoc($result);
        $days_stayed = max(1, $admission['days_stayed']);
        $charges = $days_stayed * $admission['rate_per_day'];
    }
    
    return $charges;
}

// Function to calculate appointment charges
function calculateAppointmentCharges($conn, $appointment_id) {
    $charges = 0;
    
    $sql = "SELECT a.*, d.doctor_id 
            FROM appointment a 
            JOIN doctors d ON a.doctor_id = d.doctor_id 
            WHERE a.appointment_id = $appointment_id";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $appointment = mysqli_fetch_assoc($result);
        $doctor_id = $appointment['doctor_id'];
        
        $sql = "SELECT consultation_fee FROM permanent_doctors WHERE doctor_id = '$doctor_id'";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $doctor = mysqli_fetch_assoc($result);
            $charges = $doctor['consultation_fee'];
        } else {
            $sql = "SELECT consultation_fee FROM visiting_doctors WHERE doctor_id = '$doctor_id'";
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $doctor = mysqli_fetch_assoc($result);
                $charges = $doctor['consultation_fee'];
            }
        }
    }
    
    return $charges;
}

// Function to calculate treatment charges
function calculateTreatmentCharges($conn, $treatment_ids) {
    $charges = 0;
    
    if (!empty($treatment_ids)) {
        $treatment_ids_str = implode(',', $treatment_ids);
        $sql = "SELECT SUM(cost) AS total_cost FROM treatment WHERE treatment_id IN ($treatment_ids_str)";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $charges = $row['total_cost'];
        }
    }
    
    return $charges;
}

// Process form submission for patient selection
if (isset($_POST['find_patient'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    
    $sql = "SELECT * FROM patient WHERE patient_id = $patient_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $patient = mysqli_fetch_assoc($result);
        
        // Get active unpaid admissions
        $sql = "SELECT a.*, b.bed_type, b.rate_per_day 
                FROM admission a 
                JOIN beds b ON a.bed_id = b.bed_id 
                WHERE a.patient_id = $patient_id 
                AND (a.status = 'Admitted' OR a.status = 'Transferred')
                AND NOT EXISTS (
                    SELECT 1 FROM bill_details d
                    JOIN bills b ON d.bill_id = b.bill_id
                    WHERE d.item_type = 'Admission' 
                    AND d.item_id = a.admission_id
                    AND b.payment_status = 'Paid'
                )";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $admissions[] = $row;
            }
        }
        
        // Get unpaid appointments
        $sql = "SELECT a.*, d.firstname, d.lastname 
                FROM appointment a 
                JOIN doctors d ON a.doctor_id = d.doctor_id 
                WHERE a.patient_id = $patient_id 
                AND a.status != 'Cancelled'
                AND NOT EXISTS (
                    SELECT 1 FROM bill_details d
                    JOIN bills b ON d.bill_id = b.bill_id
                    WHERE d.item_type = 'Appointment' 
                    AND d.item_id = a.appointment_id
                    AND b.payment_status = 'Paid'
                )";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $appointments[] = $row;
            }
        }
        
        // Get unpaid treatments
        $sql = "SELECT * FROM treatment 
                WHERE patient_id = $patient_id
                AND NOT EXISTS (
                    SELECT 1 FROM bill_details d
                    JOIN bills b ON d.bill_id = b.bill_id
                    WHERE d.item_type = 'Treatment' 
                    AND d.item_id = treatment_id
                    AND b.payment_status = 'Paid'
                )";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $treatments[] = $row;
            }
        }
        
        // Check for existing unpaid bill
        $sql = "SELECT * FROM bills WHERE patient_id = $patient_id AND payment_status = 'Pending' ORDER BY bill_date DESC";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $bill = mysqli_fetch_assoc($result);
            $bill_id = $bill['bill_id'];
            
            $sql = "SELECT * FROM bill_details WHERE bill_id = $bill_id";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $bill_details[] = $row;
                }
            }
        }
    } else {
        $error = "Patient not found!";
    }
}

// Process form submission for bill generation
if (isset($_POST['generate_bill'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $admission_id = isset($_POST['admission_id']) ? mysqli_real_escape_string($conn, $_POST['admission_id']) : null;
    $appointment_ids = isset($_POST['appointment_ids']) ? $_POST['appointment_ids'] : [];
    $treatment_ids = isset($_POST['treatment_ids']) ? $_POST['treatment_ids'] : [];
    $discount = mysqli_real_escape_string($conn, $_POST['discount']);
    $tax = mysqli_real_escape_string($conn, $_POST['tax']);
    
    // Validate items haven't been paid since page load
    $valid_appointment_ids = [];
    foreach ($appointment_ids as $app_id) {
        if (!isItemAlreadyPaid($conn, 'Appointment', $app_id)) {
            $valid_appointment_ids[] = $app_id;
        }
    }
    
    $valid_treatment_ids = [];
    foreach ($treatment_ids as $treatment_id) {
        if (!isItemAlreadyPaid($conn, 'Treatment', $treatment_id)) {
            $valid_treatment_ids[] = $treatment_id;
        }
    }
    
    $valid_admission_id = null;
    if ($admission_id && !isItemAlreadyPaid($conn, 'Admission', $admission_id)) {
        $valid_admission_id = $admission_id;
    }
    
    // Calculate charges
    $admission_charges = $valid_admission_id ? calculateAdmissionCharges($conn, $valid_admission_id) : 0;
    
    $appointment_charges = 0;
    foreach ($valid_appointment_ids as $app_id) {
        $appointment_charges += calculateAppointmentCharges($conn, $app_id);
    }
    
    $treatment_charges = calculateTreatmentCharges($conn, $valid_treatment_ids);
    
    $total_amount = $admission_charges + $appointment_charges + $treatment_charges;
    $discount_amount = ($discount / 100) * $total_amount;
    $tax_amount = ($tax / 100) * ($total_amount - $discount_amount);
    $net_amount = $total_amount - $discount_amount + $tax_amount;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create new bill
        $sql = "INSERT INTO bills (patient_id, total_amount, discount, tax, net_amount, payment_status, bill_date) 
                VALUES ($patient_id, $total_amount, $discount, $tax, $net_amount, 'Pending', '$bill_date')";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error creating bill: " . mysqli_error($conn));
        }
        
        $bill_id = mysqli_insert_id($conn);
        
        // Archive and remove previous paid bills
        // 1. Archive paid bills
        $sql = "INSERT INTO archived_bills 
                SELECT * FROM bills 
                WHERE patient_id = $patient_id 
                AND payment_status = 'Paid'
                AND bill_id != $bill_id";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error archiving paid bills: " . mysqli_error($conn));
        }

        // 2. Archive bill details
        $sql = "INSERT INTO archived_bill_details 
                SELECT d.* FROM bill_details d
                JOIN bills b ON d.bill_id = b.bill_id
                WHERE b.patient_id = $patient_id 
                AND b.payment_status = 'Paid'
                AND b.bill_id != $bill_id";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error archiving bill details: " . mysqli_error($conn));
        }

        // 3. Delete paid bills
        $sql = "DELETE FROM bills 
                WHERE patient_id = $patient_id 
                AND payment_status = 'Paid'
                AND bill_id != $bill_id";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error cleaning up paid bills: " . mysqli_error($conn));
        }

        // Add admission charges if applicable
        if ($valid_admission_id) {
            $sql = "INSERT INTO bill_details (bill_id, item_type, item_id, description, amount) 
                    VALUES ($bill_id, 'Admission', $valid_admission_id, 'Room charges', $admission_charges)";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error adding admission charges: " . mysqli_error($conn));
            }
        }
        
        // Add appointment charges
        foreach ($valid_appointment_ids as $app_id) {
            $fee = calculateAppointmentCharges($conn, $app_id);
            $sql = "INSERT INTO bill_details (bill_id, item_type, item_id, description, amount) 
                    VALUES ($bill_id, 'Appointment', $app_id, 'Doctor consultation', $fee)";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error adding appointment charges: " . mysqli_error($conn));
            }
        }
        
        // Add treatment charges
        foreach ($valid_treatment_ids as $treatment_id) {
            $sql = "SELECT cost, treatment_name FROM treatment WHERE treatment_id = $treatment_id";
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $treatment = mysqli_fetch_assoc($result);
                $cost = $treatment['cost'];
                $description = $treatment['treatment_name'];
                
                $sql = "INSERT INTO bill_details (bill_id, item_type, item_id, description, amount) 
                        VALUES ($bill_id, 'Treatment', $treatment_id, '$description', $cost)";
                
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception("Error adding treatment charges: " . mysqli_error($conn));
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $success = "Bill generated successfully with ID: " . $bill_id . ". Previous paid bills archived.";
        
        // Reload bill data
        $sql = "SELECT b.*, p.first_name, p.last_name 
                FROM bills b 
                JOIN patient p ON b.patient_id = p.patient_id 
                WHERE b.bill_id = $bill_id";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $bill = mysqli_fetch_assoc($result);
            $payment_status = $bill['payment_status'];
            $payment_method = $bill['payment_method'];
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Process payment
if (isset($_POST['make_payment'])) {
    $bill_id = mysqli_real_escape_string($conn, $_POST['bill_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $amount_paid = (float)$_POST['amount_paid'];
    $net_amount = (float)$net_amount;
    
    if ($amount_paid < $net_amount) {
        $error = "Payment amount cannot be less than the bill total";
    } else {
        $sql = "UPDATE bills SET payment_status = 'Paid', payment_method = '$payment_method', payment_date = NOW() WHERE bill_id = $bill_id";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Payment processed successfully!";
            
            $sql = "SELECT b.*, p.first_name, p.last_name 
                    FROM bills b 
                    JOIN patient p ON b.patient_id = p.patient_id 
                    WHERE b.bill_id = $bill_id";
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $bill = mysqli_fetch_assoc($result);
                $payment_status = $bill['payment_status'];
                $payment_method = $bill['payment_method'];
            }
        } else {
            $error = "Error processing payment: " . mysqli_error($conn);
        }
    }
}

// Get bill details from URL
if (isset($_GET['bill_id'])) {
    $bill_id = mysqli_real_escape_string($conn, $_GET['bill_id']);
    
    $sql = "SELECT b.*, p.first_name, p.last_name 
            FROM bills b 
            JOIN patient p ON b.patient_id = p.patient_id 
            WHERE b.bill_id = $bill_id";
    
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $bill = mysqli_fetch_assoc($result);
        $patient_id = $bill['patient_id'];
        $total_amount = $bill['total_amount'];
        $discount = $bill['discount'];
        $tax = $bill['tax'];
        $net_amount = $bill['net_amount'];
        $payment_status = $bill['payment_status'];
        $bill_date = $bill['bill_date'];
        $payment_method = $bill['payment_method'];
        
        $sql = "SELECT * FROM bill_details WHERE bill_id = $bill_id";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $bill_details[] = $row;
            }
        }
    } else {
        $error = "Bill not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .bill-header { border-bottom: 1px solid #ddd; margin-bottom: 20px; padding-bottom: 10px; }
        .bill-footer { border-top: 1px solid #ddd; margin-top: 20px; padding-top: 10px; }
        .print-area { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .container { width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="mb-4">Hospital Billing System</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Patient Selection Form -->
                <div class="card mb-4 no-print">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Select Patient</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="patient_id" class="form-label">Patient</label>
                                        <select name="patient_id" id="patient_id" class="form-select" required>
                                            <option value="">-- Select Patient --</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['patient_id']; ?>" <?php echo ($patient_id == $patient['patient_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $patient['first_name'] . ' ' . $patient['last_name'] . ' (ID: ' . $patient['patient_id'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" name="find_patient" class="btn btn-primary">Find Patient</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($patient_id && empty($bill_id)): ?>
                    <!-- Bill Generation Form -->
                    <div class="card mb-4 no-print">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Generate Bill</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                                
                                <?php if (!empty($admissions)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Select Admission (if applicable)</label>
                                        <select name="admission_id" class="form-select">
                                            <option value="">-- None --</option>
                                            <?php foreach ($admissions as $admission): ?>
                                                <option value="<?php echo $admission['admission_id']; ?>">
                                                    Admission ID: <?php echo $admission['admission_id']; ?> - 
                                                    Date: <?php echo date('d M Y', strtotime($admission['admission_date'])); ?> - 
                                                    Bed: <?php echo $admission['bed_type']; ?> 
                                                    (Rate: $<?php echo $admission['rate_per_day']; ?>/day)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($appointments)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Select Appointments</label>
                                        <div class="border p-3 rounded">
                                            <?php foreach ($appointments as $appointment): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="appointment_ids[]" value="<?php echo $appointment['appointment_id']; ?>" id="app_<?php echo $appointment['appointment_id']; ?>">
                                                    <label class="form-check-label" for="app_<?php echo $appointment['appointment_id']; ?>">
                                                        Appointment ID: <?php echo $appointment['appointment_id']; ?> - 
                                                        Date: <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?> - 
                                                        Doctor: Dr. <?php echo $appointment['firstname'] . ' ' . $appointment['lastname']; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($treatments)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Select Treatments</label>
                                        <div class="border p-3 rounded">
                                            <?php foreach ($treatments as $treatment): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="treatment_ids[]" value="<?php echo $treatment['treatment_id']; ?>" id="treat_<?php echo $treatment['treatment_id']; ?>">
                                                    <label class="form-check-label" for="treat_<?php echo $treatment['treatment_id']; ?>">
                                                        <?php echo $treatment['treatment_name']; ?> - 
                                                        Cost: $<?php echo $treatment['cost']; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="discount" class="form-label">Discount (%)</label>
                                            <input type="number" class="form-control" id="discount" name="discount" min="0" max="100" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tax" class="form-label">Tax (%)</label>
                                            <input type="number" class="form-control" id="tax" name="tax" min="0" max="100" value="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="generate_bill" class="btn btn-primary">Generate Bill</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($bill_id): ?>
                    <!-- Bill Display and Payment Form -->
                    <div class="print-area">
                        <div class="bill-header text-center">
                            <h3>Hospital Name</h3>
                            <p>123 Medical Street, Healthcare City</p>
                            <p>Phone: (123) 456-7890 | Email: info@hospital.com</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Bill #<?php echo $bill_id; ?></h4>
                                <p>Date: <?php echo date('d M Y', strtotime($bill_date)); ?></p>
                                <p>Status: <span class="badge <?php echo ($payment_status == 'Paid') ? 'bg-success' : 'bg-warning'; ?>"><?php echo $payment_status; ?></span></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h5>Patient Information</h5>
                                <?php 
                                $patient_sql = "SELECT * FROM patient WHERE patient_id = $patient_id";
                                $patient_result = mysqli_query($conn, $patient_sql);
                                if ($patient_result && mysqli_num_rows($patient_result) > 0) {
                                    $patient = mysqli_fetch_assoc($patient_result);
                                    echo "<p>Name: {$patient['first_name']} {$patient['last_name']}</p>";
                                    echo "<p>ID: {$patient['patient_id']}</p>";
                                    echo "<p>Contact: {$patient['mobile']}</p>";
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="table-responsive mt-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>ID</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bill_details as $detail): ?>
                                        <tr>
                                            <td><?php echo $detail['description']; ?></td>
                                            <td><?php echo $detail['item_type']; ?></td>
                                            <td><?php echo $detail['item_id']; ?></td>
                                            <td class="text-end">$<?php echo number_format($detail['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <th class="text-end">$<?php echo number_format($total_amount, 2); ?></th>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Discount (<?php echo $discount; ?>%):</td>
                                        <td class="text-end">-$<?php echo number_format(($discount / 100) * $total_amount, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax (<?php echo $tax; ?>%):</td>
                                        <td class="text-end">$<?php echo number_format(($tax / 100) * ($total_amount - ($discount / 100) * $total_amount), 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">$<?php echo number_format($net_amount, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="bill-footer">
                            <div class="row">
                                <div class="col-md-8">
                                    <p><strong>Payment Method:</strong> <?php echo !empty($payment_method) ? $payment_method : 'Not paid yet'; ?></p>
                                    <p><strong>Terms & Conditions:</strong></p>
                                    <p>1. All payments are due within 30 days of the bill date.<br>
                                       2. Late payments may incur additional charges.<br>
                                       3. For any billing inquiries, please contact our billing department.</p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <p><strong>Hospital Signature</strong></p>
                                    <p class="mt-5">Authorized Signatory</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($payment_status != 'Paid'): ?>
                        <div class="card mt-4 no-print">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Process Payment</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="payment_method" class="form-label">Payment Method</label>
                                                <select name="payment_method" id="payment_method" class="form-select" required>
                                                    <option value="">-- Select Payment Method --</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Credit Card">Credit Card</option>
                                                    <option value="Debit Card">Debit Card</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Insurance">Insurance</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="amount_paid" class="form-label">Amount Paid</label>
                                                <input type="number" step="0.01" class="form-control" id="amount_paid" name="amount_paid" value="<?php echo $net_amount; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="make_payment" class="btn btn-success">Process Payment</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 no-print">
                        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Bill</button>
                        <a href="bills.php" class="btn btn-primary"><i class="fas fa-list"></i> View All Bills</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>