<?php
include('../includes/config.php');
include('../includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM lender_settings ORDER BY id DESC LIMIT 1");
$lender_settings = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lender_name = $_POST['lender_name'];
    $lender_age = $_POST['lender_age'];
    $lender_address = $_POST['lender_address'];
    $gcash_number = $_POST['gcash_number'];
    $lbp_account_number = $_POST['lbp_account_number'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $interest_rate = $_POST['interest_rate'];
    $penalty_rate = $_POST['penalty_rate'];
    $max_loan_term = $_POST['max_loan_term'];

    // Handle signature upload
    $lender_signature_path = $lender_settings['lender_signature_path'] ?? '';

    if (!empty($_POST['lender_signature_data'])) {
        // Signature was drawn on the pad
        $lender_signature_path = saveSignatureFromData($_POST['lender_signature_data'], 'lender_signatures');
    } elseif (!empty($_FILES['lender_signature_upload']['name'])) {
        // Signature was uploaded as file
        $lender_signature_path = uploadFile($_FILES['lender_signature_upload'], 'lender_signatures');
    }

    if ($lender_settings) {
        // Update existing settings
        $stmt = $pdo->prepare("UPDATE lender_settings SET 
            lender_name = ?, lender_age = ?, lender_address = ?, 
            gcash_number = ?, lbp_account_number = ?, contact_number = ?, 
            email = ?, interest_rate = ?, penalty_rate = ?, max_loan_term = ?,
            lender_signature_path = ?, updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([
            $lender_name,
            $lender_age,
            $lender_address,
            $gcash_number,
            $lbp_account_number,
            $contact_number,
            $email,
            $interest_rate,
            $penalty_rate,
            $max_loan_term,
            $lender_signature_path,
            $lender_settings['id']
        ]);
    } else {
        // Insert new settings
        $stmt = $pdo->prepare("INSERT INTO lender_settings 
            (lender_name, lender_age, lender_address, gcash_number, lbp_account_number, 
             contact_number, email, interest_rate, penalty_rate, max_loan_term, lender_signature_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $lender_name,
            $lender_age,
            $lender_address,
            $gcash_number,
            $lbp_account_number,
            $contact_number,
            $email,
            $interest_rate,
            $penalty_rate,
            $max_loan_term,
            $lender_signature_path
        ]);
    }

    $success = "Lender settings updated successfully!";

    // Refresh settings
    $stmt = $pdo->query("SELECT * FROM lender_settings ORDER BY id DESC LIMIT 1");
    $lender_settings = $stmt->fetch();
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

// Signature data handling function
function saveSignatureFromData($data_url, $type)
{
    $target_dir = "../assets/$type/";
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
    <title>Lender Configuration - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-pad-container {
            position: relative;
            height: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        .signature-image {
            max-width: 250px;
            max-height: 80px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Loan Application System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="generate_id.php"><i class="bi bi-plus-circle"></i> Generate ID</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php"><i class="bi bi-list-check"></i> Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="lender_config.php"><i class="bi bi-gear"></i> Lender Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Lender Configuration</h2>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">Lender Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lender_name" class="form-label">Lender Name</label>
                                <input type="text" class="form-control" id="lender_name" name="lender_name"
                                    value="<?php echo htmlspecialchars($lender_settings['lender_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lender_age" class="form-label">Lender Age</label>
                                <input type="number" class="form-control" id="lender_age" name="lender_age"
                                    value="<?php echo htmlspecialchars($lender_settings['lender_age'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lender_address" class="form-label">Complete Address</label>
                        <textarea class="form-control" id="lender_address" name="lender_address" rows="3" required><?php echo htmlspecialchars($lender_settings['lender_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number"
                                    value="<?php echo htmlspecialchars($lender_settings['contact_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($lender_settings['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Payment Methods</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gcash_number" class="form-label">GCash Number</label>
                                <input type="text" class="form-control" id="gcash_number" name="gcash_number"
                                    value="<?php echo htmlspecialchars($lender_settings['gcash_number'] ?? ''); ?>">
                                <div class="form-text">Format: 09123456789</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lbp_account_number" class="form-label">Land Bank Account Number</label>
                                <input type="text" class="form-control" id="lbp_account_number" name="lbp_account_number"
                                    value="<?php echo htmlspecialchars($lender_settings['lbp_account_number'] ?? ''); ?>">
                                <div class="form-text">Format: 1234-5678-9012</div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Loan Terms</h5>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="interest_rate" class="form-label">Interest Rate (% per month)</label>
                                <input type="number" class="form-control" id="interest_rate" name="interest_rate"
                                    value="<?php echo htmlspecialchars($lender_settings['interest_rate'] ?? '10.00'); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="penalty_rate" class="form-label">Penalty Rate (% per day)</label>
                                <input type="number" class="form-control" id="penalty_rate" name="penalty_rate"
                                    value="<?php echo htmlspecialchars($lender_settings['penalty_rate'] ?? '2.00'); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_loan_term" class="form-label">Maximum Loan Term (months)</label>
                                <input type="number" class="form-control" id="max_loan_term" name="max_loan_term"
                                    value="<?php echo htmlspecialchars($lender_settings['max_loan_term'] ?? '10'); ?>" min="1" required>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Lender Signature</h5>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Draw Lender Signature</label>
                                <div class="signature-pad-container">
                                    <canvas id="lender-signature-pad" width="600" height="200"
                                        style="touch-action: none; width: 100%; height: 100%; cursor: crosshair;"></canvas>
                                </div>
                                <div class="form-text">Sign using your mouse, touchscreen, or stylus</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <button type="button" id="clear-lender-signature" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-eraser"></i> Clear
                                </button>
                                <button type="button" id="undo-lender-signature" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-arrow-counterclockwise"></i> Undo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative upload option -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lender-signature-upload" class="form-label">Or Upload Signature (PNG)</label>
                                <input type="file" class="form-control" id="lender-signature-upload" name="lender_signature_upload" accept="image/png">
                                <div class="form-text">Upload a PNG image if you cannot use the signature pad. Max file size: 2MB.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Show current signature if exists -->
                    <?php if (!empty($lender_settings['lender_signature_path']) && file_exists($lender_settings['lender_signature_path'])): ?>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Signature</label>
                                    <div>
                                        <img src="<?php echo $lender_settings['lender_signature_path']; ?>"
                                            class="signature-image"
                                            alt="Current Lender Signature">
                                        <div class="form-text">Existing signature on file</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Hidden field to store signature data -->
                    <input type="hidden" id="lender-signature-data" name="lender_signature_data" value="">

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title">Preview in Agreement</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Note:</strong> This information will appear in the generated loan agreements.
                </div>

                <?php if ($lender_settings): ?>
                    <div class="border p-3 rounded">
                        <h6>LENDER: <?php echo htmlspecialchars($lender_settings['lender_name']); ?>, of legal age,
                            <?php echo htmlspecialchars($lender_settings['lender_age']); ?> years old, Filipino, with residence at
                            <?php echo htmlspecialchars($lender_settings['lender_address']); ?></h6>

                        <h6 class="mt-3">Payment Methods:</h6>
                        <ul>
                            <li><strong>GCash:</strong> <?php echo htmlspecialchars($lender_settings['gcash_number']); ?></li>
                            <li><strong>Land Bank:</strong> <?php echo htmlspecialchars($lender_settings['lbp_account_number']); ?></li>
                        </ul>

                        <h6 class="mt-3">Loan Terms:</h6>
                        <ul>
                            <li><strong>Interest Rate:</strong> <?php echo htmlspecialchars($lender_settings['interest_rate']); ?>% per month</li>
                            <li><strong>Penalty Rate:</strong> <?php echo htmlspecialchars($lender_settings['penalty_rate']); ?>% per day for late payments</li>
                            <li><strong>Maximum Term:</strong> <?php echo htmlspecialchars($lender_settings['max_loan_term']); ?> months</li>
                        </ul>

                        <h6 class="mt-3">Lender Signature:</h6>
                        <?php if (!empty($lender_settings['lender_signature_path']) && file_exists($lender_settings['lender_signature_path'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo $lender_settings['lender_signature_path']; ?>"
                                    class="signature-image"
                                    alt="Lender Signature">
                                <p class="mt-2"><strong><?php echo htmlspecialchars($lender_settings['lender_name']); ?></strong></p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No signature configured yet.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No lender settings configured yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lender Signature Pad functionality
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('lender-signature-pad');
            const clearBtn = document.getElementById('clear-lender-signature');
            const undoBtn = document.getElementById('undo-lender-signature');
            const signatureData = document.getElementById('lender-signature-data');

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
                signaturePad.clear();
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
                    data.pop();
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
            const signatureUpload = document.getElementById('lender-signature-upload');
            signatureUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        signatureData.value = event.target.result;
                        // Display the uploaded image on the canvas
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
                updateSignatureData();
                return true;
            });
        });
    </script>
</body>

</html>