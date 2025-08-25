<?php
include('../includes/config.php');


// Ensure co-borrower session is active
if (!isset($_SESSION['co_borrower_application_id']) || !isset($_SESSION['co_borrower_last_name'])) {
    header("Location: coborrower_login.php");
    exit();
}

// Validate application in DB
$stmt = $pdo->prepare("SELECT status FROM applications 
                       WHERE application_id = ? AND status = 'submitted'");
$stmt->execute([$_SESSION['co_borrower_application_id']]);
$application = $stmt->fetch();

if (!$application) {
    // Invalid or not submitted
    session_destroy();
    header("Location: coborrower_login.php?error=invalid");
    exit();
}
