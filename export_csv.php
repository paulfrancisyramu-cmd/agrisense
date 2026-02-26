<?php
include 'includes/db_connect.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="agrisense_logs.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Timestamp', 'Temp', 'Hum', 'Status']);
$stmt = $conn->query("SELECT created_at AS timestamp, temp, hum, 'Recorded' AS status FROM sensor_data ORDER BY created_at DESC");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    fputcsv($output, $row);
}
fclose($output);
?>