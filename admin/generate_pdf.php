<?php
include('../includes/config.php');
include('../includes/auth.php');

// Add this at the top after including config.php
$stmt = $pdo->query("SELECT * FROM lender_settings ORDER BY id DESC LIMIT 1");
$lender = $stmt->fetch();

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include mPDF library
require_once '../vendor/autoload.php'; // If using Composer
// Or include the manual way if not using Composer

if (isset($_GET['application_id'])) {
    $application_id = $_GET['application_id'];

    // Get application data
    $stmt = $pdo->prepare("SELECT a.*, 
                          borrower.*, 
                          coborrower.first_name as c_first_name, coborrower.middle_name as c_middle_name, 
                          coborrower.last_name as c_last_name, coborrower.name_extension as c_name_extension,
                          coborrower.birthdate as c_birthdate, coborrower.contact_number as c_contact_number,
                          coborrower.email as c_email, coborrower.signature_path as c_signature_path
                          FROM applications a
                          LEFT JOIN applicants borrower ON a.application_id = borrower.application_id AND borrower.relationship_type = 'borrower'
                          LEFT JOIN applicants coborrower ON a.application_id = coborrower.application_id AND coborrower.relationship_type = 'co_borrower'
                          WHERE a.application_id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    if ($application) {
        // Create PDF
        $mpdf = new \Mpdf\Mpdf();

        // In generate_pdf.php, replace the HTML with this exact format:
        $html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.4; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .section { margin-bottom: 15px; }
        .signature-area { margin-top: 50px; }
        .signature-line { border-top: 1px solid #000; width: 300px; margin-bottom: 5px; }
        .terms { font-size: 11px; margin-top: 20px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h2>PERSONAL LOAN AGREEMENT</h2>
        <p>This Personal Loan Agreement (the "Agreement") is made and entered into on this ______ day of ______, 2025</p>
    </div>

    <div class="section">
        <p><strong>LENDER:</strong> [Lender Name], of legal age, [Lender Age] years old, Filipino, with residence at [Lender Address]</p>
        <p><strong>BORROWER:</strong> ' . $application['first_name'] . ' ' . $application['middle_name'] . ' ' . $application['last_name'] . ' ' . $application['name_extension'] . ', of legal age, Filipino, with residence at ' . $application['street_address'] . ', ' . $application['barangay'] . ', ' . $application['city'] . ', ' . $application['province'] . ', ' . $application['region'] . '</p>
        <p><strong>Contact Number:</strong> ' . $application['contact_number'] . '</p>
        <p><strong>Email Address:</strong> ' . $application['email'] . '</p>
        
        <p><strong>CO-BORROWER:</strong> ' . $application['c_first_name'] . ' ' . $application['c_middle_name'] . ' ' . $application['c_last_name'] . ' ' . $application['c_name_extension'] . ', of legal age, Filipino, with residence at [Co-borrower Address]</p>
        <p><strong>Contact Number:</strong> ' . $application['c_contact_number'] . '</p>
        <p><strong>Email Address:</strong> ' . $application['c_email'] . '</p>
    </div>

    <div class="section">
        <h4>1. LOAN AMOUNT AND TERMS</h4>
        <p>i. The Lender agrees to loan the Borrower the principal sum of <strong>₱' . number_format($application['loan_amount'], 2) . '</strong>.</p>
        <p>ii. The Borrower agrees to pay interest at the rate of ten percent (10%) per month.</p>
        <p>iii. The principal shall be due within the maximum period of ' . $application['term_months'] . ' months.</p>
        <!-- Add all other terms from your document -->
    </div>

    <!-- Continue with all sections from your document -->

    <div class="page-break"></div>
    
    <div class="section">
        <h4>IDENTIFICATION DOCUMENTS</h4>
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">';

        if (!empty($application['id_front_path']) && file_exists($application['id_front_path'])) {
            $html .= '<div>
                <p><strong>Front ID:</strong></p>
                <img src="' . $application['id_front_path'] . '" style="max-width: 250px; max-height: 150px; border: 1px solid #ccc;">
            </div>';
        }

        if (!empty($application['id_back_path']) && file_exists($application['id_back_path'])) {
            $html .= '<div>
                <p><strong>Back ID:</strong></p>
                <img src="' . $application['id_back_path'] . '" style="max-width: 250px; max-height: 150px; border: 1px solid #ccc;">
            </div>';
        }

        $html .= '</div>
    </div>

    <div class="signature-area">
        <p>IN WITNESS WHEREOF, the parties have signed this Agreement on the ______ day of ______, 2025</p>
        
        <div style="display: flex; justify-content: space-between; margin-top: 50px;">
            <div>
                <div class="signature-line"></div>
                <p>LENDER: [Lender Name]</p>
            </div>
            
            <div>
                <div class="signature-line"></div>
                <p>BORROWER: ' . $application['first_name'] . ' ' . $application['last_name'] . '</p>';
        if (!empty($application['signature_path']) && file_exists($application['signature_path'])) {
            $html .= '<img src="' . $application['signature_path'] . '" style="height: 60px; margin-top: -40px;">';
        }
        $html .= '</div>
        <div>
                <div class="signature-line"></div>
                <p>CO-BORROWER: ' . $application['c_first_name'] . ' ' . $application['c_last_name'] . '</p>';
        if (!empty($application['c_signature_path']) && file_exists($application['c_signature_path'])) {
            $html .= '<img src="' . $application['c_signature_path'] . '" style="height: 60px; margin-top: -40px;">';
        }
        $html .= '</div>
        </div>
    </div>

    
</body>
</html>';

        $mpdf->WriteHTML($html);

        // Output PDF
        $mpdf->Output('loan_agreement_' . $application_id . '.pdf', 'D');
        exit();
    }
}

header("Location: applications.php");
exit();
