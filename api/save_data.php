<?php
// api/save_data.php
include '../includes/db_connect.php';

// Include your logic files so the system can make real recommendations
include '../includes/crops.php'; 
include '../includes/dss_logic.php'; 

date_default_timezone_set('Asia/Manila');

// 1. Get the REAL sensor data sent by your ESP32
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['temperature']) && isset($data['humidity'])) {
    // These are your actual, physical DHT11 readings
    $temp = (float)$data['temperature'];
    $hum = (float)$data['humidity'];
    
    // 2. Fetch the REAL settings and weather forecast
    $settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();
    $weather = fetch_micro_season_forecast(); 
    
    // Get the real 14-day rainfall total
    $rain_forecast = (float)$weather['two_week_total']; 

    // 2.5 Only log a new row when values actually change
    $last_row = $conn->query("SELECT temp, hum, rain_forecast FROM sensor_data ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($last_row) {
        $last_temp = (float)$last_row['temp'];
        $last_hum = (float)$last_row['hum'];
        $last_rain = (float)$last_row['rain_forecast'];

        // Small tolerance so tiny sensor noise does not spam logs
        $temp_changed = abs($temp - $last_temp) >= 0.1;
        $hum_changed = abs($hum - $last_hum) >= 0.1;
        $rain_changed = abs($rain_forecast - $last_rain) >= 0.1;

        if (!$temp_changed && !$hum_changed && !$rain_changed) {
            echo "No significant change in readings; log skipped.";
            $conn->close();
            exit;
        }
    }
    
    // 3. Generate the REAL recommendation based on the live sensor data
    $active_season = get_current_season($temp, $hum, $rain_forecast, $settings['rain_threshold']);
    
    $recommendation = "No suitable crop found for " . $active_season;
    foreach ($CROP_DATABASE as $crop) {
        if (in_array($active_season, $crop['seasons'])) {
            $recommendation = "Plant " . $crop['name'] . " (" . $active_season . ")";
            break; 
        }
    }

    // 4. Save ALL the real data into MySQL
    $stmt = $conn->prepare("INSERT INTO sensor_data (temp, hum, rain_forecast, recommendation) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ddds", $temp, $hum, $rain_forecast, $recommendation);
    
    if ($stmt->execute()) {
        echo "Success: Real data and recommendation logged.";
    } else {
        echo "Database Error: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "Error: No valid sensor data received from ESP32.";
}
$conn->close();
?>