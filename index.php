<?php
require_once 'includes/header.php';

// Fetch top 10 places by popularity
$stmt = $pdo->query("SELECT * FROM places ORDER BY popularity_rank ASC LIMIT 10");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="hero">
    <div class="hero-container" style="padding: 0 5%; width: 100%;">
        <div class="hero-content">
            <h1>Plan Your Next <span style="display:block;">Dream Journey</span></h1>
            <p>Your one-stop destination for planning, organizing, and discovering unique travel experiences across the globe.</p>
            <div class="hero-actions">
                <?php if(!$is_logged_in): ?>
                    <a href="register.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-sparkles"></i> Create Free Account</a>
                    <a href="places.php" class="btn btn-outline btn-lg">Explore Places</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                <?php endif; ?>
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
