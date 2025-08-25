<?php
include('../includes/config.php');
include('../includes/auth_application.php');

// Get application details
$stmt = $pdo->prepare("SELECT * FROM applications WHERE application_id = ?");
$stmt->execute([$_SESSION['application_id']]);
$application = $stmt->fetch();

// Check if application already submitted
if ($application['status'] !== 'generated') {
    header("Location: login.php");
    exit();
}

// Get form data from session if coming back from preview
$form_data = $_SESSION['form_data'] ?? [];
$files = $_SESSION['files'] ?? [];


// FORM PROCESSING - REDIRECT TO PREVIEW INSTEAD OF DIRECT SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store all form data in session for preview
    $_SESSION['form_data'] = $_POST;
    $_SESSION['files'] = $_FILES;

    $upload_errors = [];

    // Process ID front - keep existing or upload new
    if (!empty($_POST['keep_id_front']) && $_POST['keep_id_front'] == '1' && !empty($_SESSION['id_front_path'])) {
        // Keep the existing file
        $id_front_path = $_SESSION['id_front_path'];
    } elseif (!empty($_FILES['id_front']['name'])) {
        // Upload new file
        $id_front_path = uploadFile($_FILES['id_front'], 'ids');
        if (!$id_front_path) {
            $upload_errors[] = "Failed to upload ID front photo. Please check file type and size.";
        }
    } elseif (empty($_SESSION['id_front_path'])) {
        // No file exists and no new file uploaded
        $upload_errors[] = "ID front photo is required.";
    } else {
        // Keep the existing file (default behavior)
        $id_front_path = $_SESSION['id_front_path'];
    }

    // Process ID back - keep existing or upload new
    if (!empty($_POST['keep_id_back']) && $_POST['keep_id_back'] == '1' && !empty($_SESSION['id_back_path'])) {
        // Keep the existing file
        $id_back_path = $_SESSION['id_back_path'];
    } elseif (!empty($_FILES['id_back']['name'])) {
        // Upload new file
        $id_back_path = uploadFile($_FILES['id_back'], 'ids');
        if (!$id_back_path) {
            $upload_errors[] = "Failed to upload ID back photo. Please check file type and size.";
        }
    } elseif (empty($_SESSION['id_back_path'])) {
        // No file exists and no new file uploaded
        $upload_errors[] = "ID back photo is required.";
    } else {
        // Keep the existing file (default behavior)
        $id_back_path = $_SESSION['id_back_path'];
    }

    // Store the file paths in session
    if (!empty($id_front_path)) {
        $_SESSION['id_front_path'] = $id_front_path;
    }
    if (!empty($id_back_path)) {
        $_SESSION['id_back_path'] = $id_back_path;
    }

    // Handle signature
    $signature_path = '';
    if (!empty($_POST['signature_data'])) {
        $signature_path = saveSignatureFromData($_POST['signature_data']);
        if ($signature_path) {
            $_SESSION['signature_path'] = $signature_path;
        }
    } elseif (!empty($_FILES['signature_upload']['name'])) {
        $signature_path = uploadFile($_FILES['signature_upload'], 'signatures');
        if ($signature_path) {
            $_SESSION['signature_path'] = $signature_path;
        }
    }

    // Check if signature is provided
    if (empty($signature_path)) {
        $upload_errors[] = "Please provide a signature either by drawing or uploading an image.";
    }

    // If there are upload errors, show them and don't redirect
    if (!empty($upload_errors)) {
        $_SESSION['error'] = implode("<br>", $upload_errors);
    } else {
        // Store all form data in session for preview
        $_SESSION['form_data'] = $_POST;

        // Redirect to preview page
        header("Location: preview.php");
        exit();
    }
}

// Add this function to handle signature data URL
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

// File upload function
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

// Show success message if form was cleared
if (isset($_GET['cleared']) && $_GET['cleared'] == 1) {
    echo '<div class="alert alert-success">Form has been cleared successfully. You can start over.</div>';
}

?>

