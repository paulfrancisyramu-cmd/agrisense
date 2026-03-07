<?php
// dashboard.php
session_start();
// FIX 1: Set the timezone to match your save_data.php
date_default_timezone_set('Asia/Manila'); 

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

include 'includes/db_connect.php';
include 'includes/crops.php';
include 'includes/dss_logic.php';

$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch();
$latest = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch();
$weather = fetch_micro_season_forecast();

// Heartbeat tracking
$hb = $conn->query("SELECT last_seen FROM device_heartbeat WHERE id=1")->fetch();
$current_time = time();
$last_seen = isset($hb['last_seen']) ? strtotime($hb['last_seen']) : 0;
$timeout = isset($settings['heartbeat_timeout']) ? (int)$settings['heartbeat_timeout'] : 60;
$is_live = ($last_seen > 0 && ($current_time - $last_seen) <= $timeout);

$sensor_data = [
    'temperature' => ($is_live) ? ($latest['temp'] ?? "--") : "--",
    'humidity' => ($is_live) ? ($latest['hum'] ?? "--") : "--",
    'rain_array' => $weather['rain_array'],
    'forecast_trend' => $weather['forecast_trend'],
    'active_season' => 'Stable',
    'is_live' => $is_live
];

$top_crop = null;
if ($sensor_data['temperature'] !== "--") {
    $current_temp = (float)$sensor_data['temperature'];
    $current_hum = (float)$sensor_data['humidity'];
    $sensor_data['active_season'] = get_current_season($current_temp, $current_hum, $weather['two_week_total'], $settings['rain_threshold']);
    
    $ranked = [];
    foreach ($CROP_DATABASE as $crop) {
        if (in_array($sensor_data['active_season'], $crop['seasons'])) {
            $score = 100 - (abs($current_temp - (array_sum($crop['ideal_temp'])/2)) * 5);
            $ranked[] = [
                "name" => $crop['name'],
                "image_url" => $crop['image_url'],
                "match" => max(0, (int)$score),
                "req_temp" => $crop['ideal_temp'][0] . "-" . $crop['ideal_temp'][1] . "°C",
                "req_hum" => $crop['ideal_hum'][0] . "-" . $crop['ideal_hum'][1] . "%"
            ];
        }
    }
    if (!empty($ranked)) {
        usort($ranked, function($a, $b) { 
            if ($a['match'] == $b['match']) return strcmp($a['name'], $b['name']);
            return $b['match'] <=> $a['match']; 
        });
        $top_crop = $ranked[0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AgriSense - Dashboard</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleMenu()">☰ Menu</button>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Field Conditions</h1>
            <div class="status" style="<?php echo $is_live ? '' : 'background: #ffdce0; color: #d90429;'; ?>">
                <?php echo $is_live ? 'System Online' : 'Hardware Offline'; ?>
            </div>
        </div>

        <div class="card-grid dashboard-grid">
            
            <div class="card chart-full-width">
                <div class="chart-header">
                    <h3 class="flex-center gap-8">
                        <img src="https://unpkg.com/lucide-static@latest/icons/trending-up.svg" width="20" class="icon-green"> 
                        14-Day Precipitation Forecast
                    </h3>
                    
                    <div id="rain-subtext" class="trend-badge">
                        <?php echo $sensor_data['forecast_trend'] ? $sensor_data['forecast_trend'] : "Stable Conditions"; ?>
                    </div>
                </div>

                <div class="legend-container">
                    <div class="legend-item"><span class="dot rain"></span> Wet/Rainy</div>
                    <div class="legend-item"><span class="dot hot"></span> Hot Dry</div>
                    <div class="legend-item"><span class="dot cool"></span> Cool Dry</div>
                    <div class="legend-item"><span class="dot stable"></span> Stable</div>
                </div>
                
                <div class="chart-wrapper">
                   <canvas id="weatherTrendChart" 
                        data-rain="<?php echo htmlspecialchars(json_encode($sensor_data['rain_array'])); ?>"
                        data-season="<?php echo $sensor_data['active_season']; ?>">
                    </canvas>
                </div>
            </div>

            <div class="card" id="card-temp">
                <h3>TEMPERATURE</h3>
                <div class="value"><?php echo $sensor_data['temperature']; ?> <span>°C</span></div>
                <?php if ($sensor_data['is_live']): ?>
                    <div class="subtext live-status">Live reading active</div>
                <?php else: ?>
                    <div class="subtext error-status">⚠️ No Hardware Detected</div>
                <?php endif; ?>
            </div>

            <div class="card" id="card-hum">
                <h3>HUMIDITY</h3>
                <div class="value"><?php echo $sensor_data['humidity']; ?> <span>%</span></div>
                <?php if ($sensor_data['is_live']): ?>
                    <div class="subtext live-status">Live reading active</div>
                <?php else: ?>
                    <div class="subtext error-status">⚠️ No Hardware Detected</div>
                <?php endif; ?>
            </div>

            <div class="card recommendation-card" id="card-ideal-crop">
                <h3 class="light-green-text">IDEAL CROP</h3>
                <div class="value flex-center gap-15">
                    <?php if ($top_crop): ?>
                        <img src="<?php echo $top_crop['image_url']; ?>" class="crop-icon-circle"> 
                        <span class="crop-title"><?php echo $top_crop['name']; ?></span>
                    <?php else: ?>
                        <span class="crop-title">Analyzing...</span>
                    <?php endif; ?>
                </div>
                <?php if ($top_crop): ?>
                <div class="req-text">
                    Ideal: <?php echo $top_crop['req_temp']; ?> | <?php echo $top_crop['req_hum']; ?>
                </div>
                <?php endif; ?>
                <div class="subtext match-text">
                    <?php if ($top_crop): ?>
                        <?php echo $top_crop['match']; ?>% Match for current season
                    <?php else: ?>
                        Gathering metrics...
                    <?php endif; ?>
                </div>
            </div>

        </div> 
    </div> 

    <script>
        function toggleMenu() {
            const sidebar = document.querySelector('.sidebar');
            const btn = document.querySelector('.mobile-toggle');
            sidebar.classList.toggle('active');
            btn.innerText = sidebar.classList.contains('active') ? "✕ Close" : "☰ Menu";
        }
    </script>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>