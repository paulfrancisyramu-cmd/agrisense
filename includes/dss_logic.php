<?php
// includes/dss_logic.php

function fetch_micro_season_forecast() {
    $url = "https://api.open-meteo.com/v1/forecast?latitude=14.13&longitude=121.41&daily=precipitation_sum&timezone=auto&forecast_days=14";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);

    if ($res) {
        $data = json_decode($res, true);
        $rain_days = $data['daily']['precipitation_sum'] ?? array_fill(0, 14, 0);
        
        $week_1 = array_sum(array_slice($rain_days, 0, 7));
        $week_2 = array_sum(array_slice($rain_days, 7, 7));
        $total_forecast = round(array_sum($rain_days), 2);
        
        if ($week_2 > ($week_1 + 15)) {
            $trend = "Incoming Wet Season";
        } elseif ($week_1 > 15 && $week_2 < 5) {
            $trend = "Transition to Dry";
        } else {
            $trend = "Stable Conditions";
        }

        return [
            'rain_array' => $rain_days, 
            'rainfall' => round($week_1, 2), 
            'forecast_trend' => $trend, 
            'two_week_total' => $total_forecast, 
            'api_status' => "Online"
        ];
    }
    
    return [
        'rain_array' => array_fill(0, 14, 0), 
        'rainfall' => 0, 
        'forecast_trend' => "Stable Conditions", 
        'two_week_total' => 0, 
        'api_status' => "Offline"
    ];
}

function get_current_season($temp, $hum, $rain_total, $rain_threshold) {
    if ($rain_total >= $rain_threshold) return "Wet/Rainy";
    if ($hum >= 80.0) return "Wet/Rainy";
    if ($temp >= 28.5) return "Hot Dry";
    return "Cool Dry";
}
?>