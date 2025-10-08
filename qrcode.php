<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Create QR code instance
$qrCode = QrCode::create('https://example.com')
    ->setSize(300)
    ->setMargin(10)
    ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
    ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255))
    ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
    ->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh());

// Writer instance
$writer = new PngWriter();
$result = $writer->write($qrCode);

// Output directly to browser
header('Content-Type: '.$result->getMimeType());
echo $result->getString();
