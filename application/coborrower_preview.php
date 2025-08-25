<?php

include('../includes/coborrower_auth.php');

// Get application details
$stmt = $pdo->prepare("SELECT * FROM applications WHERE application_id = ?");
$stmt->execute([$_SESSION['co_borrower_application_id']]);
$application = $stmt->fetch();

// Get borrower information
$stmt = $pdo->prepare("SELECT * FROM applicants WHERE application_id = ? AND relationship_type = 'borrower'");
$stmt->execute([$_SESSION['co_borrower_application_id']]);
$borrower = $stmt->fetch();

// Get lender settings
$stmt = $pdo->query("SELECT * FROM lender_settings ORDER BY id DESC LIMIT 1");
$lender = $stmt->fetch();

// Get co-borrower form data from session
$co_form_data = $_SESSION['co_form_data'] ?? [];

if (empty($co_form_data)) {
    header("Location: coborrower_form.php");
    exit();
}

$co_id_front_path = $_SESSION['co_id_front_path'] ?? '';
$co_id_back_path = $_SESSION['co_id_back_path'] ?? '';
$co_signature_path = $_SESSION['co_signature_path'] ?? '';

// Check if signature exists
$has_co_signature = !empty($co_signature_path) || !empty($co_form_data['co_signature_data']);

