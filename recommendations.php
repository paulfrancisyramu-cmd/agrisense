<?php
// recommendations.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

include 'includes/db_connect.php';
include 'includes/crops.php';
include 'includes/dss_logic.php';

$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch();
$latest = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch();
$weather = fetch_micro_season_forecast();

// --- HARDWARE FRESHNESS CHECK (COMMENTED OUT FOR TESTING) ---

$current_time = time();
// Match dashboard/devices heartbeat logic using created_at
$last_seen = isset($latest['created_at']) ? strtotime($latest['created_at']) : 0;
// Wider window so recommendations stay live while ESP32 sends periodically
$timeout = 60;

$is_live = ($last_seen > 0 && ($current_time - $last_seen) <= $timeout);
$no_data = !$is_live;


// --- FAKE DATA FOR TESTING ---
/*
$is_live = true;
$no_data = false;
$latest['temp'] = 31.5;  // Try changing this (e.g., 18.0 for cool)
$latest['hum'] = 82.0;   // Try changing this (e.g., 60.0 for cool)
// -----------------------------
*/
$top_crop = null;
$other_crops = [];

if ($is_live) {
    $current_temp = (float)$latest['temp'];
    $current_hum = (float)$latest['hum'];
    $active_season = get_current_season($current_temp, $current_hum, $weather['two_week_total'], $settings['rain_threshold']);
    
    $ranked = [];
    foreach ($CROP_DATABASE as $crop) {
        if (in_array($active_season, $crop['seasons'])) {
            $score = 100 - (abs($current_temp - (array_sum($crop['ideal_temp'])/2)) * 5);
            $ranked[] = [
                "name" => $crop['name'],
                "image_url" => $crop['image_url'],
                "match" => max(0, (int)$score),
                "subtext" => "Detected: " . $active_season,
                "req_temp" => $crop['ideal_temp'][0] . "-" . $crop['ideal_temp'][1] . "°C",
                "req_hum" => $crop['ideal_hum'][0] . "-" . $crop['ideal_hum'][1] . "%",
                "seasons_text" => implode(", ", $crop['seasons'])
            ];
        }
    }
    
    if (!empty($ranked)) {
        usort($ranked, function($a, $b) {
            if ($a['match'] == $b['match']) return strcmp($a['name'], $b['name']);
            return $b['match'] <=> $a['match'];
        });
        $top_crop = $ranked[0];
        $other_crops = array_slice($ranked, 1);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Recommendations</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Crop Recommendations</h1>
            <div class="status" id="system-status"><?php echo $is_live ? "Live Analysis Active" : "Hardware Offline"; ?></div>
        </div>

        <?php if ($no_data): ?>
            <div class="card" style="text-align: center; padding: 50px 20px; border-top: 5px solid #d90429;">
                <img src="https://unpkg.com/lucide-static@latest/icons/satellite-dish.svg" width="48" style="filter: opacity(0.5); margin-bottom: 20px;">
                <h2 style="color: #1b4332; margin-bottom: 10px;">Awaiting Field Data</h2>
                <p style="color: #748c94; max-width: 500px; margin: 0 auto;">
                    AgriSense requires live temperature and humidity readings from your ESP32 node to generate accurate, seasonal crop recommendations.
                </p>
            </div>
        <?php else: ?>
            <?php if ($top_crop): ?>
            <div class="recommendation-hero">
                <div class="hero-label">
                    <img src="https://img.icons8.com/color/48/star.png" width="18" style="margin-right: 6px; vertical-align: text-bottom;"> 
                    TOP MATCH FOR CURRENT CONDITIONS
                </div>
                <div class="hero-content">
                    <img src="<?php echo $top_crop['image_url']; ?>" class="hero-photo" alt="<?php echo $top_crop['name']; ?>">
                    <div>
                        <div class="hero-name"><?php echo $top_crop['name']; ?></div>
                        <div class="match-percent"><?php echo $top_crop['match']; ?>% Environmental Match</div>
                    </div>
                </div>
                <div class="trend-label" style="background: rgba(0,0,0,0.15); padding: 12px 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #b7e4c7;">
                    <img src="https://unpkg.com/lucide-static@latest/icons/bot.svg" width="16" class="icon-white" style="margin-right: 5px; opacity: 0.9;">
                    <strong>Explainability Engine:</strong> <?php echo $top_crop['subtext']; ?>. 
                    This crop thrives in <strong><?php echo $top_crop['req_temp']; ?></strong> and <strong><?php echo $top_crop['req_hum']; ?></strong>.
                </div>
            </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; margin-bottom: 15px;">
                <h2 style="color: #1b4332; font-size: 20px;">Alternative Options</h2>
                <span style="font-size: 13px; color: #748c94;">Ranked by viability</span>
            </div>

            <div class="recommendation-grid">
                <?php foreach ($other_crops as $crop): ?>
                <div class="crop-card">
                    <div class="crop-header">
                        <img src="<?php echo $crop['image_url']; ?>" class="crop-photo" alt="<?php echo $crop['name']; ?>">
                        <div class="crop-name"><?php echo $crop['name']; ?></div>
                    </div>
                    
                    <div class="crop-match">
                        <span class="percentage" style="color: #2d6a4f;">
                            <?php echo $crop['match']; ?><span class="unit" style="color: inherit; opacity: 0.8;">%</span>
                        </span>
                    </div>

                    <div class="crop-trend">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <img src="https://unpkg.com/lucide-static@latest/icons/thermometer.svg" width="14" class="icon-green"> 
                            <span style="font-size: 11px; line-height: 1.4; color: #748c94;">
                            <strong style="color: #2c3e50;">Ideal Temp:</strong> <?php echo $crop['req_temp']; ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <img src="https://unpkg.com/lucide-static@latest/icons/droplets.svg" width="14" class="icon-green">
                            <span style="font-size: 11px; line-height: 1.4; color: #748c94;">
                            <strong style="color: #2c3e50;">Ideal Hum:</strong> <?php echo $crop['req_hum']; ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <img src="https://unpkg.com/lucide-static@latest/icons/calendar.svg" width="14" class="icon-green" style="margin-top: 1px;"> 
                            <span style="font-size: 11px; line-height: 1.4; color: #748c94;">
                                <strong style="color: #2c3e50;">Seasons:</strong> <?php echo $crop['seasons_text']; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>