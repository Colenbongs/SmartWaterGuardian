<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Water Guardian - Real-time water monitoring and conservation system">
    <title><?php echo $page_title ?? 'Smart Water Guardian'; ?></title>
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id']) && $_SESSION['logged_in']): ?>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="dashboard.php">
                    <i class="fas fa-water"></i>
                    <span>Smart Water Guardian</span>
                </a>
            </div>
            
            <button class="nav-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="history.php" class="nav-link <?php echo $current_page === 'history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a href="alerts.php" class="nav-link <?php echo $current_page === 'alerts.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i> Alerts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="thresholds.php" class="nav-link <?php echo $current_page === 'thresholds.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h"></i> Thresholds
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reviews.php" class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                </li>
                <?php if ($_SESSION['role'] === 'system_admin' || $_SESSION['role'] === 'municipal_admin'): ?>
                <li class="nav-item">
                    <a href="admin.php" class="nav-link <?php echo $current_page === 'admin.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <button onclick="logoutUser()" class="nav-link logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>
    
    <main>