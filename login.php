<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title text-center">Admin Login</h3>
                </div>
                <div class="card-body">
                    <!-- Display error messages if any -->
                    <?php
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        if ($error == 'emptyfields') {
                            echo '<div class="alert alert-danger">Please fill in all fields.</div>';
                        } elseif ($error == 'invalidcredentials') {
                            echo '<div class="alert alert-danger">Invalid email or password.</div>';
                        }
                    }
                    ?>
                    <!-- Login Form -->
                    <form action="login_process.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>