<?php
include('../includes/config.php');
include('../includes/auth.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Generate application ID
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $last_name = trim($_POST['last_name']);

    if (!empty($last_name)) {
        // Generate unique application ID
        $application_id = 'APP' . strtoupper(substr($last_name, 0, 3)) . date('YmdHis');

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO applications (application_id, admin_id, status) VALUES (?, ?, 'generated')");
        $stmt->execute([$application_id, $_SESSION['user_id']]);

        $success = "Application ID generated successfully: <strong>$application_id</strong>";
    } else {
        $error = "Please enter a last name";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Application ID - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Loan Application System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="generate_id.php"><i class="bi bi-plus-circle"></i> Generate ID</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php"><i class="bi bi-list-check"></i> Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lender_config.php"><i class="bi bi-gear"></i> Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Generate Application ID</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Applicant's Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                <div class="form-text">This will be used to generate the application ID and as the username for login.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Generate Application ID</button>
                        </form>
                    </div>
                </div>

                <!-- Recent Generated IDs -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Recently Generated IDs</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT application_id, created_at 
                                              FROM applications 
                                              WHERE status = 'generated' 
                                              ORDER BY created_at DESC 
                                              LIMIT 10");
                        $stmt->execute();
                        $recent_ids = $stmt->fetchAll();

                        if (count($recent_ids) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Date Generated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_ids as $id): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($id['application_id']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($id['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No application IDs generated yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>