<!DOCTYPE html>
<html lang="en">

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Application Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="login.php">Loan Application System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Loan Application Form</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name"
                                    value="<?php echo htmlspecialchars($form_data['middle_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                    value="<?php echo htmlspecialchars($form_data['last_name'] ?? $_SESSION['last_name'] ?? ''); ?>" required readonly>
                                <div class="form-text">This field is pre-filled and cannot be changed.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="name_extension" class="form-label">Name Extension (Jr., Sr., III)</label>
                                <input type="text" class="form-control" id="name_extension" name="name_extension"
                                    value="<?php echo htmlspecialchars($form_data['name_extension'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate"
                                    value="<?php echo htmlspecialchars($form_data['birthdate'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number"
                                    value="<?php echo htmlspecialchars($form_data['contact_number'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Address Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="region" class="form-label">Region</label>
                                <select class="form-select" id="region" name="region" required>
                                    <option value="">Select Region</option>
                                    <!-- Regions will be loaded via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="province" class="form-label">Province</label>
                                <select class="form-select" id="province" name="province" required disabled>
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="city" class="form-label">City/Municipality</label>
                                <select class="form-select" id="city" name="city" required disabled>
                                    <option value="">Select City/Municipality</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select" id="barangay" name="barangay" required disabled>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="street_address" class="form-label">Street/Purok/Village</label>
                                <input type="text" class="form-control" id="street_address" name="street_address"
                                    value="<?php echo htmlspecialchars($form_data['street_address'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code"
                                    value="<?php echo htmlspecialchars($form_data['zip_code'] ?? ''); ?>" required maxlength="4">
                                <div class="form-text">Enter 4-digit zip code</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden fields to store the actual names (not just codes) -->
                    <!-- Hidden fields for address names -->
                    <input type="hidden" id="region_name" name="region_name" value="<?php echo htmlspecialchars($form_data['region_name'] ?? ''); ?>">
                    <input type="hidden" id="province_name" name="province_name" value="<?php echo htmlspecialchars($form_data['province_name'] ?? ''); ?>">
                    <input type="hidden" id="city_name" name="city_name" value="<?php echo htmlspecialchars($form_data['city_name'] ?? ''); ?>">
                    <input type="hidden" id="barangay_name" name="barangay_name" value="<?php echo htmlspecialchars($form_data['barangay_name'] ?? ''); ?>">
                </div>
            </div>

            <!-- Loan Details -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Loan Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="loan_amount" class="form-label">Loan Amount (₱)</label>
                                <input type="number" class="form-control" id="loan_amount" name="loan_amount"
                                    value="<?php echo htmlspecialchars($form_data['loan_amount'] ?? ''); ?>" min="1000" step="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="term_months" class="form-label">Loan Term (months)</label>
                                <select class="form-select" id="term_months" name="term_months" required>
                                    <option value="">Select Term</option>
                                    <?php for ($i = 1; $i <= 10; $i++):
                                        $selected = ($form_data['term_months'] ?? '') == $i ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $i; ?>" <?php echo $selected; ?>>
                                            <?php echo $i; ?> month<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Identification -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Identification</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_type" class="form-label">ID Type</label>
                                <select class="form-select" id="id_type" name="id_type" required>
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
                                        $selected = ($form_data['id_type'] ?? '') == $type ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $type; ?>" <?php echo $selected; ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Show previously uploaded files -->
                    <?php if (!empty($_SESSION['id_front_path']) || !empty($_SESSION['id_back_path'])): ?>
                        <div class="alert alert-info">
                            <strong>Previously uploaded files:</strong>
                            <div class="row mt-2">
                                <?php if (!empty($_SESSION['id_front_path'])): ?>
                                    <div class="col-md-6">
                                        <p>ID Front: <a href="<?php echo $_SESSION['id_front_path']; ?>" target="_blank">View File</a></p>
                                        <input type="hidden" name="existing_id_front" value="<?php echo $_SESSION['id_front_path']; ?>">
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($_SESSION['id_back_path'])): ?>
                                    <div class="col-md-6">
                                        <p>ID Back: <a href="<?php echo $_SESSION['id_back_path']; ?>" target="_blank">View File</a></p>
                                        <input type="hidden" name="existing_id_back" value="<?php echo $_SESSION['id_back_path']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="mb-0 mt-2"><small>Upload new files only if you want to replace the existing ones.</small></p>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_front" class="form-label">ID Front Photo</label>
                                <input type="file" class="form-control" id="id_front" name="id_front" accept=".jpg,.jpeg,.png"
                                    <?php echo empty($_SESSION['id_front_path']) ? 'required' : ''; ?>>
                                <p>Allowed file type: jpg, jpeg, png only; Max size: 3MB only.</p>
                                <?php if (!empty($_SESSION['id_front_path'])): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="keep_id_front" name="keep_id_front" value="1" checked>
                                        <label class="form-check-label" for="keep_id_front">
                                            Keep existing ID front photo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_back" class="form-label">ID Back Photo</label>
                                <input type="file" class="form-control" id="id_back" name="id_back" accept=".jpg,.jpeg,.png"
                                    <?php echo empty($_SESSION['id_back_path']) ? 'required' : ''; ?>>
                                <p>Allowed file type: jpg, jpeg, png only; Max size: 3MB only.</p>
                                <?php if (!empty($_SESSION['id_back_path'])): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="keep_id_back" name="keep_id_back" value="1" checked>
                                        <label class="form-check-label" for="keep_id_back">
                                            Keep existing ID back photo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signature -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Signature</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Draw Your Signature</label>
                                <div class="signature-pad-container border rounded" style="position: relative; height: 200px;">
                                    <canvas id="signature-pad" width="600" height="200"
                                        style="touch-action: none; width: 100%; height: 100%; cursor: crosshair;"></canvas>
                                </div>
                                <div class="form-text">Sign using your mouse, touchscreen, or stylus</div>
                            </div>
                        </div>
                    </div>

                    <!-- Show if signature already exists -->
                    <?php if (!empty($form_data['signature_data'])): ?>
                        <div class="alert alert-info">
                            Signature already provided. Draw again if you want to change it.
                        </div>
                    <?php endif; ?>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <button type="button" id="clear-signature" class="btn btn-outline-danger">
                                    <i class="bi bi-eraser"></i> Clear
                                </button>
                                <button type="button" id="undo-signature" class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-counterclockwise"></i> Undo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative upload option -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="signature-upload" class="form-label">Or Upload Signature (PNG)</label>
                                <input type="file" class="form-control" id="signature-upload" name="signature_upload" accept="image/png">
                                <div class="form-text">Upload a PNG image if you cannot use the signature pad. Max file size: 2MB.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field to store signature data -->
                    <input type="hidden" id="signature-data" name="signature_data" value="<?php echo htmlspecialchars($form_data['signature_data'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="d-grid gap-2 mt-3">
                            <a href="clear_form.php" class="btn btn-outline-danger btn-lg"
                                onclick="return confirm('Are you sure you want to clear all form data? This will remove all entered information.')">
                                <i class="bi bi-trash"></i> Clear Form and Start Over
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Preview Application</button>
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
        });

        // Load all regions from PSGC API
        async function loadRegions() {
            try {
                const response = await fetch(`${PSGC_API}/regions/`);
                const regions = await response.json();

                const regionSelect = document.getElementById('region');
                regionSelect.innerHTML = '<option value="">Select Region</option>';

                regions.forEach(region => {
                    regionSelect.innerHTML += `<option value="${region.code}">${region.name}</option>`;
                });
            } catch (error) {
                console.error('Error loading regions:', error);
                alert('Failed to load regions. Please refresh the page.');
            }
        }

        // Load provinces based on selected region
        document.getElementById('region').addEventListener('change', async function() {
            const regionCode = this.value;
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            // Store region name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('region_name').value = selectedOption.text;

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
        document.getElementById('province').addEventListener('change', async function() {
            const provinceCode = this.value;
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');

            // Store province name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('province_name').value = selectedOption.text;

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
        document.getElementById('city').addEventListener('change', async function() {
            const cityCode = this.value;
            const barangaySelect = document.getElementById('barangay');

            // Store city name in hidden field
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('city_name').value = selectedOption.text;

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
        document.getElementById('barangay').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('barangay_name').value = selectedOption.text;
        });

        // Add zip code validation
        document.getElementById('zip_code').addEventListener('input', function() {
            const zipCode = this.value;
            // Remove any non-digit characters
            this.value = zipCode.replace(/\D/g, '');

            // Limit to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
        });

        // Add form validation for zip code
        document.querySelector('form').addEventListener('submit', function(e) {

            updateSignatureData();

            const zipCode = document.getElementById('zip_code').value;
            if (zipCode.length !== 4) {
                e.preventDefault();
                alert('Please enter a valid 4-digit zip code.');
                document.getElementById('zip_code').focus();
            }

            if (signatureData.value === '' && signatureUpload.files.length === 0) {
                e.preventDefault();
                alert('Please provide a signature either by drawing or uploading an image.');
                return false;
            }

            // Show loading indicator (optional)
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        });

        // Signature Pad functionality
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signature-pad');
            const clearBtn = document.getElementById('clear-signature');
            const undoBtn = document.getElementById('undo-signature');
            const signatureData = document.getElementById('signature-data');

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
                signaturePad.clear(); // Clear and redraw on resize
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
            const signatureUpload = document.getElementById('signature-upload');
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

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                if (signatureData.value === '' && signatureUpload.files.length === 0) {
                    e.preventDefault();
                    alert('Please provide a signature either by drawing or uploading an image.');
                    return false;
                }
            });
        });

        // Pre-load signature if it exists
        document.addEventListener('DOMContentLoaded', function() {
            const signatureData = document.getElementById('signature-data').value;
            if (signatureData) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.getElementById('signature-pad');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                };
                img.src = signatureData;
            }

            // Pre-select address dropdowns if values exist
            const regionValue = "<?php echo $form_data['region'] ?? ''; ?>";
            if (regionValue) {
                // Trigger change event to load provinces
                setTimeout(() => {
                    document.getElementById('region').value = regionValue;
                    document.getElementById('region').dispatchEvent(new Event('change'));

                    // Set other values after a delay to allow loading
                    setTimeout(() => {
                        document.getElementById('province').value = "<?php echo $form_data['province'] ?? ''; ?>";
                        document.getElementById('province').dispatchEvent(new Event('change'));

                        setTimeout(() => {
                            document.getElementById('city').value = "<?php echo $form_data['city'] ?? ''; ?>";
                            document.getElementById('city').dispatchEvent(new Event('change'));

                            setTimeout(() => {
                                document.getElementById('barangay').value = "<?php echo $form_data['barangay'] ?? ''; ?>";
                            }, 500);
                        }, 500);
                    }, 500);
                }, 100);
            }
        });

        function clearForm() {
            if (confirm('Are you sure you want to clear all form data?')) {
                // Clear session data
                fetch('clear_form.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

        // Add this to your JavaScript section
        document.addEventListener('DOMContentLoaded', function() {
            // Handle ID front checkbox
            const keepIdFront = document.getElementById('keep_id_front');
            const idFrontInput = document.getElementById('id_front');

            if (keepIdFront && idFrontInput) {
                keepIdFront.addEventListener('change', function() {
                    idFrontInput.disabled = this.checked;
                    idFrontInput.required = !this.checked;
                });

                // Initialize state
                idFrontInput.disabled = keepIdFront.checked;
                idFrontInput.required = !keepIdFront.checked;
            }

            // Handle ID back checkbox
            const keepIdBack = document.getElementById('keep_id_back');
            const idBackInput = document.getElementById('id_back');

            if (keepIdBack && idBackInput) {
                keepIdBack.addEventListener('change', function() {
                    idBackInput.disabled = this.checked;
                    idBackInput.required = !this.checked;
                });

                // Initialize state
                idBackInput.disabled = keepIdBack.checked;
                idBackInput.required = !keepIdBack.checked;
            }
        });
    </script>