<?php
// settings.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
include 'includes/db_connect.php';

// Handle form submission to update settings
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $heat = (float)$_POST['heat_threshold'];
    $hum = (float)$_POST['humidity_threshold'];
    $rain = (float)$_POST['rain_threshold'];
    $timeout = (int)$_POST['heartbeat_timeout'];

    $update = $conn->prepare("UPDATE system_settings SET heat_threshold = ?, hum_threshold = ?, rain_threshold = ?, heartbeat_timeout = ? WHERE id = 1");
    $update->execute([$heat, $hum, $rain, $timeout]);
}

// Fetch current settings to populate the form
$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Settings</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>System Configuration</h1>
        </div>

        <form action="settings.php" method="POST">
            
            <div class="settings-group">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/map-pin.svg" width="20" class="icon-green"> Geographic Parameters</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Target Location</label>
                        <input type="text" value="Nagcarlan, Laguna" disabled>
                    </div>
                    <div class="form-group">
                        <label>Latitude</label>
                        <input type="text" value="14.13" disabled>
                    </div>
                    <div class="form-group">
                        <label>Longitude</label>
                        <input type="text" value="121.41" disabled>
                    </div>
                <p style="font-size: 13px; color: #cc5500; margin-top: 10px; font-weight: 500; background: #fffaf0; padding: 10px; border-radius: 6px; border-left: 3px solid #cc5500;">
                    These parameters are strictly visual to define the specific boundaries of this study. The weather API is hardcoded strictly to Nagcarlan.
                </p>
            </div>

            <div class="settings-group" style="border-left-color: #e67e22;">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/triangle-alert.svg" width="20" class="icon-green"> Alert Thresholds (DSS Rules)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Extreme Heat Trigger (°C)</label>
                        <input type="number" step="0.1" name="heat_threshold" value="<?php echo htmlspecialchars($settings['heat_threshold']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>High Transpiration Trigger (%)</label>
                        <input type="number" step="0.1" name="humidity_threshold" value="<?php echo htmlspecialchars($settings['hum_threshold']); ?>" required>
                    </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rainy Season Threshold (14-Day mm)</label>
                        <input type="number" step="0.1" name="rain_threshold" value="<?php echo htmlspecialchars($settings['rain_threshold']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Heartbeat Timeout (Seconds)</label>
                        <input type="number" name="heartbeat_timeout" value="<?php echo htmlspecialchars($settings['heartbeat_timeout']); ?>" required>
                    </div>
                <p style="font-size: 12px; color: #95a5a6; margin-top: 5px;">* Adjusting these triggers will instantly alter how the system generates crop recommendations and system alerts.</p>
            </div>
            
            <button type="submit" class="btn-save" onclick="alert('Configuration saved securely. DSS rules updated.')">Save Changes</button>
        </form>

    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
