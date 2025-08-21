<?php
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = trim($_POST['application_id']);
    $last_name = trim($_POST['last_name']);

    // Check if application exists and matches last name
    $stmt = $pdo->prepare("SELECT a.*, app.last_name 
                          FROM applications a 
                          LEFT JOIN applicants app ON a.application_id = app.application_id 
                          WHERE a.application_id = ? AND app.last_name = ? AND a.status = 'generated'");
    $stmt->execute([$application_id, $last_name]);
    $application = $stmt->fetch();

    if ($application) {
        $_SESSION['application_id'] = $application['application_id'];
        $_SESSION['last_name'] = $application['last_name'];
        header("Location: form.php");
        exit();
    } else {
        $error = "Invalid Application ID or Last Name, or application already submitted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Login - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Applicant Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="application_id" class="form-label">Application ID</label>
                                <input type="text" class="form-control" id="application_id" name="application_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>