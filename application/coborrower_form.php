<?php

include('../includes/coborrower_auth.php');

// Fetch borrower's application data
$stmt = $pdo->prepare("SELECT * FROM applications WHERE application_id = ?");
$stmt->execute([$_SESSION['co_borrower_application_id']]);
$application = $stmt->fetch();

if (!$application) {
    die("Application not found.");
}

// Get borrower's personal information from applicants table
$stmt = $pdo->prepare("SELECT * FROM applicants WHERE application_id = ? AND relationship_type = 'borrower'");
$stmt->execute([$_SESSION['co_borrower_application_id']]);
$borrower = $stmt->fetch();

if (!$borrower) {
    die("Borrower information not found.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store all form data in session for preview
    $_SESSION['co_form_data'] = $_POST;

    // Process file uploads
    $upload_errors = [];

    // Process ID front
    if (!empty($_FILES['co_id_front']['name'])) {
        $co_id_front_path = uploadFile($_FILES['co_id_front'], 'ids');
        if (!$co_id_front_path) {
            $upload_errors[] = "Failed to upload ID front photo. Please check file type and size.";
        } else {
            $_SESSION['co_id_front_path'] = $co_id_front_path;
        }
    } else {
        $upload_errors[] = "ID front photo is required.";
    }

    // Process ID back
    if (!empty($_FILES['co_id_back']['name'])) {
        $co_id_back_path = uploadFile($_FILES['co_id_back'], 'ids');
        if (!$co_id_back_path) {
            $upload_errors[] = "Failed to upload ID back photo. Please check file type and size.";
        } else {
            $_SESSION['co_id_back_path'] = $co_id_back_path;
        }
    } else {
        $upload_errors[] = "ID back photo is required.";
    }

    // Handle signature
    $co_signature_path = '';
    if (!empty($_POST['co_signature_data'])) {
        $co_signature_path = saveSignatureFromData($_POST['co_signature_data']);
        if ($co_signature_path) {
            $_SESSION['co_signature_path'] = $co_signature_path;
        }
    } elseif (!empty($_FILES['co_signature_upload']['name'])) {
        $co_signature_path = uploadFile($_FILES['co_signature_upload'], 'signatures');
        if ($co_signature_path) {
            $_SESSION['co_signature_path'] = $co_signature_path;
        }
    }

    // Check if signature is provided
    if (empty($co_signature_path)) {
        $upload_errors[] = "Please provide a signature either by drawing or uploading an image.";
    }

    // If there are upload errors, show them and don't redirect
    if (!empty($upload_errors)) {
        $_SESSION['error'] = implode("<br>", $upload_errors);
    } else {
        // Redirect to preview page
        header("Location: coborrower_preview.php");
        exit();
    }
}

// File upload function (same as in borrower form)
function uploadFile($file, $type)
{
    $target_dir = "../assets/$type/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;

    // Check file size (max 3MB for IDs, 2MB for signatures)
    $max_size = ($type === 'ids') ? 3000000 : 2000000;
    if ($file["size"] > $max_size) {
        $_SESSION['error'] = "File too large. Maximum size is " . ($max_size / 1000000) . "MB.";
        return false;
    }

    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['error'] = "Only JPG, JPEG, and PNG files are allowed.";
        return false;
    }

    // Check if image file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        return false;
    }

    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }

    $_SESSION['error'] = "Sorry, there was an error uploading your file.";
    return false;
}

