<?php
include('../includes/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $processing_id = trim($_POST['processing_id']);
    $last_name = trim($_POST['last_name']);

    // Check if processing ID exists and application is in 'submitted' status
    $stmt = $pdo->prepare("SELECT a.* 
                           FROM applications a 
                           WHERE a.processing_id = ? 
                           AND a.status = 'submitted'");
    $stmt->execute([$processing_id]);
    $application = $stmt->fetch();

    if ($application) {
        // Store co-borrower session data (standardized keys)
        $_SESSION['co_borrower_application_id'] = $application['application_id'];
        $_SESSION['processing_id'] = $application['processing_id'];
        $_SESSION['co_borrower_last_name'] = $last_name;
        $_SESSION['is_coborrower'] = true;

        header("Location: coborrower_form.php");
        exit();
    } else {
        $error = "Invalid Processing ID or application not ready for co-borrower.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Co-borrower Login - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h3 class="text-center">Co-borrower Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="processing_id" class="form-label">Processing ID</label>
                                <input type="text" class="form-control" id="processing_id" name="processing_id" required
                                    value="<?php echo isset($_SESSION['processing_id']) ? htmlspecialchars($_SESSION['processing_id']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Your Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <button type="submit" class="btn btn-info w-100">Login as Co-borrower</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3">
                    Use the Processing ID provided by the primary borrower.
                </p>
                <p class="text-center">
                    <a href="../login.php" class="text-decoration-none">← Back to Main Login</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>