<?php
include('../includes/config.php');

// Check if setup is already complete
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$user_count = $stmt->fetch()['count'];

if ($user_count > 0) {
    // Setup already completed, redirect to login
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Create first admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, is_setup_complete) VALUES (?, ?, 'admin', TRUE)");
        $stmt->execute([$username, $hashed_password]);

        // Mark setup as complete
        $pdo->exec("UPDATE users SET is_setup_complete = TRUE");

        // Redirect to login
        header("Location: login.php?setup=complete");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Initial Setup</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-center">Welcome! It looks like this is your first time using the system.<br>Please create an administrator account.</p>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Admin Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>