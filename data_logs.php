<?php
// data_logs.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// FORCE PHILIPPINES TIMEZONE (Matches Nagcarlan/Lipa City)
date_default_timezone_set('Asia/Manila');

include 'includes/db_connect.php';

// Get today's date in YYYY-MM-DD format based on Manila time
$today = date('Y-m-d');

// If a date is provided in the URL, use it; otherwise, default to TODAY
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// FIX: Fetch logs using 'created_at' to match your updated MySQL schema
$stmt = $conn->prepare("SELECT * FROM sensor_data WHERE DATE(created_at) = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriSense - Data Logs</title>
    <link rel="stylesheet" href="static/style.css?v=<?php echo time(); ?>">
    <style>
        /* Local Table Styles - Centered with Green Header */
        .log-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
            border: 1px solid #e2e8f0; 
        }

        /* Centered Green Header Row */
        .log-table th { 
            background: #1b4332; 
            color: #d8f3dc; 
            text-align: center; 
            padding: 18px 20px; 
            font-weight: 700; 
            font-size: 13px; 
            letter-spacing: 0.5px; 
            text-transform: uppercase; 
        }

        /* Centered Data Cells */
        .log-table td { 
            padding: 15px 20px; 
            border-bottom: 1px solid #f0f4f8; 
            color: #2c3e50; 
            font-size: 15px; 
            text-align: center; 
        }

        .log-table tr:last-child td { border-bottom: none; }
        .log-table tr:hover { background-color: #f8fcf9; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #d8f3dc; color: #1b4332; text-transform: uppercase; display: inline-block; }
        .export-btn { background: #2d6a4f; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
        .export-btn:hover { background: #1b4332; transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header" style="margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center; gap:16px;">
            <div>
                <h1>System Data Logs</h1>
                <p style="color: #748c94; margin-bottom: 0; font-size: 14px;">
                    View sensor readings stored in MySQL for the selected day (PHT).
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <form method="get" style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-size:13px; color:#555; font-weight: 600;">
                        Select date:
                        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" style="padding: 6px; border-radius: 4px; border: 1px solid #ddd; font-family: 'Poppins', sans-serif;">
                    </label>
                    <button type="submit" class="export-btn">Load Day</button>
                </form>
                <a href="export_csv.php" class="export-btn">
                    <img src="https://unpkg.com/lucide-static@latest/icons/download.svg" width="18" class="icon-white"> 
                    Download CSV Report
                </a>
            </div>
        </div>

        <?php if ($logs->num_rows == 0): ?>
            <div class="card" style="text-align: center; padding: 50px 20px; border-top: 5px solid #40916c;">
                <p style="color: #95a5a6;">No data logged yet for <?php echo htmlspecialchars($selected_date); ?>.</p>
            </div>
        <?php else: ?>
            <table class="log-table">
                <thead>
                    <tr>
                        <th>Created At</th>
                        <th>Temperature</th>
                        <th>Humidity</th>
                        <th>Rain (14‑Day)</th>
                        <th>Event Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: 600; color: #40916c;">
                            <?php echo date("H:i:s", strtotime($log['created_at'])); ?>
                        </td>
                        <td><?php echo number_format($log['temp'], 1); ?> °C</td>
                        <td><?php echo number_format($log['hum'], 1); ?> %</td>
                        <td><?php echo isset($log['rain_forecast']) ? number_format($log['rain_forecast'], 1) . ' mm' : '--'; ?></td>
                        <td><span class="badge">Recorded</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="static/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>