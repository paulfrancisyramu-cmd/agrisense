<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <h2><img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="24" class="icon-white"> AgriSense</h2>
    
    <p class="sidebar-label">Main Menu</p>
    <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/layout-dashboard.svg" width="18" class="icon-white"> Dashboard
    </a>
    <a href="data_logs.php" class="<?php echo ($current_page == 'data_logs.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/database.svg" width="18" class="icon-white"> Data Logs
    </a>
    <a href="recommendations.php" class="<?php echo ($current_page == 'recommendations.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/sprout.svg" width="18" class="icon-white"> Recommendations
    </a>
    
    <p class="sidebar-label">System</p>
    <a href="devices.php" class="<?php echo ($current_page == 'devices.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/rss.svg" width="18" class="icon-white"> Device Status
    </a>
    <a href="alerts.php" class="<?php echo ($current_page == 'alerts.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/bell.svg" width="18" class="icon-white"> Alerts
    </a>
    <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/settings.svg" width="18" class="icon-white"> Settings
    </a>
    
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
            <img src="https://unpkg.com/lucide-static@latest/icons/log-out.svg" width="18" class="icon-white"> Log Out
        </a>
    </div>
</div>