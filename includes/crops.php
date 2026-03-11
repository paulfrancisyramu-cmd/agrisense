<?php
/**
 * AgriSense - Nagcarlan DA Crop Database
 * Combines hardcoded default crops with admin-created crops from database
 */

$CROP_DATABASE = [
    // Crops suitable for ALL Seasons (Wet/Rainy, Cool Dry, Hot Dry)
    [
        "name" => "Cabbage", 
        "image_url" => "https://img.icons8.com/color/96/cabbage.png", 
        "ideal_temp" => [17, 24], 
        "ideal_hum" => [60, 90], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Chinese Cabbage", 
        "image_url" => "https://img.icons8.com/?size=100&id=RtaauUCTxdA1&format=png&color=000000", 
        "ideal_temp" => [15, 20], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Beans / Sitao", 
        "image_url" => "https://img.icons8.com/?size=100&id=HplvJJynUBBe&format=png&color=000000", 
        "ideal_temp" => [20, 30], 
        "ideal_hum" => [65, 80], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Cucumber", 
        "image_url" => "https://img.icons8.com/color/96/cucumber.png", 
        "ideal_temp" => [23, 30], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sweet Potato", 
        "image_url" => "https://img.icons8.com/color/96/sweet-potato.png", 
        "ideal_temp" => [29, 35], 
        "ideal_hum" => [85, 90], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Ampalaya", 
        "image_url" => "https://img.icons8.com/color/96/melon.png", 
        "ideal_temp" => [24, 27], 
        "ideal_hum" => [65, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sayote", 
        "image_url" => "https://img.icons8.com/color/96/pear.png", 
        "ideal_temp" => [20, 30], 
        "ideal_hum" => [70, 90], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Sili (Panigang/Tingala)", 
        "image_url" => "https://img.icons8.com/color/96/chili-pepper.png", 
        "ideal_temp" => [21, 32], 
        "ideal_hum" => [65, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Radish", 
        "image_url" => "https://img.icons8.com/color/96/radish.png", 
        "ideal_temp" => [15, 21], 
        "ideal_hum" => [65, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    [
        "name" => "Pechay", 
        "image_url" => "https://img.icons8.com/color/96/bok-choy.png", 
        "ideal_temp" => [18, 22], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry", "Hot Dry"]
    ],
    
    // Wet/Rainy & Cool Dry Only
    [
        "name" => "Tomato", 
        "image_url" => "https://img.icons8.com/color/96/tomato.png", 
        "ideal_temp" => [20, 24], 
        "ideal_hum" => [60, 75], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    [
        "name" => "Squash", 
        "image_url" => "https://img.icons8.com/color/96/pumpkin.png", 
        "ideal_temp" => [18, 30], 
        "ideal_hum" => [50, 70], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    [
        "name" => "Pachoi", 
        "image_url" => "https://img.icons8.com/color/96/bok-choy.png", 
        "ideal_temp" => [18, 24], 
        "ideal_hum" => [60, 85], 
        "seasons" => ["Wet/Rainy", "Cool Dry"]
    ],
    
    // Wet/Rainy & Hot Dry Only
    [
        "name" => "Eggplant", 
        "image_url" => "https://img.icons8.com/color/96/eggplant.png", 
        "ideal_temp" => [20, 30], 
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
        "ideal_temp" => [18, 24], 
        "ideal_hum" => [60, 80], 
        "seasons" => ["Cool Dry"]
    ],
    [
        "name" => "Gabi", 
        "image_url" => "https://img.icons8.com/color/96/potato.png", 
        "ideal_temp" => [25, 30], 
        "ideal_hum" => [65, 80], 
        "seasons" => ["Hot Dry"]
    ]
];

/**
 * Get all crops (default + admin-created)
 * Fetches admin crops from database and merges with default crops
 */
function get_all_crops($conn = null) {
    global $CROP_DATABASE;
    
    $all_crops = $CROP_DATABASE;
    
    // If database connection is provided, fetch admin-created crops
    if ($conn !== null) {
        try {
            $stmt = $conn->query("SELECT * FROM crops ORDER BY name");
            $admin_crops = $stmt->fetchAll();
            
            foreach ($admin_crops as $crop) {
                // Parse seasons array (PostgreSQL returns as string {a,b,c} or array)
                $seasons = is_array($crop['seasons']) ? $crop['seasons'] : [];
                if (is_string($crop['seasons'])) {
                    $seasons = array_map('trim', explode(',', trim($crop['seasons'], '{}')));
                }
                
                $all_crops[] = [
                    "name" => $crop['name'],
                    "image_url" => $crop['image_url'],
                    "ideal_temp" => [(float)$crop['ideal_temp_min'], (float)$crop['ideal_temp_max']],
                    "ideal_hum" => [(float)$crop['ideal_hum_min'], (float)$crop['ideal_hum_max']],
                    "seasons" => $seasons,
                    "is_admin_created" => true // Flag to identify admin crops
                ];
            }
        } catch (Exception $e) {
            // If table doesn't exist or error, just return default crops
            error_log("Error fetching admin crops: " . $e->getMessage());
        }
    }
    
    return $all_crops;
}
