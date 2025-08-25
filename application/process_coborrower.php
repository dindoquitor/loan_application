<?php
include('../includes/config.php');
include('../includes/auth_application.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['is_coborrower'])) {
    // Process the form data (similar to main form processing)
    $processing_id = $_POST['processing_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    // ... (all other form fields)

    // Get application ID from processing ID
    $stmt = $pdo->prepare("SELECT application_id FROM applications WHERE processing_id = ?");
    $stmt->execute([$processing_id]);
    $application = $stmt->fetch();

    if ($application) {
        // Save co-borrower data (similar to main applicant)
        $stmt = $pdo->prepare("INSERT INTO applicants (
            application_id, first_name, middle_name, last_name, name_extension, 
            birthdate, contact_number, email, 
            region_code, region_name, province_code, province_name, 
            city_code, city_name, barangay_code, barangay_name, 
            zip_code, street_address, loan_amount, term_months, id_type, 
            id_front_path, id_back_path, signature_path, relationship_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'co_borrower')");

        // Execute with all parameters...

        // Update application status
        $stmt = $pdo->prepare("UPDATE applications SET status = 'co_applicant_completed' WHERE processing_id = ?");
        $stmt->execute([$processing_id]);

        // Clear processing ID for security
        $stmt = $pdo->prepare("UPDATE applications SET processing_id = NULL WHERE processing_id = ?");
        $stmt->execute([$processing_id]);

        header("Location: coborrower_success.php");
        exit();
    }
}

header("Location: coborrower_login.php");
exit();
