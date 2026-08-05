<?php
/**
 * Smart Water Guardian - Generate Invoice PDF
 * Creates a PDF invoice for water bills
 */

require_once '../vendor/autoload.php'; // For TCPDF or similar

use TCPDF;

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['firstName'] ?? 'User';
$last_name = $_SESSION['lastName'] ?? '';
$email = $_SESSION['email'] ?? '';

// Get bill data from URL
$billId = $_GET['bill_id'] ?? null;
$month = $_GET['month'] ?? date('F Y');
$amount = $_GET['amount'] ?? '0.00';
$usage = $_GET['usage'] ?? '0';
$invoiceNo = $_GET['invoice_no'] ?? 'INV-' . date('Ymd') . rand(1000, 9999);
$dueDate = $_GET['due_date'] ?? date('Y-m-d', strtotime('+30 days'));

if (!$billId) {
    die('Bill ID required');
}

// Create PDF
class PDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(0, 212, 255);
        $this->Cell(0, 15, 'Smart Water Guardian', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 7, 'Water Bill Invoice', 0, 1, 'C');
        $this->Line(10, 32, 200, 32);
        $this->SetY(40);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, '© ' . date('Y') . ' Smart Water Guardian. All rights reserved.', 0, 0, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// Invoice details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'INVOICE DETAILS', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(200, 200, 200);

$pdf->Cell(60, 8, 'Invoice Number:', 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, $invoiceNo, 0, 1);
$pdf->SetTextColor(200, 200, 200);

$pdf->Cell(60, 8, 'Date:', 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, date('Y-m-d'), 0, 1);
$pdf->SetTextColor(200, 200, 200);

$pdf->Cell(60, 8, 'Due Date:', 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, $dueDate, 0, 1);
$pdf->SetTextColor(200, 200, 200);

// Customer details
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'BILL TO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(200, 200, 200);
$pdf->Cell(0, 8, $first_name . ' ' . $last_name, 0, 1);
$pdf->Cell(0, 8, $email, 0, 1);

// Invoice table
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(0, 212, 255, 0.1);
$pdf->SetTextColor(0, 212, 255);

$pdf->Cell(80, 10, 'Description', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Usage (kL)', 1, 0, 'C', true);
$pdf->Cell(60, 10, 'Amount (R)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(20, 25, 45);

$pdf->Cell(80, 10, 'Water Usage - ' . $month, 1, 0, 'L', true);
$pdf->Cell(50, 10, $usage, 1, 0, 'C', true);
$pdf->Cell(60, 10, number_format((float)$amount, 2), 1, 1, 'C', true);

// Total
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(255, 215, 0);
$pdf->Cell(130, 12, 'TOTAL AMOUNT', 1, 0, 'R', true);
$pdf->Cell(60, 12, 'R ' . number_format((float)$amount, 2), 1, 1, 'C', true);

// Payment instructions
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Payment Instructions', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(200, 200, 200);
$pdf->MultiCell(0, 8, "Please make payment to the following account:\n\nBank: Smart Water Banking\nAccount Name: Smart Water Guardian\nAccount Number: 1234567890\nBranch Code: 123456\nReference: " . $invoiceNo . "\n\nPayment is due by " . $dueDate . ". Late payments may incur additional charges.");

// Output PDF
$pdf->Output('Invoice_' . $invoiceNo . '.pdf', 'D');
?>