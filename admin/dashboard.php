<?php
include('../includes/config.php');
include('../includes/auth.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get statistics
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recent applications
$stmt = $pdo->prepare("SELECT a.*, app.first_name, app.last_name 
                      FROM applications a 
                      LEFT JOIN applicants app ON a.application_id = app.application_id 
                      WHERE app.relationship_type = 'borrower'
                      ORDER BY a.created_at DESC 
                      LIMIT 5");
$stmt->execute();
$recent_applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Loan Application System</title>
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
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="generate_id.php"><i class="bi bi-plus-circle"></i> Generate ID</a>
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
        <h2 class="mb-4">Admin Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Generated</h5>
                        <p class="card-text display-6"><?php echo $status_counts['generated'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Submitted</h5>
                        <p class="card-text display-6"><?php echo $status_counts['submitted'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Co-Applicant</h5>
                        <p class="card-text display-6"><?php echo $status_counts['co_applicant_completed'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Approved</h5>
                        <p class="card-text display-6"><?php echo $status_counts['approved'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="generate_id.php" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> Generate Application ID</a>
                        <a href="applications.php" class="btn btn-secondary me-2"><i class="bi bi-list-check"></i> View Applications</a>
                        <a href="lender_config.php" class="btn btn-outline-primary"><i class="bi bi-gear"></i> Lender Settings</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_applications) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Applicant Name</th>
                                            <th>Loan Amount</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                                <td>₱<?php echo number_format($app['loan_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?php
                                                        switch ($app['status']) {
                                                            case 'generated':
                                                                echo 'bg-primary';
                                                                break;
                                                            case 'submitted':
                                                                echo 'bg-info';
                                                                break;
                                                            case 'co_applicant_completed':
                                                                echo 'bg-warning';
                                                                break;
                                                            case 'approved':
                                                                echo 'bg-success';
                                                                break;
                                                            case 'rejected':
                                                                echo 'bg-danger';
                                                                break;
                                                            default:
                                                                echo 'bg-secondary';
                                                        }
                                                        ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No applications found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>