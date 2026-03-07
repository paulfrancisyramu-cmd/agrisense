<?php
// data_logs.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// FORCE PHILIPPINES TIMEZONE (Matches Nagcarlan/Lipa City)
date_default_timezone_set('Asia/Manila');

include 'includes/db_connect.php';
include 'includes/crops.php';
include 'includes/dss_logic.php';

// Load rainfall threshold once so we can reuse the same season logic per row
$settings = $conn->query("SELECT rain_threshold FROM system_settings WHERE id=1")->fetch();
$rain_threshold = $settings ? (float)$settings['rain_threshold'] : 15.0;

// Get today's date in YYYY-MM-DD format based on Manila time
$today = date('Y-m-d');

// If a date is provided in the URL, use it; otherwise, default to TODAY
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// FIX: Fetch logs using 'created_at' to match your updated MySQL schema
$stmt = $conn->prepare("SELECT * FROM sensor_data WHERE DATE(created_at) = ? ORDER BY created_at DESC");
$stmt->execute([$selected_date]);
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Data Logs</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <!-- Data logs page uses global styles from style.css for consistency -->
    <style>
        /* Export button specific styles */
        .export-btn { background: #2d6a4f; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
        .export-btn:hover { background: #1b4332; transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header" style="margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center; gap:16px;">
            <div>
                <h1>System Data Logs</h1>
                <p style="color: #748c94; margin-bottom: 0; font-size: 14px;">
                    View sensor readings stored in MySQL for the selected day (PHT).
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <form method="get" style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-size:13px; color:#555; font-weight: 600;">
                        Select date:
                        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" style="padding: 6px; border-radius: 4px; border: 1px solid #ddd; font-family: 'Poppins', sans-serif;">
                    </label>
                    <button type="submit" class="export-btn">Load Day</button>
                </form>
                <a href="export_csv.php" class="export-btn">
                    <img src="https://unpkg.com/lucide-static@latest/icons/download.svg" width="18" class="icon-white"> 
                    Download CSV Report
                </a>
            </div>
        </div>

        <?php if (count($logs) === 0): ?>
            <div class="card" style="text-align: center; padding: 50px 20px; border-top: 5px solid #40916c;">
                <p style="color: #95a5a6;">No data logged yet for <?php echo htmlspecialchars($selected_date); ?>.</p>
            </div>
        <?php else: ?>
            <div class="log-table-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Created At</th>
                            <th>Temperature</th>
                            <th>Humidity</th>
                            <th>Rain (14‑Day)</th>
                            <th>Ideal Crop</th>
                            <th>Event Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="font-weight: 600; color: #40916c;">
                                <?php echo date("H:i:s", strtotime($log['created_at'])); ?>
                            </td>
                            <td><?php echo number_format($log['temp'], 1); ?> °C</td>
                            <td><?php echo number_format($log['hum'], 1); ?> %</td>
                            <td><?php echo isset($log['rain_forecast']) ? number_format($log['rain_forecast'], 1) . ' mm' : '--'; ?></td>
                            <td>
                                <?php
                                // Derive the ideal crop using the SAME logic as dashboard/recommendations
                                $temp = (float)$log['temp'];
                                $hum  = (float)$log['hum'];
                                $rain = isset($log['rain_forecast']) ? (float)$log['rain_forecast'] : 0.0;

                                $season = get_current_season($temp, $hum, $rain, $rain_threshold);

                                $ranked = [];
                                foreach ($CROP_DATABASE as $crop) {
                                    if (in_array($season, $crop['seasons'])) {
                                        $avgIdeal = (array_sum($crop['ideal_temp']) / 2);
                                        $score = 100 - (abs($temp - $avgIdeal) * 5);
                                        $ranked[] = [
                                            'name' => $crop['name'],
                                            'match' => max(0, (int)$score)
                                        ];
                                    }
                                }

                                if (!empty($ranked)) {
                                    usort($ranked, function($a, $b) {
                                        if ($a['match'] == $b['match']) return strcmp($a['name'], $b['name']);
                                        return $b['match'] <=> $a['match'];
                                    });
                                    $ideal = $ranked[0]['name'] . " (" . $season . ")";
                                    echo htmlspecialchars($ideal);
                                } else {
                                    echo '--';
                                }
                                ?>
                            </td>
                            <td><span class="badge">Recorded</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>