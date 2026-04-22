<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planora - Your Ultimate Travel Planner</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fa-solid fa-paper-plane"></i> Planora
            </a>
            
            <div class="nav-toggle" id="mobile-toggle">
                <i class="fa-solid fa-bars-staggered"></i>
            </div>
            
            <ul class="nav-menu" id="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="places.php" class="nav-link">Explore</a></li>
                
                <?php if($is_logged_in): ?>
                    <li><a href="dashboard.php" class="nav-link">My Plans</a></li>
                    <li><a href="logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary btn-sm">Join Now</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="flash-container">
        <?php displayFlash(); ?>
    </div>
    
    <main class="main-content">
