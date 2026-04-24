<?php
require_once 'includes/header.php';

// Fetch top 10 places by popularity
$stmt = $pdo->query("SELECT * FROM places ORDER BY popularity_rank ASC LIMIT 10");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="hero" style="background: white; padding: 100px 0;">
    <div class="hero-container" style="padding: 0 5%; width: 100%;">
        <div class="flex flex-column md-flex-row items-center gap-5">
            <div class="hero-content" style="flex: 1; text-align: left;">
                <div class="mb-4">
                    <span style="background: #fff0f3; color: #ff2d75; padding: 6px 16px; border-radius: 30px; font-size: 0.8rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">
                        • Sri Lanka's Hill Capital
                    </span>
                </div>
                <h1 style="font-family: 'Playfair Display', serif; font-size: 4.5rem; line-height: 1.1; color: #1a1a1a; margin-bottom: 2rem;">
                    Discover the Magic of <span style="display:block; color: #cc4c54; font-style: italic;">Kandy City</span>
                </h1>
                <p style="font-size: 1.1rem; color: #666; max-width: 450px; line-height: 1.6; margin-bottom: 3rem;">
                    Plan your perfect one-day tour through the spiritual and natural wonders of Sri Lanka's most enchanting destination.
                </p>
                <div class="flex gap-4">
                    <?php if(!$is_logged_in): ?>
                        <a href="register.php" class="btn btn-primary btn-lg" style="background: #cc4c54; border-color: #cc4c54; padding: 16px 32px; border-radius: 12px; font-size: 1rem;">Start Planning</a>
                        <a href="places.php" class="btn btn-outline btn-lg" style="padding: 16px 32px; border-radius: 12px; border: 1px solid #eee; background: #fafafa; color: #333; font-size: 1rem;">Explore Destinations</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg" style="background: #cc4c54; border-color: #cc4c54; padding: 16px 32px; border-radius: 12px;"><i class="fa-solid fa-map-location-dot"></i> My Kandy Itinerary</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hero-image-wrapper" style="flex: 1.2; position: relative;">
                <div style="overflow: hidden; border-radius: 40px; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                    <img src="https://as2.ftcdn.net/jpg/10/82/81/49/1000_F_1082814980_IMkQOjKq8BG877sRqhj2hmNrM1UZYs1F.webp" 
                         alt="Beautiful Kandy City" 
                         style="width: 100%; display: block;">
                </div>
                
                <!-- Floating Testimonial Card -->
                <div style="position: absolute; bottom: 30px; left: -20px; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px; min-width: 200px; border: 1px solid #f0f0f0;">
                    <div style="background: #fff0f3; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #cc4c54;">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.9rem; color: #1a1a1a;">Top Rated City</div>
                        <div style="font-size: 0.75rem; color: #888;">2,400+ happy travellers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="section-header text-center mb-5">
    <h2 class="display-4">Trending Destinations</h2>
    <p class="text-muted">Handpicked places that are currently trending among our community.</p>
</div>

<div class="grid grid-cols-1 grid-cols-2 grid-cols-3">
    <?php foreach($places as $place): ?>
    <div class="card">
        <div class="card-img-wrapper">
            <img src="<?= htmlspecialchars($place['image_url']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" class="card-img">
            <div class="card-badge">#<?= htmlspecialchars($place['popularity_rank']) ?> Popularity</div>
        </div>
        <div class="card-body">
            <h3 class="card-title"><?= htmlspecialchars($place['name']) ?></h3>
            <p class="text-muted mb-3"><i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i> <?= htmlspecialchars($place['location']) ?></p>
            <p class="card-text"><?= htmlspecialchars(substr($place['description'], 0, 100)) ?>...</p>
            
            <div class="card-footer mt-4">
                <a href="place_details.php?id=<?= $place['id'] ?>" class="btn btn-secondary btn-block">
                    <i class="fa-solid fa-circle-info"></i> View Details
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="text-center mt-5">
    <a href="places.php" class="btn btn-primary btn-lg">View All Destinations <i class="fa-solid fa-arrow-right"></i></a>
</div>

<?php require_once 'includes/footer.php'; ?>
