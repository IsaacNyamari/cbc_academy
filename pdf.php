<?php
require 'vendor/autoload.php';
session_start();

use Fpdf\Fpdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// DB Connection
require 'includes/db.php'; // your PDO connection

// ===================
// Subject Completion Data
// ===================
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_subjects, 
           SUM(CASE WHEN total_topics = completed_topics THEN 1 ELSE 0 END) AS completed_subjects
    FROM (
        SELECT COUNT(t.id) AS total_topics, 
               COUNT(sp.topic_id) AS completed_topics
        FROM subjects s
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp 
               ON t.id = sp.topic_id 
              AND sp.student_id = ? 
              AND sp.completion_status = 'completed'
        GROUP BY s.id
    ) AS subject_completion
");
$stmt->execute([$_SESSION['user_id']]);
$subject_completion = $stmt->fetch(PDO::FETCH_ASSOC);

$total_subjects = (int) $subject_completion['total_subjects'];
$completed_subjects = (int) $subject_completion['completed_subjects'];

// ===================
// Fetch Subjects List
// ===================
$stmt = $pdo->prepare("
    SELECT DISTINCT s.name 
    FROM subjects s
    JOIN chapters c ON s.id = c.subject_id
    JOIN topics t ON c.id = t.chapter_id
    LEFT JOIN student_progress sp 
           ON t.id = sp.topic_id 
          AND sp.student_id = ? 
          AND sp.completion_status = 'completed'
");
$stmt->execute([$_SESSION['user_id']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===================
// Generate QR Code
// ===================
$qrCode = QrCode::create('https://learn.yeahkenyan.com/verify?user=' . urlencode($_SESSION["full_name"]))
    ->setSize(250)
    ->setMargin(10)
    ->setForegroundColor(new Color(0, 0, 0))
    ->setBackgroundColor(new Color(255, 255, 255))
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

$writer = new PngWriter();
$result = $writer->write($qrCode);

// Save QR code to temp file
$tempQr = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
file_put_contents($tempQr, $result->getString());

// ===================
// Create PDF Certificate
// ===================
$logo = "assets/images/logo.png";
$background = "assets/images/background.png";
$pdf = new Fpdf();
$pdf->AddPage("P", "A4");
$pdf->SetTitle($_SESSION["full_name"] . " Certificate");

// Background image (full page)
$pdf->Image($background, 0, 0, 210, 297); // 210x297 = A4 size in mm

$primaryColor = [34, 85, 153];
$accentColor = [0, 128, 0];

// Border
$pdf->SetDrawColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->SetLineWidth(2);
$pdf->Rect(10, 10, 190, 277);

// Logo
$pdf->Image($logo, 85, 18, 40, 0);

// Title
$pdf->SetY(65);
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->SetFont("Arial", "B", 28);
$pdf->Cell(0, 15, "Certificate of Completion", 0, 1, "C");

// Subtitle
$pdf->SetFont("Arial", "", 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, "This is to certify that", 0, 1, "C");

// Recipient Name
$pdf->SetFont("Times", "B", 22);
$pdf->SetTextColor($accentColor[0], $accentColor[1], $accentColor[2]);
$pdf->Cell(0, 15, $_SESSION["full_name"], 0, 1, "C");

// Course Info
$pdf->SetFont("Arial", "", 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 10, "Has successfully completed the defined course. This certificate is awarded in recognition of the achievement and dedication demonstrated throughout the learning process.", 0, "C");

// Warm message before subjects
$pdf->Ln(8);
$pdf->SetFont("Arial", "I", 12);
$pdf->MultiCell(0, 8, "During this journey, " . $_SESSION["full_name"] . " explored and mastered the following subjects, showcasing dedication and hard work:", 0, "C");
$pdf->Ln(5);

// Table header
$pdf->SetFont("Arial", "B", 12);
$pdf->Cell(0, 8, "Subjects Covered", 0, 1, "C");

// Table content
$pdf->SetFont("Arial", "", 12);
foreach ($subjects as $row) {
    $pdf->Cell(0, 8, $row['name'], 0, 1, "C");
}

// Date
$pdf->Ln(10);
$pdf->SetFont("Arial", "I", 12);
$pdf->Cell(0, 10, "Date Issued: " . date("F j, Y"), 0, 1, "C");

// Signature
$pdf->Ln(20);
$pdf->Cell(60, 10, "", 0, 0, "C");

// Signature image
$signatureImg = "assets/images/signature.png"; // Path to signature image
$pdf->Image($signatureImg, 85, $pdf->GetY(), 40, 15); // Adjust position/size as needed

$pdf->Ln(16); // Move below image
$pdf->Cell(60, 10, "", 0, 0, "C");
$pdf->Cell(70, 6, "Signature", 0, 1, "C");

// QR Code
$pdf->Image($tempQr, 160, 250, 30, 30);

// ===================
// Save PDF to server
// ===================
$folder = __DIR__ . "/certificates";

$fileName = "certificate_" . $_SESSION["user_id"] . "_" . time() . ".pdf";
$filePath = $folder . "/" . $fileName;
$pdf->Output($filePath, "F"); // Save to file

// ===================
// Store in database
// ===================
$stmt = $pdo->prepare("INSERT INTO certificates (student_id, file_path, issue_date) VALUES (?, ?, NOW())");
$stmt->execute([$_SESSION["user_id"], $fileName]);

// Output to browser as well
$pdf->Output("I", $_SESSION["full_name"] . ".pdf");

// Clean up QR
unlink($tempQr);
