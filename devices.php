<?php
// devices.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

include 'includes/db_connect.php';
include 'includes/crops.php';
include 'includes/dss_logic.php';

$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();
$latest = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch_assoc();
$weather = fetch_micro_season_forecast();

$current_time = time();
// Use the same created_at heartbeat column used across the app
$last_seen = isset($latest['created_at']) ? strtotime($latest['created_at']) : 0;
// Give the ESP32 a wider window so brief gaps don't mark it offline
$timeout = 60;

// Diagnostic Logic
$esp32_online = ($last_seen > 0 && ($current_time - $last_seen) <= $timeout);
$dht11_online = ($esp32_online && isset($latest['temp']) && $latest['temp'] !== null);
$api_online = ($weather['api_status'] === 'Online');

$all_systems_nominal = ($esp32_online && $dht11_online && $api_online);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Device Status</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Hardware & API Monitoring</h1>
            
            <?php if ($all_systems_nominal): ?>
                <div class="status" style="background: #d8f3dc; color: #1b4332;">All Systems Nominal</div>
            <?php else: ?>
                <div style="background: #ffe3e3; color: #d90429; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                    <span style="display: block; width: 10px; height: 10px; background: #d90429; border-radius: 50%;"></span>
                    Hardware Disconnected
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 20px;">
            
            <div class="card">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/cpu.svg" width="20" class="icon-green"> Main Controller</h3>
                <div class="value" style="font-size: 24px; margin: 15px 0;">ESP32 Node</div>
                <?php if ($esp32_online): ?>
                    <div class="subtext" style="color: #40916c; font-weight: bold; background: #f8fcf9;">🟢 Online (Active)</div>
                <?php else: ?>
                    <div class="subtext" style="color: #d90429; font-weight: bold; background: #fff0f0;">🔴 Offline (Disconnected)</div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/thermometer.svg" width="20" class="icon-green"> DHT11 Sensor</h3>
                <div class="value" style="font-size: 24px; margin: 15px 0;">Temp/Humidity</div>
                <?php if ($dht11_online): ?>
                    <div class="subtext" style="color: #40916c; font-weight: bold; background: #f8fcf9;">🟢 Online (Calibrated)</div>
                <?php else: ?>
                    <div class="subtext" style="color: #d90429; font-weight: bold; background: #fff0f0;">🔴 Offline (No Data)</div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/cloud-sun.svg" width="20" class="icon-green"> Precipitation Data</h3>
                <div class="value" style="font-size: 24px; margin: 15px 0;">Open-Meteo API</div>
                <?php if ($api_online): ?>
                    <div class="subtext" style="color: #40916c; font-weight: bold; background: #f8fcf9;">🟢 Connected (14-Day Sync)</div>
                <?php else: ?>
                    <div class="subtext" style="color: #d90429; font-weight: bold; background: #fff0f0;">🔴 Offline (Connection Failed)</div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>