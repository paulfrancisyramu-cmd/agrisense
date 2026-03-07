<?php
/**
 * AgriSense - Nagcarlan DA Crop Database
 * Ported from Python/Flask CROP_DATABASE
 */

$CROP_DATABASE = [
    // Crops suitable for ALL Seasons (Wet/Rainy, Cool Dry, Hot Dry)
    [
        "name" => "Cabbage", 
        "image_url" => "https://img.icons8.com/color/96/cabbage.png", 
        "ideal_temp" => [15, 25], 
        "ideal_hum" => [60, 90], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Chinese Cabbage", 
        "image_url" => "https://img.icons8.com/?size=100&id=RtaauUCTxdA1&format=png&color=000000", 
        "ideal_temp" => [15, 25], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Beans / Sitao", 
        "image_url" => "https://img.icons8.com/?size=100&id=HplvJJynUBBe&format=png&color=000000", 
        "ideal_temp" => [20, 29], 
        "ideal_hum" => [65, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Cucumber", 
        "image_url" => "https://img.icons8.com/color/96/cucumber.png", 
        "ideal_temp" => [24, 30], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sweet Potato", 
        "image_url" => "https://img.icons8.com/color/96/sweet-potato.png", 
        "ideal_temp" => [20, 30], 
        "ideal_hum" => [50, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Ampalaya", 
        "image_url" => "https://img.icons8.com/color/96/melon.png", 
        "ideal_temp" => [24, 31], 
        "ideal_hum" => [65, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sayote", 
        "image_url" => "https://img.icons8.com/color/96/pear.png", 
        "ideal_temp" => [18, 25], 
        "ideal_hum" => [70, 90], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sili (Panigang/Tingala)", 
        "image_url" => "https://img.icons8.com/color/96/chili-pepper.png", 
        "ideal_temp" => [25, 32], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Radish", 
        "image_url" => "https://img.icons8.com/color/96/radish.png", 
        "ideal_temp" => [15, 25], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Pechay", 
        "image_url" => "https://img.icons8.com/color/96/bok-choy.png", 
        "ideal_temp" => [20, 30], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    
    // Wet/Rainy & Cool Dry Only
    [
        "name" => "Tomato", 
        "image_url" => "https://img.icons8.com/color/96/tomato.png", 
        "ideal_temp" => [20, 28], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    [
        "name" => "Squash", 
        "image_url" => "https://img.icons8.com/color/96/pumpkin.png", 
        "ideal_temp" => [25, 32], 
        "ideal_hum" => [50, 75], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    [
        "name" => "Pachoi", 
        "image_url" => "https://img.icons8.com/color/96/bok-choy.png", 
        "ideal_temp" => [20, 30], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    
    // Wet/Rainy & Hot Dry Only
    [
        "name" => "Eggplant", 
        "image_url" => "https://img.icons8.com/color/96/eggplant.png", 
        "ideal_temp" => [25, 32], 
        "ideal_hum" => [60, 75], 
        "seasons" => ["Wet/Rainy", "Hot Dry"]
    ],
    [
        "name" => "Mustasa", 
        "image_url" => "https://img.icons8.com/color/96/mustard.png", 
        "ideal_temp" => [15, 25], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Wet/Rainy", "Hot Dry"]
    ],
    
    // Specific Single Seasons
    [
        "name" => "Patola", 
        "image_url" => "https://img.icons8.com/color/96/zucchini.png", 
        "ideal_temp" => [25, 30], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Cool Dry"]
    ],
    [
        "name" => "Gabi", 
        "image_url" => "https://img.icons8.com/color/96/potato.png", 
        "ideal_temp" => [25, 35], 
        "ideal_hum" => [80, 95], 
        "seasons" => ["Hot Dry"]
    ]
];