<?php
include('../includes/config.php');
include('../includes/auth_application.php');

// Check if user is logged in
if (!isset($_SESSION['application_id'])) {
    header("Location: login.php");
    exit();
}

// Clear all form-related session data
unset($_SESSION['form_data']);
unset($_SESSION['files']);
unset($_SESSION['id_front_path']);
unset($_SESSION['id_back_path']);
unset($_SESSION['signature_path']);

// Also clear any form-related session variables
$keys_to_clear = ['form_data', 'files', 'temp_form_data'];
foreach ($keys_to_clear as $key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// Redirect back to clean form
header("Location: form.php?cleared=1");
exit();
