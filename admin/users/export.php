<?php
require_once '../../includes/config.php';
require_once '../../vendor/autoload.php';

use Fpdf\Fpdf;

function exportData($roleFilter, $format, $title, $filename)
{
    global $pdo;

    // Build query dynamically
    $sql = "SELECT id, full_name, email";
    if ($roleFilter === 'users') $sql .= ", role";
    $sql .= ", created_at FROM users";

    if ($roleFilter === 'users') {
        $sql .= " WHERE role != 'admin'";
    } elseif (in_array($roleFilter, ['teacher', 'student'])) {
        $sql .= " WHERE role = :role";
    }

    $sql .= " ORDER BY created_at DESC";
    $query = $pdo->prepare($sql);

    if (in_array($roleFilter, ['teacher', 'student'])) {
        $query->bindValue(':role', $roleFilter);
    }

    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$result) {
        echo "No {$title} found.";
        exit;
    }

    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($result[0]));
            foreach ($result as $row) fputcsv($output, $row);
            fclose($output);
            break;

        case 'xls':
            header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
            echo "<table border='1'><tr>";
            foreach (array_keys($result[0]) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>";
            foreach ($result as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            break;

        case 'pdf':
            $logo = '../../assets/images/logo.png';
            $pdf = new FPDF();
            $pdf->AddPage();
            $logoWidth = 33;
            $x = ($pdf->GetPageWidth() - $logoWidth) / 2;
            $pdf->Image($logo, $x, 8, $logoWidth);
            $pdf->Ln(20);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 20, $title, 0, 1, 'C');

            // Table header (skip ID)
            $pdf->SetFont('Arial', 'B', 12);
            foreach (array_keys($result[0]) as $col) {
                if ($col === 'id') continue;
                $width = ($col === 'email') ? 70 : 40;
                $pdf->Cell($width, 10, ucfirst(str_replace('_', ' ', $col)), 1);
            }
            $pdf->Ln();

            // Table rows (skip ID, format created_at)
            $pdf->SetFont('Arial', '', 12);
            foreach ($result as $row) {
                foreach ($row as $key => $cell) {
                    if ($key === 'id') continue;
                    if ($key === 'created_at') {
                        $cell = timeAgo($cell); // Convert to "time ago"
                    }
                    $width = ($key === 'email') ? 70 : 40;
                    $pdf->Cell($width, 10, $cell, 1);
                }
                $pdf->Ln();
            }
            $pdf->Output('D', "{$filename}.pdf");
            break;

        default:
            echo "Invalid format.";
            http_response_code(400);
            return;
    }
    exit;
}

// Handle request
if (isset($_GET['export'], $_GET['type'])) {
    $format = $_GET['export'];
    $type   = $_GET['type'];

    $map = [
        'users'    => ['users', 'Users', 'users_export'],
        'teachers' => ['teacher', 'Teachers', 'teachers_export'],
        'students' => ['student', 'Students', 'students_export'],
    ];

    if (isset($map[$type])) {
        list($roleFilter, $title, $filename) = $map[$type];
        exportData($roleFilter, $format, $title, $filename);
    } else {
        echo "Invalid type.";
        http_response_code(400);
    }
} else {
    echo "Missing export or type parameter.";
    http_response_code(400);
}
