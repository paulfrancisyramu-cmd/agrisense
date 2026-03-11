<?php
// manage_crops.php - Admin crop management page
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

include 'includes/db_connect.php';
include 'includes/crops.php';

// Handle form submission for adding/editing crops
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_crop'])) {
        // Add new crop
        $name = trim($_POST['name']);
        $temp_min = (float)$_POST['temp_min'];
        $temp_max = (float)$_POST['temp_max'];
        $hum_min = (float)$_POST['hum_min'];
        $hum_max = (float)$_POST['hum_max'];
        $seasons = $_POST['seasons'] ?? [];
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/crops/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $name) . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_url = $target_path;
            }
        }
        
        // Use default image if none uploaded
        if (empty($image_url)) {
            $image_url = 'https://img.icons8.com/color/96/plant.png';
        }
        
        if (!empty($name) && $temp_min < $temp_max && $hum_min < $hum_max && !empty($seasons)) {
            $stmt = $conn->prepare("INSERT INTO crops (name, image_url, ideal_temp_min, ideal_temp_max, ideal_hum_min, ideal_hum_max, seasons, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $image_url, $temp_min, $temp_max, $hum_min, $hum_max, '{' . implode(',', $seasons) . '}', $_SESSION['user_id']]);
            $message = "Crop '$name' added successfully!";
        } else {
            $error = "Please fill all fields correctly. Temperature and humidity min must be less than max.";
        }
    } elseif (isset($_POST['delete_crop'])) {
        // Delete crop
        $crop_id = (int)$_POST['crop_id'];
        $stmt = $conn->prepare("DELETE FROM crops WHERE id = ? AND created_by = ?");
        $stmt->execute([$crop_id, $_SESSION['user_id']]);
        $message = "Crop deleted successfully!";
    }
}

// Fetch admin-created crops from database
$db_crops = $conn->query("SELECT * FROM crops ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSense - Manage Crops</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: #f0f4f2 !important; }
        .crop-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-top: 20px;
        }
        .crop-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .crop-card img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }
        .crop-card h4 { margin: 10px 0 5px; color: #1b4332; }
        .crop-card .details { font-size: 13px; color: #64748b; }
        .crop-card .seasons { 
            display: flex; 
            gap: 5px; 
            flex-wrap: wrap; 
            margin-top: 10px; 
        }
        .season-tag {
            font-size: 11px;
            padding: 3px 8px;
            background: #e2e8f0;
            border-radius: 12px;
            color: #475569;
        }
        .crop-card .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-delete:hover { background: #b91c1c; }
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .form-container h3 { margin-bottom: 20px; color: #1b4332; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .form-group-full { grid-column: 1 / -1; }
        .form-group label {
            display: block;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            border-color: #40916c;
            outline: none;
        }
        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-add {
            background: #40916c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
        }
        .btn-add:hover { background: #2d6a4f; }
        .message { 
            padding: 12px 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        .message.success { 
            background: #dcfce7; 
            color: #166534; 
            border: 1px solid #bbf7d0; 
        }
        .message.error { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="32" style="filter: invert(36%) sepia(62%) saturate(464%) hue-rotate(105deg) brightness(94%) contrast(84%);">
                <div>
                    <h1 style="margin: 0; font-size: 24px;">Manage Crops</h1>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Admin Crop Management</p>
                </div>
            </div>
            <div class="status" style="background: #dcfce7; color: #166534;">Admin Panel</div>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add New Crop Form -->
        <div class="form-container">
            <h3><img src="https://unpkg.com/lucide-static@latest/icons/plus-circle.svg" width="20" class="icon-green"> Add New Crop</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Crop Name *</label>
                        <input type="text" name="name" placeholder="e.g., Rice, Corn, Tomato" required>
                    </div>
                    <div class="form-group">
                        <label>Crop Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Min Temperature (°C) *</label>
                        <input type="number" step="0.1" name="temp_min" placeholder="e.g., 20" required>
                    </div>
                    <div class="form-group">
                        <label>Max Temperature (°C) *</label>
                        <input type="number" step="0.1" name="temp_max" placeholder="e.g., 30" required>
                    </div>
                    <div class="form-group">
                        <label>Min Humidity (%) *</label>
                        <input type="number" step="0.1" name="hum_min" placeholder="e.g., 60" required>
                    </div>
                    <div class="form-group">
                        <label>Max Humidity (%) *</label>
                        <input type="number" step="0.1" name="hum_max" placeholder="e.g., 80" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Seasons *</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="seasons[]" value="Wet/Rainy"> Wet/Rainy</label>
                            <label><input type="checkbox" name="seasons[]" value="Cool Dry"> Cool Dry</label>
                            <label><input type="checkbox" name="seasons[]" value="Hot Dry"> Hot Dry</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_crop" class="btn-add">Add Crop</button>
            </form>
        </div>

        <!-- List of Admin-Created Crops -->
        <h3 style="color: #1b4332; margin-top: 30px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/list.svg" width="20" class="icon-green"> 
            Admin-Created Crops (<?php echo count($db_crops); ?>)
        </h3>
        
        <?php if (empty($db_crops)): ?>
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <p>No crops created yet. Use the form above to add your first crop.</p>
            </div>
        <?php else: ?>
            <div class="crop-grid">
                <?php foreach ($db_crops as $crop): ?>
                    <div class="crop-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo htmlspecialchars($crop['image_url']); ?>" alt="<?php echo htmlspecialchars($crop['name']); ?>">
                            <div>
                                <h4><?php echo htmlspecialchars($crop['name']); ?></h4>
                                <div class="details">
                                    Temp: <?php echo $crop['ideal_temp_min']; ?> - <?php echo $crop['ideal_temp_max']; ?>°C<br>
                                    Humidity: <?php echo $crop['ideal_hum_min']; ?> - <?php echo $crop['ideal_hum_max']; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="seasons">
                            <?php 
                            $seasons = is_array($crop['seasons']) ? $crop['seasons'] : explode(',', trim($crop['seasons'], '{}'));
                            foreach ($seasons as $season): ?>
                                <span class="season-tag"><?php echo htmlspecialchars(trim($season)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="actions">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this crop?');">
                                <input type="hidden" name="crop_id" value="<?php echo $crop['id']; ?>">
                                <button type="submit" name="delete_crop" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Default Crops Reference -->
        <h3 style="color: #1b4332; margin-top: 40px;">
            <img src="https://unpkg.com/lucide-static@latest/icons/info.svg" width="20" class="icon-green"> 
            Default System Crops
        </h3>
        <p style="color: #64748b; margin-bottom: 20px;">These are built-in crops that cannot be modified from this page.</p>
        
        <div class="crop-grid">
            <?php foreach ($CROP_DATABASE as $crop): ?>
                <div class="crop-card" style="opacity: 0.8;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="<?php echo htmlspecialchars($crop['image_url']); ?>" alt="<?php echo htmlspecialchars($crop['name']); ?>">
                        <div>
                            <h4><?php echo htmlspecialchars($crop['name']); ?></h4>
                            <div class="details">
                                Temp: <?php echo $crop['ideal_temp'][0]; ?> - <?php echo $crop['ideal_temp'][1]; ?>°C<br>
                                Humidity: <?php echo $crop['ideal_hum'][0]; ?> - <?php echo $crop['ideal_hum'][1]; ?>%
                            </div>
                        </div>
                    </div>
                    <div class="seasons">
                        <?php foreach ($crop['seasons'] as $season): ?>
                            <span class="season-tag"><?php echo htmlspecialchars($season); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>

