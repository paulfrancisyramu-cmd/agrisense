<?php
// alerts.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
include 'includes/db_connect.php';

$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch();
$latest = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch();

$alerts = [];
$current_time = time();
// Use the same created_at heartbeat column and wider timeout
$last_seen = isset($latest['created_at']) ? strtotime($latest['created_at']) : 0;
$timeout = 60; 

$esp32_online = ($last_seen > 0 && ($current_time - $last_seen) <= $timeout);

if (!$esp32_online) {
    $alerts[] = ["title" => "Hardware Link Lost", "message" => "Critical: ESP32 is no longer transmitting. Data stale.", "type" => "alert-unread", "color" => "#d90429", "icon" => "plug-zap", "time" => "Now"];
} elseif (!isset($latest['temp']) || $latest['temp'] === null) {
    $alerts[] = ["title" => "DHT11 Sensor Error", "message" => "ESP32 online, but sensor data is missing.", "type" => "alert-unread", "color" => "#cc5500", "icon" => "triangle-alert", "time" => "Now"];
} else {
    $temp = (float)$latest['temp'];
    $hum = (float)$latest['hum'];
    if ($temp >= $settings['heat_threshold']) {
        $alerts[] = ["title" => "Extreme Heat Stress", "message" => "Temp at {$temp}°C. Risk of crop wilting.", "type" => "alert-unread", "color" => "#cc5500", "icon" => "thermometer-sun", "time" => "Now"];
    }
    if ($hum <= $settings['hum_threshold']) {
        $alerts[] = ["title" => "High Transpiration Risk", "message" => "Humidity dropped to {$hum}%.", "type" => "alert-unread", "color" => "#0077b6", "icon" => "droplets", "time" => "Now"];
    }
}

if (empty($alerts)) {
    $alerts[] = ["title" => "All Nominal", "message" => "Conditions are optimal.", "type" => "alert-read", "color" => "#40916c", "icon" => "check-circle", "time" => "Now"];
}

$unread_count = 0;
foreach ($alerts as $a) { if ($a['type'] == 'alert-unread') $unread_count++; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Alerts</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <style>
        /* ADDED: High contrast styling for Alert Cards */
        body { background-color: #f0f4f2; }
        .alert-card { 
            background: white; 
            padding: 25px; 
            border-radius: 16px; 
            margin-bottom: 20px; 
            border: 1px solid #d1dbd4; 
            border-left: 6px solid #40916c; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.06); 
            transition: 0.3s;
        }
        .alert-card:hover { transform: translateX(5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .alert-unread { border-left-color: #d90429; background: #fffcfc; }
        .icon-alert { filter: brightness(0) saturate(100%) invert(43%) sepia(34%) saturate(735%) hue-rotate(105deg) brightness(92%) contrast(85%); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>System Alerts</h1>
            <div id="unread-counter" class="status" style="background: <?php echo ($unread_count > 0) ? '#ffecd1' : '#d8f3dc'; ?>; color: <?php echo ($unread_count > 0) ? '#cc5500' : '#1b4332'; ?>;">
                <?php echo ($unread_count > 0) ? "$unread_count Unread" : "All Caught Up"; ?>
            </div>
        </div>

        <?php foreach ($alerts as $alert): ?>
        <div class="alert-card <?php echo $alert['type']; ?>" onclick="markAsRead(this)">
            <h3 style="color: <?php echo $alert['color']; ?>; margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                <img src="https://unpkg.com/lucide-static@latest/icons/<?php echo $alert['icon']; ?>.svg" width="22" class="icon-alert" style="filter: none;">
                <?php echo $alert['title']; ?>
            </h3>
            <p style="font-size: 14px; color: #555;"><?php echo $alert['message']; ?></p>
            <span style="font-size: 12px; color: #999; display: block; margin-top: 10px;"><?php echo $alert['time']; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>