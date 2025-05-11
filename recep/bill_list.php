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
$bills = [];
$search_patient = '';
$search_status = '';
$search_date_from = '';
$search_date_to = '';
$error = '';
$success = '';

// Check if a bill is being cancelled
if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $bill_id = mysqli_real_escape_string($conn, $_GET['cancel']);
    
    $sql = "UPDATE bills SET payment_status = 'Cancelled' WHERE bill_id = $bill_id AND payment_status = 'Pending'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Bill #$bill_id has been cancelled successfully.";
    } else {
        $error = "Error cancelling bill: " . mysqli_error($conn);
    }
}

// Handle search filters
if (isset($_GET['search'])) {
    $search_patient = isset($_GET['patient']) ? mysqli_real_escape_string($conn, $_GET['patient']) : '';
    $search_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
    $search_date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
    $search_date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
}

// Build the query with filters
$sql = "SELECT b.*, p.first_name, p.last_name 
        FROM bills b 
        JOIN patient p ON b.patient_id = p.patient_id 
        WHERE 1=1";

if (!empty($search_patient)) {
    $sql .= " AND (p.first_name LIKE '%$search_patient%' OR p.last_name LIKE '%$search_patient%' OR p.patient_id = '$search_patient')";
}

if (!empty($search_status)) {
    $sql .= " AND b.payment_status = '$search_status'";
}

if (!empty($search_date_from)) {
    $sql .= " AND b.bill_date >= '$search_date_from'";
}

if (!empty($search_date_to)) {
    $sql .= " AND b.bill_date <= '$search_date_to'";
}

$sql .= " ORDER BY b.bill_date DESC";

// Execute the query
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bills[] = $row;
    }
} else {
    $error = "Error retrieving bills: " . mysqli_error($conn);
}

// Calculate total statistics
$total_amount = 0;
$total_paid = 0;
$total_pending = 0;

foreach ($bills as $bill) {
    if ($bill['payment_status'] == 'Paid') {
        $total_paid += $bill['net_amount'];
    } elseif ($bill['payment_status'] == 'Pending') {
        $total_pending += $bill['net_amount'];
    }
    
    if ($bill['payment_status'] != 'Cancelled') {
        $total_amount += $bill['net_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bills - Hospital Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

    <style>
        .stats-card {
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Include navigation here -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="mb-4">Hospital Billing Records</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Billed Amount</h5>
                                <h3 class="mb-0">$<?php echo number_format($total_amount, 2); ?></h3>
                                <small>All active bills</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Paid</h5>
                                <h3 class="mb-0">$<?php echo number_format($total_paid, 2); ?></h3>
                                <small>All paid bills</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total Pending</h5>
                                <h3 class="mb-0">$<?php echo number_format($total_pending, 2); ?></h3>
                                <small>All pending bills</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Search Bills</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="patient" class="form-label">Patient Name/ID</label>
                                        <input type="text" class="form-control" id="patient" name="patient" value="<?php echo $search_patient; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Payment Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="Pending" <?php echo ($search_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Paid" <?php echo ($search_status == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                            <option value="Partial" <?php echo ($search_status == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                                            <option value="Cancelled" <?php echo ($search_status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $search_date_from; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $search_date_to; ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="search" class="btn btn-primary">Search</button>
                            <a href="bills_list.php" class="btn btn-secondary">Reset Filters</a>
                        </form>
                    </div>
                </div>
                
                <!-- Bills Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Bills</h5>
                        <a href="bill.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Create New Bill
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="billsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Bill ID</th>
                                        <th>Patient</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bills)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No bills found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bills as $bill): ?>
                                            <tr>
                                                <td><?php echo $bill['bill_id']; ?></td>
                                                <td><?php echo $bill['first_name'] . ' ' . $bill['last_name']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></td>
                                                <td>$<?php echo number_format($bill['net_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo ($bill['payment_status'] == 'Paid') ? 'bg-success' : 
                                                             (($bill['payment_status'] == 'Pending') ? 'bg-warning' : 
                                                             (($bill['payment_status'] == 'Partial') ? 'bg-info' : 'bg-secondary')); 
                                                    ?>">
                                                        <?php echo $bill['payment_status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="bill.php?bill_id=<?php echo $bill['bill_id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    
                                                    <?php if ($bill['payment_status'] == 'Pending'): ?>
                                                        <a href="bills_list.php?cancel=<?php echo $bill['bill_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this bill?')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($bill['payment_status'] == 'Pending' || $bill['payment_status'] == 'Partial'): ?>
                                                        <a href="bill.php?bill_id=<?php echo $bill['bill_id']; ?>" class="btn btn-success btn-sm">
                                                            <i class="fas fa-credit-card"></i> Pay
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#billsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "language": {
                    "lengthMenu": "Show _MENU_ bills per page",
                    "zeroRecords": "No bills found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No bills available",
                    "infoFiltered": "(filtered from _MAX_ total bills)"
                }
            });
        });
    </script>
</body>
</html>