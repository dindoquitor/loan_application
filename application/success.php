<?php
include('../includes/config.php');
include('../includes/auth_application.php');

if (!isset($_SESSION['processing_id'])) {
    header("Location: login.php");
    exit();
}

$processing_id = $_SESSION['processing_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="text-center">Application Submitted Successfully!</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="mb-3">Your Processing ID: <strong><?php echo $processing_id; ?></strong></h4>
                        <p class="mb-4">Please share this Processing ID with your co-borrower to complete their part of the application.</p>
                        <div class="alert alert-info">
                            <strong>Important:</strong> The co-borrower needs to visit the application login page and use this Processing ID along with their last name to complete the application process.
                        </div>
                        <a href="logout.php" class="btn btn-primary">Finish</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>