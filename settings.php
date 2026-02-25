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

    $update = $conn->prepare("UPDATE system_settings SET heat_threshold=?, hum_threshold=?, rain_threshold=?, heartbeat_timeout=? WHERE id=1");
    $update->bind_param("dddi", $heat, $hum, $rain, $timeout);
    $update->execute();
}

// Fetch current settings to populate the form
$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Settings</title>
    <link rel="stylesheet" href="static/style.css?v=13">
    <style>
        /* ADDED: Background contrast and sharper card definition to prevent blending */
        body { background-color: #f0f4f2 !important; }

        .settings-group { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            /* Enhanced shadow and border for depth */
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            border: 1px solid #d1dbd4;
            border-left: 5px solid #40916c; 
        }

        /* Restored original styles */
        .settings-group h3 { color: #1b4332; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 18px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 12px; color: #748c94; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #fff; color: #2c3e50; transition: border-color 0.3s; }
        .form-group input:focus { border-color: #40916c; outline: none; }
        .form-group input:disabled { background: #f1f5f9; cursor: not-allowed; opacity: 0.7; border-color: #e2e8f0; }
        .btn-save { background: #2d6a4f; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: 0.2s; font-size: 14px; }
        .btn-save:hover { background: #1b4332; transform: translateY(-2px); }
    </style>
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
                </div>
                <p style="font-size: 13px; color: #cc5500; margin-top: 10px; font-weight: 500; background: #fffaf0; padding: 10px; border-radius: 6px; border-left: 3px solid #cc5500;">
                    These parameters are strictly visual to define the specific boundaries of this study. The weather API is hardcoded strictly to Nagcarlan.
                </p>
            </div>

            <div class="settings-group" style="border-left-color: #e67e22;">
                <h3><img src="https://unpkg.com/lucide-static@latest/icons/triangle-alert.svg" width="20" style="filter: brightness(0) saturate(100%) invert(48%) sepia(87%) saturate(1637%) hue-rotate(352deg) brightness(97%) contrast(88%);"> Alert Thresholds (DSS Rules)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Extreme Heat Trigger (°C)</label>
                        <input type="number" step="0.1" name="heat_threshold" value="<?php echo htmlspecialchars($settings['heat_threshold']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>High Transpiration Trigger (%)</label>
                        <input type="number" step="0.1" name="humidity_threshold" value="<?php echo htmlspecialchars($settings['hum_threshold']); ?>" required>
                    </div>
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
                </div>
                <p style="font-size: 12px; color: #95a5a6; margin-top: 5px;">* Adjusting these triggers will instantly alter how the system generates crop recommendations and system alerts.</p>
            </div>
            
            <button type="submit" class="btn-save" onclick="alert('Configuration saved securely. DSS rules updated.')">Save Changes</button>
        </form>

    </div>
    <script src="static/js/app.js?v=13"></script>
</body>
</html>