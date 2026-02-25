<?php
include 'includes/db_connect.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="agrisense_logs.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Timestamp', 'Temp', 'Hum', 'Status']);
$stmt = $conn->query("SELECT timestamp, temp, hum, status FROM sensor_data ORDER BY timestamp DESC");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    fputcsv($output, $row);
}
fclose($output);
?>