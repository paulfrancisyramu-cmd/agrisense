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

// Heartbeat-based hardware check: if no fresh reading within timeout,
// treat hardware as offline so the cards show "No Hardware Detected".
$current_time = time();
$last_seen = isset($latest['created_at']) ? strtotime($latest['created_at']) : 0;
// Give a generous 5‑minute window in case readings are infrequent
$timeout = 300;
$is_live = ($last_seen > 0 && ($current_time - $last_seen) <= $timeout);

$sensor_data = [
    'temperature' => ($is_live) ? ($latest['temp'] ?? "--") : "--",
    'humidity' => ($is_live) ? ($latest['hum'] ?? "--") : "--",
    'rain_array' => $weather['rain_array'],
    'forecast_trend' => $weather['forecast_trend'],
    'active_season' => 'Stable',
    'is_live' => $is_live
];
// -----------------------------

// --- FAKE DATA FOR TESTING ---
/*
$sensor_data = [
    'temperature' => 31.5,  // Try changing this (e.g., 18.0 for cool)
    'humidity' => 82.0,     // Try changing this (e.g., 60.0 for cool)
    'rain_array' => $weather['rain_array'],
    'forecast_trend' => $weather['forecast_trend'],
    'active_season' => 'Stable',
    'is_live' => true       // Forces the dashboard to show "Live reading active"
];
// -----------------------------
*/
$top_crop = null;
// Only run the recommendation algorithm if we actually have live hardware data
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
            // Add the alphabetical tie-breaker here too!
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
    <title>AgriSense - Dashboard</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Field Conditions</h1>
            <div class="status">System Online</div>
        </div>

        <div class="card-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
            
            <div class="card" style="grid-column: 1 / -1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
                        <img src="https://unpkg.com/lucide-static@latest/icons/trending-up.svg" width="20" class="icon-green"> 
                        14-Day Precipitation Forecast
                    </h3>
                    
                    <div id="rain-subtext" style="font-size: 12px; font-weight: bold; background: #f1f7f5; color: #40916c; padding: 6px 12px; border-radius: 20px;">
                        <?php echo $sensor_data['forecast_trend'] ? $sensor_data['forecast_trend'] : "Stable Conditions"; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; font-size: 11px; margin-bottom: 20px; color: #8d99ae; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; background: #0077b6; border-radius: 3px;"></span> Wet/Rainy</div>
                    <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; background: #e67e22; border-radius: 3px;"></span> Hot Dry</div>
                    <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; background: #2d6a4f; border-radius: 3px;"></span> Cool Dry</div>
                    <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; background: #8d99ae; border-radius: 3px;"></span> Stable</div>
                </div>
                
                <div style="width: 100%; height: 250px;">
                   <canvas id="weatherTrendChart" 
                        data-rain="<?php echo htmlspecialchars(json_encode($sensor_data['rain_array'])); ?>"
                        data-season="<?php echo $sensor_data['active_season']; ?>">
                    </canvas>
                </div>
            </div>

            <div class="card" id="card-temp">
                <h3>TEMPERATURE</h3>
                <div class="value"><?php echo $sensor_data['temperature']; ?> <span style="font-family: 'Poppins', sans-serif;">°C</span></div>
                <?php if ($sensor_data['is_live']): ?>
                    <div class="subtext" style="color: #40916c; font-weight: 600;">Live reading active</div>
                <?php else: ?>
                    <div class="subtext" style="color: #d90429; font-weight: 600;">⚠️ No Hardware Detected</div>
                <?php endif; ?>
            </div>

            <div class="card" id="card-hum">
                <h3>HUMIDITY</h3>
                <div class="value"><?php echo $sensor_data['humidity']; ?> <span style="font-family: 'Poppins', sans-serif;">%</span></div>
                <?php if ($sensor_data['is_live']): ?>
                    <div class="subtext" style="color: #40916c; font-weight: 600;">Live reading active</div>
                <?php else: ?>
                    <div class="subtext" style="color: #d90429; font-weight: 600;">⚠️ No Hardware Detected</div>
                <?php endif; ?>
            </div>

            <div class="card recommendation-card" id="card-ideal-crop">
                <h3 style="color: #d8f3dc;">IDEAL CROP</h3>
                <div class="value" style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                    <?php if ($top_crop): ?>
                        <img src="<?php echo $top_crop['image_url']; ?>" style="width: 50px; height: 50px; background: white; border-radius: 50%; padding: 5px;"> 
                        <span style="font-family: 'Poppins', sans-serif; font-size: 28px; font-weight: 600; color: white !important; letter-spacing: 0.5px;"><?php echo $top_crop['name']; ?></span>
                    <?php else: ?>
                        <span style="font-family: 'Poppins', sans-serif; font-size: 24px; font-weight: 600; color: white !important;">Analyzing...</span>
                    <?php endif; ?>
                </div>
                <?php if ($top_crop): ?>
                <div style="font-family: 'Poppins', sans-serif; font-size: 13px; color: #d8f3dc; margin-top: 10px; font-weight: 500;">
                    Ideal: <?php echo $top_crop['req_temp']; ?> | <?php echo $top_crop['req_hum']; ?>
                </div>
                <?php endif; ?>
                <div class="subtext" style="font-family: 'Poppins', sans-serif; margin-top: 8px; color: #d8f3dc; background: transparent; padding: 0;">
                    <?php if ($top_crop): ?>
                        <?php echo $top_crop['match']; ?>% Match for current season
                    <?php else: ?>
                        Gathering metrics...
                    <?php endif; ?>
                </div>
            </div>

        </div> 
    </div> 
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>