<?php
include('../includes/config.php');
include('../includes/auth_application.php');

// Add this at the top of your submit_application.php for debugging
error_log("Form data: " . print_r($_SESSION['form_data'], true));

// Then check what values you're getting:
$region_code = $form_data['region'] ?? 'MISSING';
$region_name = $form_data['region_name'] ?? 'MISSING';
$province_code = $form_data['province'] ?? 'MISSING';
$province_name = $form_data['province_name'] ?? 'MISSING';
$city_code = $form_data['city'] ?? 'MISSING';
$city_name = $form_data['city_name'] ?? 'MISSING';
$barangay_code = $form_data['barangay'] ?? 'MISSING';
$barangay_name = $form_data['barangay_name'] ?? 'MISSING';

error_log("Region code: $region_code, Region name: $region_name");
error_log("Province code: $province_code, Province name: $province_name");
error_log("City code: $city_code, City name: $city_name");
error_log("Barangay code: $barangay_code, Barangay name: $barangay_name");

// Check if user is logged in and has form data in session
if (!isset($_SESSION['application_id']) || !isset($_SESSION['form_data'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from session
    $form_data = $_SESSION['form_data'];

    // Get file paths from session
    $id_front_path = $_SESSION['id_front_path'] ?? '';
    $id_back_path = $_SESSION['id_back_path'] ?? '';
    $signature_path = $_SESSION['signature_path'] ?? '';

    // Process the form data with corrected variable names
    $first_name = $form_data['first_name'];
    $middle_name = $form_data['middle_name'] ?? '';
    $last_name = $form_data['last_name'];
    $name_extension = $form_data['name_extension'] ?? '';
    $birthdate = $form_data['birthdate'];
    $contact_number = $form_data['contact_number'];
    $email = $form_data['email'];
    $region_code = $form_data['region'];
    $region = $form_data['region_name']; // Changed to match 'region' column
    $province_code = $form_data['province'];
    $province = $form_data['province_name']; // Changed to match 'province' column
    $city_code = $form_data['city'];
    $city = $form_data['city_name']; // Changed to match 'city' column
    $barangay_code = $form_data['barangay'];
    $barangay = $form_data['barangay_name']; // Changed to match 'barangay' column
    $street_address = $form_data['street_address'];
    $zip_code = $form_data['zip_code'];
    $loan_amount = $form_data['loan_amount'];
    $term_months = $form_data['term_months'];
    $id_type = $form_data['id_type'];

    // Validate required files
    $upload_errors = [];

    // Check ID front
    if (empty($id_front_path)) {
        $upload_errors[] = "ID front photo is required.";
    } elseif (!file_exists($id_front_path)) {
        $upload_errors[] = "ID front photo file not found. Please upload again.";
    }

    // Check ID back
    if (empty($id_back_path)) {
        $upload_errors[] = "ID back photo is required.";
    } elseif (!file_exists($id_back_path)) {
        $upload_errors[] = "ID back photo file not found. Please upload again.";
    }

    // Check signature
    if (empty($signature_path)) {
        $upload_errors[] = "Signature is required.";
    } elseif (!file_exists($signature_path)) {
        $upload_errors[] = "Signature file not found. Please provide your signature again.";
    }

    // If there are any upload errors, stop processing
    if (!empty($upload_errors)) {
        $_SESSION['error'] = implode("<br>", $upload_errors);
        header("Location: form.php");
        exit();
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Save to applicants table with corrected column names
        $stmt = $pdo->prepare("INSERT INTO applicants (
            application_id, first_name, middle_name, last_name, name_extension, 
            birthdate, contact_number, email, 
            region_code, region, province_code, province, 
            city_code, city, barangay_code, barangay, 
            zip_code, street_address, loan_amount, term_months, id_type, 
            id_front_path, id_back_path, signature_path, relationship_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'borrower')");

        $stmt->execute([
            $_SESSION['application_id'],
            $first_name,
            $middle_name,
            $last_name,
            $name_extension,
            $birthdate,
            $contact_number,
            $email,
            $region_code,
            $region,
            $province_code,
            $province,
            $city_code,
            $city,
            $barangay_code,
            $barangay,
            $zip_code,
            $street_address,
            $loan_amount,
            $term_months,
            $id_type,
            $id_front_path,
            $id_back_path,
            $signature_path
        ]);


        // Update application status
        $stmt = $pdo->prepare("UPDATE applications SET status = 'submitted' WHERE application_id = ?");
        $stmt->execute([$_SESSION['application_id']]);

        // Generate processing ID for co-borrower
        $processing_id = 'PRO' . strtoupper(substr($last_name, 0, 3)) . date('YmdHis');
        $stmt = $pdo->prepare("UPDATE applications SET processing_id = ? WHERE application_id = ?");
        $stmt->execute([$processing_id, $_SESSION['application_id']]);

        // Commit transaction
        $pdo->commit();

        // Send email notification (if configured)
        // sendProcessingIdEmail($email, $processing_id, $first_name);

        // Clear session data
        unset($_SESSION['form_data']);
        unset($_SESSION['id_front_path']);
        unset($_SESSION['id_back_path']);
        unset($_SESSION['signature_path']);
        unset($_SESSION['files']);

        // Redirect to success
        $_SESSION['processing_id'] = $processing_id;
        $_SESSION['success_message'] = "Your application has been submitted successfully!";
        header("Location: success.php");
        exit();
    } catch (Exception $e) { // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Log error with full details
        error_log("Database error in submit_application.php: " . $e->getMessage());
        error_log("Error Code: " . $e->getCode());
        error_log("File: " . $e->getFile());
        error_log("Line: " . $e->getLine());
        error_log("Trace: " . $e->getTraceAsString());

        // For debugging - show the actual error (remove this in production)
        $_SESSION['error'] = "Database Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";

        // Also log the SQL query and parameters for debugging
        error_log("SQL Query: INSERT INTO applicants with parameters:");
        error_log(print_r([
            $_SESSION['application_id'],
            $first_name,
            $middle_name,
            $last_name,
            $name_extension,
            $birthdate,
            $contact_number,
            $email,
            $region_code,
            $region,
            $province_code,
            $province,
            $city_code,
            $city,
            $barangay_code,
            $barangay,
            $zip_code,
            $street_address,
            $loan_amount,
            $term_months,
            $id_type,
            $id_front_path,
            $id_back_path,
            $signature_path
        ], true));

        header("Location: form.php");
        exit();
    }
}

// If not POST method, redirect back to form
header("Location: form.php");
exit();

// File upload function (kept for reference, but we're using session-based approach now)
function uploadFile($file, $type)
{
    $target_dir = "../assets/$type/";

    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Failed to create directory: $target_dir");
            return false;
        }
    }

    // Check if directory is writable
    if (!is_writable($target_dir)) {
        error_log("Directory not writable: $target_dir");
        return false;
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;

    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        error_log("File too large: " . $file["size"] . " bytes");
        return false;
    }

    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        error_log("Invalid file extension: " . $file_extension);
        return false;
    }

    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        error_log("Failed to move uploaded file: " . $file["tmp_name"] . " to " . $target_file);
        error_log("Upload error: " . $file['error']);
        error_log("File permissions: " . substr(sprintf('%o', fileperms($target_dir)), -4));
        return false;
    }
}

// Signature data handling function
function saveSignatureFromData($data_url)
{
    $target_dir = "../assets/signatures/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Extract the image data from the data URL
    list($type, $data) = explode(';', $data_url);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);

    // Generate filename
    $file_name = uniqid() . '_' . time() . '.png';
    $target_file = $target_dir . $file_name;

    // Save the file
    if (file_put_contents($target_file, $data)) {
        return $target_file;
    }

    return false;
}

// Email function (commented out for now)
function sendProcessingIdEmail($email, $processing_id, $name)
{
    // Implement email sending functionality here
    // You can use PHPMailer or similar library
    return true; // Placeholder
}

// Add this function to your submit_application.php
function getUploadError($error_code)
{
    $upload_errors = array(
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension'
    );

    return $upload_errors[$error_code] ?? 'Unknown upload error';
}
