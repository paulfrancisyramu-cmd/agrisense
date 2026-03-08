<?php 
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is admin - handle case where role doesn't exist yet
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<div class="mobile-navbar">
    <button id="menu-toggle" class="menu-btn">
        <img src="https://unpkg.com/lucide-static@latest/icons/menu.svg" width="24" class="icon-white">
    </button>
    <div class="mobile-logo">
        <img src="https://unpkg.com/lucide-static@latest/icons/leaf.svg" width="20" class="icon-white"> AgriSense
    </div>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="sidebar" id="main-sidebar">
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
    <?php if ($is_admin): ?>
    <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
        <img src="https://unpkg.com/lucide-static@latest/icons/settings.svg" width="18" class="icon-white"> Settings
    </a>
    <?php endif; ?>
    
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
            <img src="https://unpkg.com/lucide-static@latest/icons/log-out.svg" width="18" class="icon-white"> Log Out
        </a>
    </div>

<script>
// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const mainSidebar = document.getElementById('main-sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (menuToggle && mainSidebar && sidebarOverlay) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            mainSidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            mainSidebar.classList.remove('active');
            sidebarOverlay.classList.remove('show');
        });

        mainSidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                mainSidebar.classList.remove('active');
                sidebarOverlay.classList.remove('show');
            });
        });
    }
});
</script>
