<?php
// Check if applicant is logged in
if (!isset($_SESSION['application_id']) || !isset($_SESSION['last_name'])) {
    header("Location: ../application/login.php");
    exit();
}

// Check if application exists, is in generated status, and last name matches
$stmt = $pdo->prepare("SELECT status FROM applications WHERE application_id = ? AND intended_last_name = ?");
$stmt->execute([$_SESSION['application_id'], $_SESSION['last_name']]);
$application = $stmt->fetch();

if (!$application) {
    // Application doesn't exist or last name doesn't match
    session_destroy();
    header("Location: ../application/login.php?error=invalid_credentials");
    exit();
}

if ($application['status'] !== 'generated') {
    // Application already submitted
    session_destroy();
    header("Location: ../application/login.php?error=already_submitted");
    exit();
}
