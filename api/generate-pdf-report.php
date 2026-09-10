<?php
/**
 * Smart Water Guardian - PDF Report Generator
 * Generates water usage reports in PDF format
 */

session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    die('Unauthorized');
}

$firebase_uid = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m');

$user_stmt = $conn->prepare("
    SELECT first_name, last_name, email, meter_number 
    FROM users WHERE firebase_uid = ?
");
$user_stmt->bind_param("s", $firebase_uid);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$usage_stmt = $conn->prepare("
    SELECT 
        DATE(reading_time) as date,
        SUM(volume) as daily_volume,
        AVG(flow_rate) as avg_flow
    FROM water_readings 
    WHERE meter_id = ? 
    AND DATE_FORMAT(reading_time, '%Y-%m') = ?
    GROUP BY DATE(reading_time)
    ORDER BY date ASC
");
$meter_id = $user['meter_number'] ?? '';
$usage_stmt->bind_param("ss", $meter_id, $month);
$usage_stmt->execute();
$usage_data = $usage_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_usage = 0;
$total_bill = 0;

foreach ($usage_data as $row) {
    $total_usage += $row['daily_volume'];
}

if ($total_usage > 0) {
    $total_kl = $total_usage / 1000;
    if ($total_kl <= 6) {
        $total_bill = $total_kl * 18.50;
    } elseif ($total_kl <= 20) {
        $total_bill = (6 * 18.50) + (($total_kl - 6) * 25.00);
    } elseif ($total_kl <= 40) {
        $total_bill = (6 * 18.50) + (14 * 25.00) + (($total_kl - 20) * 35.00);
    } else {
        $total_bill = (6 * 18.50) + (14 * 25.00) + (20 * 35.00) + (($total_kl - 40) * 45.00);
    }
    $total_bill = $total_bill * 1.15;
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Water Usage Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; padding: 40px; color: #333; }
        .header { 
            background: linear-gradient(135deg, #00d4ff, #7b2ffc); 
            padding: 30px; 
            border-radius: 10px; 
            color: white; 
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin: 0; }
        .header p { margin: 5px 0 0; opacity: 0.9; }
        .section { margin-bottom: 30px; }
        .section h2 { 
            border-bottom: 2px solid #00d4ff; 
            padding-bottom: 10px; 
            color: #1976d2; 
            margin-bottom: 15px;
        }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { background: #f5f5f5; padding: 15px; border-radius: 8px; }
        .info-item .label { font-size: 12px; color: #999; text-transform: uppercase; }
        .info-item .value { font-size: 18px; font-weight: 600; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #00d4ff; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f0f4f8; }
        .summary { 
            background: #e3f2fd; 
            padding: 20px; 
            border-radius: 8px; 
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .summary-item { text-align: center; }
        .summary-item .label { font-size: 12px; color: #555; }
        .summary-item .value { font-size: 24px; font-weight: 700; color: #1976d2; }
        .footer { 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        @page { margin: 40px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Smart Water Guardian</h1>
        <p>Monthly Water Usage Report - " . date('F Y', strtotime($month . '-01')) . "</p>
    </div>
    
    <div class='section'>
        <h2>Report Information</h2>
        <div class='info-grid'>
            <div class='info-item'>
                <div class='label'>Report Generated</div>
                <div class='value'>" . date('Y-m-d H:i:s') . "</div>
            </div>
            <div class='info-item'>
                <div class='label'>Report Period</div>
                <div class='value'>" . date('F Y', strtotime($month . '-01')) . "</div>
            </div>
        </div>
    </div>
    
    <div class='section'>
        <h2>User Information</h2>
        <div class='info-grid'>
            <div class='info-item'>
                <div class='label'>Full Name</div>
                <div class='value'>" . ($user['first_name'] ?? '') . " " . ($user['last_name'] ?? '') . "</div>
            </div>
            <div class='info-item'>
                <div class='label'>Email Address</div>
                <div class='value'>" . ($user['email'] ?? '') . "</div>
            </div>
            <div class='info-item'>
                <div class='label'>Meter Number</div>
                <div class='value'>" . ($user['meter_number'] ?? 'N/A') . "</div>
            </div>
            <div class='info-item'>
                <div class='label'>Report ID</div>
                <div class='value'>RPT-" . date('Ymd') . "-" . rand(1000, 9999) . "</div>
            </div>
        </div>
    </div>
    
    <div class='section'>
        <h2>Daily Usage Data</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Volume (L)</th>
                    <th>Avg Flow (L/min)</th>
                </tr>
            </thead>
            <tbody>
";

foreach ($usage_data as $row) {
    $html .= "
                <tr>
                    <td>" . date('Y-m-d', strtotime($row['date'])) . "</td>
                    <td>" . number_format($row['daily_volume'] ?? 0, 1) . " L</td>
                    <td>" . number_format($row['avg_flow'] ?? 0, 1) . " L/min</td>
                </tr>
    ";
}

$html .= "
            </tbody>
        </table>
    </div>
    
    <div class='section'>
        <h2>Billing Summary</h2>
        <div class='summary'>
            <div class='summary-item'>
                <div class='label'>Total Usage</div>
                <div class='value'>" . number_format($total_usage, 1) . " L</div>
            </div>
            <div class='summary-item'>
                <div class='label'>Bill Amount</div>
                <div class='value'>R " . number_format($total_bill, 2) . "</div>
            </div>
            <div class='summary-item'>
                <div class='label'>Average Daily Use</div>
                <div class='value'>" . (count($usage_data) > 0 ? number_format($total_usage / count($usage_data), 1) : 0) . " L</div>
            </div>
        </div>
    </div>
    
    <div class='footer'>
        <p>This is an automated report generated by Smart Water Guardian.</p>
        <p>For any questions, please contact support@smartwater.co.za</p>
        <p>&copy; 2026 Smart Water Guardian. All rights reserved.</p>
    </div>
</body>
</html>
";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Water_Report_' . date('Y-m-d') . '.pdf';
$filepath = '../reports/' . $filename;

if (!file_exists('../reports')) {
    mkdir('../reports', 0777, true);
}

file_put_contents($filepath, $dompdf->output());

$stmt = $conn->prepare("
    INSERT INTO pdf_reports (firebase_uid, month, year, file_path, file_size)
    VALUES (?, ?, ?, ?, ?)
");
$year = date('Y', strtotime($month . '-01'));
$file_size = filesize($filepath);
$stmt->bind_param("ssssi", $firebase_uid, $month, $year, $filepath, $file_size);
$stmt->execute();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit();
?>