// Signature data URL function (same as in borrower form)
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Co-borrower Form - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        .card-header {
            background-color: #0d6efd;
            color: white;
        }

        .signature-pad-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
            height: 200px;
        }

        #co-signature-pad {
            width: 100%;
            height: 100%;
            cursor: crosshair;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Loan Application System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="coborrower_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Co-borrower Application Form</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Borrower Information Preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Borrower Information (Application to be Guaranteed)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Processing ID</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['processing_id']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Borrower Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Loan Amount</label>
                            <input type="text" class="form-control" value="₱<?php echo htmlspecialchars(number_format($borrower['loan_amount'], 2)); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Loan Term</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($borrower['term_months']); ?> months" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Borrower Address</label>
                            <input type="text" class="form-control" value="<?php
                                                                            echo htmlspecialchars(
                                                                                $borrower['street_address'] . ', ' .
                                                                                    $borrower['barangay'] . ', ' .
                                                                                    $borrower['city'] . ', ' .
                                                                                    $borrower['province'] . ', ' .
                                                                                    $borrower['zip_code']
                                                                            );
                                                                            ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($borrower['contact_number']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($borrower['email']); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Co-borrower Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Co-borrower Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="co_first_name" name="co_first_name"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_first_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="co_middle_name" name="co_middle_name"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_middle_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="co_last_name" name="co_last_name"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_last_name'] ?? $_SESSION['co_borrower_last_name'] ?? ''); ?>" required readonly>
                                <div class="form-text">This field is pre-filled and cannot be changed.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_name_extension" class="form-label">Name Extension (Jr., Sr., III)</label>
                                <input type="text" class="form-control" id="co_name_extension" name="co_name_extension"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_name_extension'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="co_birthdate" class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="co_birthdate" name="co_birthdate"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_birthdate'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="co_contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="co_contact_number" name="co_contact_number"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_contact_number'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="co_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="co_email" name="co_email"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Co-borrower Address Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Co-borrower Address Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_region" class="form-label">Region</label>
                                <select class="form-select" id="co_region" name="co_region" required>
                                    <option value="">Select Region</option>
                                    <!-- Regions will be loaded via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_province" class="form-label">Province</label>
                                <select class="form-select" id="co_province" name="co_province" required disabled>
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_city" class="form-label">City/Municipality</label>
                                <select class="form-select" id="co_city" name="co_city" required disabled>
                                    <option value="">Select City/Municipality</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="co_barangay" class="form-label">Barangay</label>
                                <select class="form-select" id="co_barangay" name="co_barangay" required disabled>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co_street_address" class="form-label">Street/Purok/Village</label>
                                <input type="text" class="form-control" id="co_street_address" name="co_street_address"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_street_address'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co_zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="co_zip_code" name="co_zip_code"
                                    value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_zip_code'] ?? ''); ?>" required maxlength="4">
                                <div class="form-text">Enter 4-digit zip code</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden fields to store the actual names (not just codes) -->
                    <input type="hidden" id="co_region_name" name="co_region_name" value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_region_name'] ?? ''); ?>">
                    <input type="hidden" id="co_province_name" name="co_province_name" value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_province_name'] ?? ''); ?>">
                    <input type="hidden" id="co_city_name" name="co_city_name" value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_city_name'] ?? ''); ?>">
                    <input type="hidden" id="co_barangay_name" name="co_barangay_name" value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_barangay_name'] ?? ''); ?>">
                </div>
            </div>

            <!-- Co-borrower Identification -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Identification</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co_id_type" class="form-label">ID Type</label>
                                <select class="form-select" id="co_id_type" name="co_id_type" required>
                                    <option value="">Select ID Type</option>
                                    <?php
                                    $id_types = [
                                        "Driver's License",
                                        "Passport",
                                        "SSS ID",
                                        "GSIS ID",
                                        "PhilHealth ID",
                                        "TIN ID",
                                        "Postal ID",
                                        "Voter's ID",
                                        "PRC ID",
                                        "Senior Citizen ID",
                                        "UMID"
                                    ];
                                    foreach ($id_types as $type):
                                        $selected = ($_SESSION['co_form_data']['co_id_type'] ?? '') == $type ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $type; ?>" <?php echo $selected; ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co_id_front" class="form-label">ID Front Photo</label>
                                <input type="file" class="form-control" id="co_id_front" name="co_id_front" accept=".jpg,.jpeg,.png" required>
                                <div class="form-text">Allowed file type: jpg, jpeg, png only; Max size: 3MB only.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co_id_back" class="form-label">ID Back Photo</label>
                                <input type="file" class="form-control" id="co_id_back" name="co_id_back" accept=".jpg,.jpeg,.png" required>
                                <div class="form-text">Allowed file type: jpg, jpeg, png only; Max size: 3MB only.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Co-borrower Signature -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Signature</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Draw Your Signature</label>
                                <div class="signature-pad-container">
                                    <canvas id="co-signature-pad" width="600" height="200"></canvas>
                                </div>
                                <div class="form-text">Sign using your mouse, touchscreen, or stylus</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <button type="button" id="co-clear-signature" class="btn btn-outline-danger">
                                    <i class="bi bi-eraser"></i> Clear
                                </button>
                                <button type="button" id="co-undo-signature" class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-counterclockwise"></i> Undo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative upload option -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="co-signature-upload" class="form-label">Or Upload Signature (PNG)</label>
                                <input type="file" class="form-control" id="co-signature-upload" name="co_signature_upload" accept="image/png">
                                <div class="form-text">Upload a PNG image if you cannot use the signature pad. Max file size: 2MB.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field to store signature data -->
                    <input type="hidden" id="co-signature-data" name="co_signature_data" value="<?php echo htmlspecialchars($_SESSION['co_form_data']['co_signature_data'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Preview Co-borrower Application</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PSGC API Base URL
        const PSGC_API = 'https://psgc.gitlab.io/api';

        // Load regions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRegions();
            toggleEmploymentDetails();

            // Pre-select address dropdowns if values exist
            const regionValue = "<?php echo $_SESSION['co_form_data']['co_region'] ?? ''; ?>";
            if (regionValue) {
                // Trigger change event to load provinces
                setTimeout(() => {
                    document.getElementById('co_region').value = regionValue;
                    document.getElementById('co_region').dispatchEvent(new Event('change'));

                    // Set other values after a delay to allow loading
                    setTimeout(() => {
                        document.getElementById('co_province').value = "<?php echo $_SESSION['co_form_data']['co_province'] ?? ''; ?>";
                        document.getElementById('co_province').dispatchEvent(new Event('change'));

                        setTimeout(() => {
                            document.getElementById('co_city').value = "<?php echo $_SESSION['co_form_data']['co_city'] ?? ''; ?>";
                            document.getElementById('co_city').dispatchEvent(new Event('change'));

                            setTimeout(() => {
                                document.getElementById('co_barangay').value = "<?php echo $_SESSION['co_form_data']['co_barangay'] ?? ''; ?>";
                            }, 500);
                        }, 500);
                    }, 500);
                }, 100);
            }
        });


        // Load all regions from PSGC API
        async function loadRegions() {
            try {
                const response = await fetch(`${PSGC_API}/regions/`);
                const regions = await response.json();

                const regionSelect = document.getElementById('co_region');
                regionSelect.innerHTML = '<option value="">Select Region</option>';

                regions.forEach(region => {
                    regionSelect.innerHTML += `<option value="${region.code}">${region.name}</option>`;
                });

                // Set pre-selected value if exists
                const selectedRegion = "<?php echo $_SESSION['co_form_data']['co_region'] ?? ''; ?>";
                if (selectedRegion) {
                    regionSelect.value = selectedRegion;
                }
            } catch (error) {
                console.error('Error loading regions:', error);
                alert('Failed to load regions. Please refresh the page.');
            }
        }

        // Load provinces based on selected region
        document.getElementById('co_region').addEventListener('change', async function() {
            const regionCode = this.value;
            const provinceSelect = document.getElementById('co_province');
            const citySelect = document.getElementById('co_city');
            const barangaySelect = document.getElementById('co_barangay');

            // Store region name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('co_region_name').value = selectedOption.text;

            if (regionCode) {
                try {
                    provinceSelect.disabled = true;
                    provinceSelect.innerHTML = '<option value="">Loading provinces...</option>';

                    const response = await fetch(`${PSGC_API}/regions/${regionCode}/provinces/`);
                    const provinces = await response.json();

                    provinceSelect.innerHTML = '<option value="">Select Province</option>';
                    provinces.forEach(province => {
                        provinceSelect.innerHTML += `<option value="${province.code}">${province.name}</option>`;
                    });
                    provinceSelect.disabled = false;

                } catch (error) {
                    console.error('Error loading provinces:', error);
                    provinceSelect.innerHTML = '<option value="">Error loading provinces</option>';
                }
            } else {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }

            // Reset downstream selects
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;
        });

        // Load cities based on selected province
        document.getElementById('co_province').addEventListener('change', async function() {
            const provinceCode = this.value;
            const citySelect = document.getElementById('co_city');
            const barangaySelect = document.getElementById('co_barangay');

            // Store province name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('co_province_name').value = selectedOption.text;

            if (provinceCode) {
                try {
                    citySelect.disabled = true;
                    citySelect.innerHTML = '<option value="">Loading cities/municipalities...</option>';

                    const response = await fetch(`${PSGC_API}/provinces/${provinceCode}/cities-municipalities/`);
                    const cities = await response.json();

                    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                    cities.forEach(city => {
                        citySelect.innerHTML += `<option value="${city.code}">${city.name}</option>`;
                    });
                    citySelect.disabled = false;

                } catch (error) {
                    console.error('Error loading cities:', error);
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                }
            } else {
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }

            // Reset downstream selects
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;
        });

        // Load barangays based on selected city
        document.getElementById('co_city').addEventListener('change', async function() {
            const cityCode = this.value;
            const barangaySelect = document.getElementById('co_barangay');

            // Store city name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('co_city_name').value = selectedOption.text;

            if (cityCode) {
                try {
                    barangaySelect.disabled = true;
                    barangaySelect.innerHTML = '<option value="">Loading barangays...</option>';

                    const response = await fetch(`${PSGC_API}/cities-municipalities/${cityCode}/barangays/`);
                    const barangays = await response.json();

                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    barangays.forEach(barangay => {
                        barangaySelect.innerHTML += `<option value="${barangay.code}">${barangay.name}</option>`;
                    });
                    barangaySelect.disabled = false;

                } catch (error) {
                    console.error('Error loading barangays:', error);
                    barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
                }
            } else {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }
        });

        // Store barangay name when selected
        document.getElementById('co_barangay').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('co_barangay_name').value = selectedOption.text;
        });

        // Add zip code validation
        document.getElementById('co_zip_code').addEventListener('input', function() {
            const zipCode = this.value;
            // Remove any non-digit characters
            this.value = zipCode.replace(/\D/g, '');

            // Limit to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
        });

        // Signature Pad functionality
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('co-signature-pad');
            const clearBtn = document.getElementById('co-clear-signature');
            const undoBtn = document.getElementById('co-undo-signature');
            const signatureData = document.getElementById('co-signature-data');
            const signatureUpload = document.getElementById('co-signature-upload');

            // Initialize signature pad
            const signaturePad = new SignaturePad(canvas, {
                minWidth: 1,
                maxWidth: 3,
                penColor: "rgb(0, 0, 0)",
                backgroundColor: "rgb(255, 255, 255)"
            });

            // Adjust canvas size for high DPI displays
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                // Redraw existing signature if any
                if (signatureData.value) {
                    const img = new Image();
                    img.onload = function() {
                        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    };
                    img.src = signatureData.value;
                }
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            // Clear signature
            clearBtn.addEventListener('click', function() {
                signaturePad.clear();
                signatureData.value = '';
            });

            // Undo last stroke
            undoBtn.addEventListener('click', function() {
                const data = signaturePad.toData();
                if (data) {
                    data.pop(); // Remove the last stroke
                    signaturePad.fromData(data);
                    updateSignatureData();
                }
            });

            // Update hidden field when signature changes
            signaturePad.addEventListener('endStroke', function() {
                updateSignatureData();
            });

            function updateSignatureData() {
                if (!signaturePad.isEmpty()) {
                    signatureData.value = signaturePad.toDataURL('image/png');
                } else {
                    signatureData.value = '';
                }
            }

            // Handle file upload alternative
            signatureUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        signatureData.value = event.target.result;
                        // Also display the uploaded image on the canvas
                        const img = new Image();
                        img.onload = function() {
                            const ctx = canvas.getContext('2d');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        };
                        img.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Pre-load signature if it exists
            if (signatureData.value) {
                const img = new Image();
                img.onload = function() {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                };
                img.src = signatureData.value;
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const signatureData = document.getElementById('co-signature-data');
            const signatureUpload = document.getElementById('co-signature-upload');

            // Check if signature is provided
            if (signatureData.value === '' && signatureUpload.files.length === 0) {
                e.preventDefault();
                alert('Please provide a signature either by drawing or uploading an image.');
                return false;
            }

            // Validate zip code
            const zipCode = document.getElementById('co_zip_code').value;
            if (zipCode.length !== 4) {
                e.preventDefault();
                alert('Please enter a valid 4-digit zip code.');
                document.getElementById('co_zip_code').focus();
                return false;
            }

            // Show loading indicator
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        });
    </script>
</body>

</html>