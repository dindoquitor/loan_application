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

// Get Philippine regions
$regions = $pdo->query("SELECT * FROM ph_regions ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form data
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $name_extension = $_POST['name_extension'];
    $birthdate = $_POST['birthdate'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $region_id = $_POST['region'];
    $province_id = $_POST['province'];
    $city_id = $_POST['city'];
    $barangay_id = $_POST['barangay'];
    $street_address = $_POST['street_address'];
    $loan_amount = $_POST['loan_amount'];
    $term_months = $_POST['term_months'];
    $id_type = $_POST['id_type'];

    // Get region, province, city, barangay names
    $region_name = $pdo->query("SELECT name FROM ph_regions WHERE id = $region_id")->fetchColumn();
    $province_name = $pdo->query("SELECT name FROM ph_provinces WHERE id = $province_id")->fetchColumn();
    $city_name = $pdo->query("SELECT name FROM ph_cities WHERE id = $city_id")->fetchColumn();
    $barangay_name = $pdo->query("SELECT name FROM ph_barangays WHERE id = $barangay_id")->fetchColumn();
    $zip_code = $pdo->query("SELECT zip_code FROM ph_barangays WHERE id = $barangay_id")->fetchColumn();

    // Handle file uploads
    $id_front_path = '';
    $id_back_path = '';
    $signature_path = '';

    // Upload ID front
    if (!empty($_FILES['id_front']['name'])) {
        $id_front_path = uploadFile($_FILES['id_front'], 'ids');
    }

    // Upload ID back
    if (!empty($_FILES['id_back']['name'])) {
        $id_back_path = uploadFile($_FILES['id_back'], 'ids');
    }

    // Upload signature
    if (!empty($_FILES['signature']['name'])) {
        $signature_path = uploadFile($_FILES['signature'], 'signatures');
    }

    // Save applicant data
    $stmt = $pdo->prepare("INSERT INTO applicants (
        application_id, first_name, middle_name, last_name, name_extension, 
        birthdate, contact_number, email, region, province, city, barangay, 
        zip_code, street_address, loan_amount, term_months, id_type, 
        id_front_path, id_back_path, signature_path, relationship_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'borrower')");

    $stmt->execute([
        $_SESSION['application_id'],
        $first_name,
        $middle_name,
        $last_name,
        $name_extension,
        $birthdate,
        $contact_number,
        $email,
        $region_name,
        $province_name,
        $city_name,
        $barangay_name,
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

    // Redirect to success page
    $_SESSION['processing_id'] = $processing_id;
    header("Location: success.php");
    exit();
}

// File upload function
function uploadFile($file, $type)
{
    $target_dir = "../assets/$type/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;

    // Check file size (max 2MB)
    if ($file["size"] > 2000000) {
        return false;
    }

    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }

    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
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
    <title>Loan Application Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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

        <form method="POST" enctype="multipart/form-data">
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
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $_SESSION['last_name']; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="name_extension" class="form-label">Name Extension (Jr., Sr., III)</label>
                                <input type="text" class="form-control" id="name_extension" name="name_extension">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['id']; ?>"><?php echo $region['name']; ?></option>
                                    <?php endforeach; ?>
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
                                <input type="text" class="form-control" id="street_address" name="street_address" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" readonly>
                            </div>
                        </div>
                    </div>
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
                                <input type="number" class="form-control" id="loan_amount" name="loan_amount" min="1000" step="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="term_months" class="form-label">Loan Term (months)</label>
                                <select class="form-select" id="term_months" name="term_months" required>
                                    <option value="">Select Term</option>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> month<?php echo $i > 1 ? 's' : ''; ?></option>
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
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="Passport">Passport</option>
                                    <option value="SSS ID">SSS ID</option>
                                    <option value="GSIS ID">GSIS ID</option>
                                    <option value="PhilHealth ID">PhilHealth ID</option>
                                    <option value="TIN ID">TIN ID</option>
                                    <option value="Postal ID">Postal ID</option>
                                    <option value="Voter's ID">Voter's ID</option>
                                    <option value="PRC ID">PRC ID</option>
                                    <option value="Senior Citizen ID">Senior Citizen ID</option>
                                    <option value="UMID">Unified Multi-Purpose ID (UMID)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_front" class="form-label">ID Front Photo</label>
                                <input type="file" class="form-control" id="id_front" name="id_front" accept="image/*" required>
                                <div class="form-text">Max file size: 2MB. Accepted formats: JPG, PNG, PDF</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_back" class="form-label">ID Back Photo</label>
                                <input type="file" class="form-control" id="id_back" name="id_back" accept="image/*" required>
                                <div class="form-text">Max file size: 2MB. Accepted formats: JPG, PNG, PDF</div>
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="signature" class="form-label">Upload Signature (PNG)</label>
                                <input type="file" class="form-control" id="signature" name="signature" accept="image/png">
                                <div class="form-text">Upload a PNG image of your signature. Max file size: 2MB.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for cascading dropdowns
        document.getElementById('region').addEventListener('change', function() {
            const regionId = this.value;
            const provinceSelect = document.getElementById('province');

            if (regionId) {
                fetch('get_provinces.php?region_id=' + regionId)
                    .then(response => response.json())
                    .then(provinces => {
                        provinceSelect.innerHTML = '<option value="">Select Province</option>';
                        provinces.forEach(province => {
                            provinceSelect.innerHTML += `<option value="${province.id}">${province.name}</option>`;
                        });
                        provinceSelect.disabled = false;
                    });
            } else {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = true;
                document.getElementById('city').innerHTML = '<option value="">Select City/Municipality</option>';
                document.getElementById('city').disabled = true;
                document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
                document.getElementById('barangay').disabled = true;
                document.getElementById('zip_code').value = '';
            }
        });

        document.getElementById('province').addEventListener('change', function() {
            const provinceId = this.value;
            const citySelect = document.getElementById('city');

            if (provinceId) {
                fetch('get_cities.php?province_id=' + provinceId)
                    .then(response => response.json())
                    .then(cities => {
                        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                        cities.forEach(city => {
                            citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
                        });
                        citySelect.disabled = false;
                    });
            } else {
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;
                document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
                document.getElementById('barangay').disabled = true;
                document.getElementById('zip_code').value = '';
            }
        });

        document.getElementById('city').addEventListener('change', function() {
            const cityId = this.value;
            const barangaySelect = document.getElementById('barangay');
            const zipCodeInput = document.getElementById('zip_code');

            if (cityId) {
                fetch('get_barangays.php?city_id=' + cityId)
                    .then(response => response.json())
                    .then(barangays => {
                        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                        barangays.forEach(barangay => {
                            barangaySelect.innerHTML += `<option value="${barangay.id}" data-zip="${barangay.zip_code}">${barangay.name}</option>`;
                        });
                        barangaySelect.disabled = false;
                    });
            } else {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                zipCodeInput.value = '';
            }
        });

        document.getElementById('barangay').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('zip_code').value = selectedOption.getAttribute('data-zip') || '';
        });
    </script>
</body>

</html>