// Calculate co-borrower age
$co_birthdate = $co_form_data['co_birthdate'] ?? '';
$co_age = '';
if (!empty($co_birthdate)) {
    $co_age = floor((time() - strtotime($co_birthdate)) / 31556926);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Agreement - Loan Application System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        p {
            margin-bottom: 0px !important;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f9f9f9;
        }

        .agreement-container {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        h2 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-top: 25px;
        }

        .underline {
            text-decoration: underline;
        }

        .signature-section {
            margin-top: 50px;
        }

        .data-field {
            display: inline-block;
            min-width: 20px;
            border-bottom: 2px solid #000;
        }

        .btn-container {
            margin-top: 20px;
            text-align: center;
        }

        .agreement-content {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .signature-image {
            max-width: 250px;
            max-height: 80px;
        }

        .signature-card {
            max-width: 300px;
            text-align: center;
        }

        @media print {
            .no-print {
                display: none;
            }

            .agreement-container {
                box-shadow: none;
                padding: 0;
            }
        }

        .paragraph {
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="coborrower_form.php">Loan Application Preview</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Loan Agreement Preview</h2>

        <div class="alert alert-info">
            Please review your loan agreement carefully before submitting. This is a legally binding document.
        </div>

        <div class="agreement-container">
            <div class="agreement-content">
                <h1 class="text-center">PERSONAL LOAN AGREEMENT</h1>

                <p>This Personal Loan Agreement (the "Agreement") is made and entered into on this
                    <span class="data-field"><?php echo date('d'); ?></span> day of
                    <span class="data-field"><?php echo date('F'); ?></span>, <?php echo date('Y'); ?>, by and between:
                </p>
                <div class="paragraph">
                    <p><strong>LENDER: <span class="data-field"><?php echo htmlspecialchars($lender['lender_name'] ?? 'Lender Name'); ?></span></strong>, of legal age,
                        <span class="data-field"><?php echo htmlspecialchars($lender['lender_age'] ?? ''); ?></span> years old, Filipino, with residence at
                        <strong><span class="data-field"><?php echo htmlspecialchars($lender['lender_address'] ?? ''); ?></span></strong> (hereinafter referred to as the "Lender");
                    </p>
                </div>

                <p>-and-</p>

                <div class="paragraph">
                    <p><strong>BORROWER: <span class="data-field"><?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name'] . ' ' . $borrower['name_extension']); ?></span></strong>, of legal age,
                        <span class="data-field"><?php echo floor((time() - strtotime($borrower['birthdate'])) / 31556926); ?></span> years old, Filipino, with residence at
                        <strong> <span class="data-field"><?php echo htmlspecialchars($borrower['street_address'] . ', ' . $borrower['barangay'] . ', ' . $borrower['city'] . ', ' . $borrower['province'] . ', ' . $borrower['zip_code']); ?></span> </strong> (hereinafter referred to as the "Borrower").
                    </p>
                </div>
                <div class="paragraph">
                    <p>Contact Number: <span class="data-field"><?php echo htmlspecialchars($borrower['contact_number']); ?></span></p>
                    <p>Email Address: <span class="data-field"><?php echo htmlspecialchars($borrower['email']); ?></span></p>
                </div>

                <p>-and-</p>

                <div class="paragraph">
                    <p><strong>CO-BORROWER: <span class="data-field"><?php echo htmlspecialchars($co_form_data['co_first_name'] . ' ' . $co_form_data['co_middle_name'] . ' ' . $co_form_data['co_last_name'] . ' ' . $co_form_data['co_name_extension']); ?></span></strong>, of legal age,
                        <span class="data-field"><?php echo $co_age; ?></span> years old, Filipino, with residence at
                        <span class="data-field underline"><?php echo htmlspecialchars($co_form_data['co_street_address'] . ', ' . $co_form_data['co_barangay_name'] . ', ' . $co_form_data['co_city_name'] . ', ' . $co_form_data['co_province_name'] . ', ' . $co_form_data['co_zip_code']); ?></span> (hereinafter referred to as the "Co-Borrower").
                    </p>
                </div>

                <div class="paragraph">
                    <p>Contact Number: <span class="data-field"><?php echo htmlspecialchars($co_form_data['co_contact_number']); ?></span></p>
                    <p>Email Address: <span class="data-field"><?php echo htmlspecialchars($co_form_data['co_email']); ?></span></p>
                </div>

                <div class="paragraph">
                    <p><strong> Below are the details of the loan and other terms and conditions:</strong></p>
                </div>

                <h4>1. LOAN AMOUNT AND TERMS</h4>
                <div class="paragraph">
                    <ol type="i">
                        <li>
                            The Lender agrees to loan the Borrower the principal sum of
                            <strong>₱<span class="data-field"><?php echo number_format($borrower['loan_amount'], 2); ?>.</span> (The "Principal Amount").</strong>
                        </li>
                        <li>
                            The Borrower agrees to pay interest at the rate of
                            <strong><?php echo htmlspecialchars($lender['interest_rate'] ?? '10'); ?>%</strong> per month,
                            payable on or before the day of each month following the approval of this Agreement.
                        </li>
                        <li>
                            The principal shall be due on the agreed maturity date or within the maximum period of
                            <strong><?php echo htmlspecialchars($lender['max_loan_term'] ?? '10'); ?></strong> months.
                        </li>
                        <li>
                            In the event the Borrower fails to pay the interest or the principal, the Lender may demand payment from the Co-Borrower.
                        </li>
                        <li>
                            Failure to pay on the designated due date shall incur a
                            <strong><?php echo htmlspecialchars($lender['penalty_rate'] ?? '2'); ?>%</strong> penalty per day until fully paid.
                        </li>
                        <li>
                            Any payment made shall first be applied to interest and penalties before being credited to the principal.
                        </li>
                        <li>
                            Should the Borrower and Co-Borrower fail to pay the outstanding amount (both principal and interest), they agree that the Lender may garnish or take personal property equivalent to the amount due, without incurring liability.
                        </li>
                        <li>
                            If the Borrower and Co-Borrower cannot be reached through their provided contact information, the Lender may post their names and pictures on social media platforms solely for the purpose of contacting them, without incurring liability. By signing this Agreement, the Borrower and Co-Borrower expressly consent to such posting for this limited purpose.
                        </li>
                    </ol>
                </div>
                <br>
                <h4>2. MODE OF PAYMENT</h4>

                <p style="margin-left: 30px;">Payments shall be made in cash, via bank transfer, or through Gcash to the Lender's designated accounts as follows:</p>

                <ul style="margin-left: 30px;">
                    <li><strong>Gcash Number:</strong>
                        <span class="data-field"><?php echo htmlspecialchars($lender['gcash_number'] ?? ''); ?></span>
                        (Account Name: <span class="data-field"><?php echo htmlspecialchars($lender['lender_name'] ?? ''); ?></span>)
                    </li>
                    <li><strong>LBP Account Number:</strong>
                        <span class="data-field"><?php echo htmlspecialchars($lender['lbp_account_number'] ?? ''); ?></span>
                        (Account Name: <span class="data-field"><?php echo htmlspecialchars($lender['lender_name'] ?? ''); ?></span>)
                    </li>
                </ul>
                <br>

                <h4>3. REPRESENTATIONS AND WARRANTIES</h4>
                <ol type="i">
                    <li>
                        The Borrower affirms that the loan proceeds shall be used solely for lawful purposes.
                    </li>
                    <li>
                        The Borrower and Co-Borrower guarantee that they have the capacity to repay the loan under the agreed terms.
                    </li>
                </ol>

                <br>

                <h4>4. REMEDIES IN CASE OF DEFAULT</h4>

                <ol type="i">
                    <li>
                        Failure to pay for any installment on its due date for more than thirty (30) days shall constitute default. In the event of default, the Lender may declare the entire unpaid balance immediately due and payable without further notice.
                    </li>
                    <li>
                        The Lender may pursue legal remedies, including but not limited to filing a claim with the Small Claims Court of the Philippines.
                    </li>
                    <li>
                        The Borrower agrees to reimburse the Lender for all expenses incurred in the collection of unpaid amounts, including attorney's fees, court costs, and transportation expenses.
                    </li>
                </ol>
                <br>
                <h4>5. WAIVER AND MODIFICATION</h4>

                <p style="margin-left: 30px;">No waiver or modification of this Agreement shall be valid unless made in writing and signed by all parties.</p>
                <br>
                <h4>6. GOVERNING LAW AND JURISDICTION</h4>

                <ol type="i">
                    <li>
                        This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines.
                    </li>
                    <li>
                        Any dispute arising from this Agreement shall be resolved exclusively in the courts of <strong>Koronadal City, Philippines.</strong>
                    </li>
                </ol>
                <br>
                <h4>7. SEPARABILITY CLAUSE</h4>

                <p style="margin-left: 30px;">If any provision of this Agreement is found to be invalid or unenforceable, the remaining provisions shall continue to be in full force and effect.</p>
                <br>
                <h4>8. LEGAL VALIDITY</h4>

                <p style="margin-left: 30px;">The Parties expressly acknowledge that the essential elements of a valid contract under <strong>Article 1318 of the Civil Code of the Philippines</strong> are present in this Agreement, namely:</p>

                <ul style="margin-left: 30px;">
                    <li><strong>Consent</strong> of the parties freely given;</li>
                    <li><strong>A determinate object</strong>, being the specific Loan Amount stated herein; and</li>
                    <li><strong>Cause of the obligation</strong>, being the Borrower's undertaking and promise to repay the Loan Amount under the agreed terms.</li>
                </ul>
                <br>
                <p><strong>IN WITNESS WHEREOF,</strong> the parties have signed this Agreement on the
                    <strong><span class="data-field"><?php echo date('d'); ?></span></strong> day of <strong><span class="data-field"><?php echo date('F'); ?></span>,</strong>
                    <strong><?php echo date('Y'); ?></strong> at <strong><span class="data-field">Brgy. San Felipe, Tantangan, South Cotabato, 9510</span></strong>.
                </p>

                <!-- LENDER SIGNATURE AREA -->
                <div class="signature-section">
                    <p><strong>LENDER:</strong></p>
                    <div class="signature-card">
                        <img src="<?php echo htmlspecialchars($lender['lender_signature_path']); ?>"
                            class="signature-image text-start"
                            alt="Lender Signature">
                        <p><strong><span class="data-field"><?php echo htmlspecialchars($lender['lender_name'] ?? ''); ?></span></strong></p>
                        <em>Signature over Printed Name</em>
                    </div>
                </div>

                <!-- BORROWER SIGNATURE AREA -->
                <div class="signature-section">
                    <span><strong>BORROWER:</strong></span> <br>
                    <p><em>I, the undersigned Borrower, have read and fully understood the terms and conditions of this Loan Agreement and hereby voluntarily agree to abide by them.</em></p>

                    <div style="margin: 20px 0; text-align: left;">
                        <div class="signature-card">
                            <?php if (!empty($borrower['signature_path'])): ?>
                                <img src="<?php echo htmlspecialchars($borrower['signature_path']); ?>"
                                    class="signature-image text-start"
                                    alt="Borrower Signature">
                            <?php else: ?>
                                <div style="height: 80px; width: 250px; margin-left:30px"></div>
                            <?php endif; ?>
                            <p> <strong>
                                    <span style="padding: 0 40px; margin: 0 5px;" class="data-field"><?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name'] . ' ' . $borrower['name_extension']); ?></span></strong><br>
                            </p>
                            <em>Signature over Printed Name</em>
                        </div>
                    </div>
                </div>

                <!-- CO-BORROWER SIGNATURE AREA -->
                <div class="signature-section">
                    <span><strong>CO-BORROWER:</strong></span> <br>
                    <p><em>I, the undersigned Co-Borrower, have read and fully understood the terms and conditions of this Loan Agreement and hereby voluntarily agree to abide by them.</em></p>

                    <div style="margin: 20px 0; text-align: left;">
                        <div class="signature-card">
                            <?php if ($has_co_signature): ?>
                                <?php if (!empty($co_signature_path)): ?>
                                    <!-- Use uploaded signature file -->
                                    <img src="<?php echo htmlspecialchars($co_signature_path); ?>"
                                        class="signature-image text-start"
                                        alt="Co-Borrower Signature">
                                <?php else: ?>
                                    <!-- Use signature data URL -->
                                    <img src="<?php echo htmlspecialchars($co_form_data['co_signature_data']); ?>"
                                        class="signature-image text-start"
                                        alt="Co-Borrower Signature">
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="height: 80px; width: 250px; margin-left:30px"></div>
                            <?php endif; ?>
                            <p> <strong>
                                    <span style="padding: 0 40px; margin: 0 5px;" class="data-field"><?php echo htmlspecialchars($co_form_data['co_first_name'] . ' ' . $co_form_data['co_middle_name'] . ' ' . $co_form_data['co_last_name'] . ' ' . $co_form_data['co_name_extension']); ?></span></strong><br>
                            </p>
                            <em>Signature over Printed Name</em>
                        </div>
                    </div>
                </div>
            </div>

            <br>
            <hr>
            <!-- IDENTIFICATION DOCUMENTS SECTION -->
            <div class="signature-section mt-50">
                <h4>CO-BORROWER IDENTIFICATION DOCUMENTS</h4>
                <p><strong>Submitted ID Type:</strong> <?php echo htmlspecialchars($co_form_data['co_id_type'] ?? 'Not specified'); ?></p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <p><strong>ID Front Photo:</strong></p>
                            <?php if (!empty($co_id_front_path)): ?>
                                <img src="<?php echo htmlspecialchars($co_id_front_path); ?>"
                                    class="img-thumbnail"
                                    style="max-height: 200px; max-width: 100%;"
                                    alt="Co-Borrower ID Front Photo">
                            <?php else: ?>
                                <div class="alert alert-warning">No ID front photo uploaded</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <p><strong>ID Back Photo:</strong></p>
                            <?php if (!empty($co_id_back_path)): ?>
                                <img src="<?php echo htmlspecialchars($co_id_back_path); ?>"
                                    class="img-thumbnail"
                                    style="max-height: 200px; max-width: 100%;"
                                    alt="Co-Borrower ID Back Photo">
                            <?php else: ?>
                                <div class="alert alert-warning">No ID back photo uploaded</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <br>
            <hr>
            <form method="POST" action="coborrower_submit.php" class="no-print">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I have read and agree to the terms and conditions of this Loan Agreement
                    </label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">Agree and Submit Application</button>
                    <a href="coborrower_form.php?from_preview=1" class="btn btn-outline-secondary">Go Back to Edit</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>