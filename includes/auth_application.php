<?php
// Check if applicant is logged in
if (!isset($_SESSION['application_id']) || !isset($_SESSION['last_name'])) {
    header("Location: login.php");
    exit();
}

// Check if application exists and is in generated status
$stmt = $pdo->prepare("SELECT status FROM applications WHERE application_id = ?");
$stmt->execute([$_SESSION['application_id']]);
$application = $stmt->fetch();

if (!$application || $application['status'] !== 'generated') {
    // Application doesn't exist or already submitted
    session_destroy();
    header("Location: login.php");
    exit();
}
