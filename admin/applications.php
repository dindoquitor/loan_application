<?php
include('../includes/config.php');
include('../includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle status updates
if (isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE application_id = ?");
    $stmt->execute([$status, $application_id]);

    // Log status change
    $stmt = $pdo->prepare("INSERT INTO application_history (application_id, status, changed_by, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$application_id, $status, $_SESSION['user_id'], $notes]);

    // Generate approval ID if status is approved
    if ($status === 'approved') {
        $approval_id = 'APR' . strtoupper(substr($application_id, 3)) . date('YmdHis');
        $stmt = $pdo->prepare("UPDATE applications SET approval_id = ? WHERE application_id = ?");
        $stmt->execute([$approval_id, $application_id]);
    }
}

// Get all applications with applicant data
$stmt = $pdo->prepare("SELECT a.*, 
                      borrower.first_name as b_first_name, borrower.last_name as b_last_name,
                      coborrower.first_name as c_first_name, coborrower.last_name as c_last_name
                      FROM applications a
                      LEFT JOIN applicants borrower ON a.application_id = borrower.application_id AND borrower.relationship_type = 'borrower'
                      LEFT JOIN applicants coborrower ON a.application_id = coborrower.application_id AND coborrower.relationship_type = 'co_borrower'
                      ORDER BY a.created_at DESC");
$stmt->execute();
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Loan Application System</a>
            <!-- ... navigation items ... -->
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Manage Applications</h2>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Application ID</th>
                        <th>Borrower</th>
                        <th>Co-borrower</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo $app['application_id']; ?></td>
                            <td><?php echo $app['b_first_name'] . ' ' . $app['b_last_name']; ?></td>
                            <td><?php echo $app['c_first_name'] . ' ' . $app['c_last_name']; ?></td>
                            <td>₱<?php echo number_format($app['loan_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        switch ($app['status']) {
                                                            case 'generated':
                                                                echo 'secondary';
                                                                break;
                                                            case 'submitted':
                                                                echo 'info';
                                                                break;
                                                            case 'co_applicant_completed':
                                                                echo 'warning';
                                                                break;
                                                            case 'approved':
                                                                echo 'success';
                                                                break;
                                                            case 'rejected':
                                                                echo 'danger';
                                                                break;
                                                            default:
                                                                echo 'secondary';
                                                        }
                                                        ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">View</a>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $app['id']; ?>">
                                    Update Status
                                </button>
                                <?php if ($app['status'] === 'approved' && $app['approval_id']): ?>
                                    <span class="badge bg-success">Approval ID: <?php echo $app['approval_id']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Status Update Modal -->
                        <div class="modal fade" id="statusModal<?php echo $app['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Application Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="submitted" <?php echo $app['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                                    <option value="co_applicant_completed" <?php echo $app['status'] === 'co_applicant_completed' ? 'selected' : ''; ?>>Co-applicant Completed</option>
                                                    <option value="approved" <?php echo $app['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="notes" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>