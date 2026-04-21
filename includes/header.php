<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planora - Trip Planner</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fa-solid fa-earth-americas"></i> Planora
            </a>
            
            <div class="nav-toggle" id="mobile-menu">
                <i class="fa-solid fa-bars"></i>
            </div>
            
            <ul class="nav-menu" id="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="places.php" class="nav-link">Destinations</a></li>
                
                <?php if($is_logged_in): ?>
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">My Plans</a></li>
                    <li class="nav-item"><span class="nav-link text-muted">|</span></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link btn-secondary">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
                    <li class="nav-item"><a href="register.php" class="nav-link btn-primary">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="flash-container">
        <?php displayFlash(); ?>
    </div>
    
    <main class="main-content">
