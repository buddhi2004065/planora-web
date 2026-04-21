<?php
require_once 'includes/header.php';

// Fetch top 10 places by popularity
$stmt = $pdo->query("SELECT * FROM places ORDER BY popularity_rank ASC LIMIT 10");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="hero">
    <h1>Welcome to Planora</h1>
    <p>Discover the world's most beautiful destinations and plan your next adventure with ease.</p>
    <?php if(!$is_logged_in): ?>
        <a href="register.php" class="btn btn-primary">Get Started</a>
    <?php else: ?>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <?php endif; ?>
</div>

<h2 class="mb-4 text-center">Top 10 Popular Destinations</h2>
<div class="grid grid-cols-3">
    <?php foreach($places as $place): ?>
    <div class="card">
        <img src="<?= htmlspecialchars($place['image_url']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" class="card-img">
        <div class="card-body">
            <h3 class="card-title"><?= htmlspecialchars($place['name']) ?></h3>
            <p class="text-muted mb-2"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($place['location']) ?></p>
            <p class="card-text"><?= htmlspecialchars(substr($place['description'], 0, 100)) ?>...</p>
            <div class="card-footer">
                <span class="rank-badge">#<?= htmlspecialchars($place['popularity_rank']) ?></span>
                <?php if($is_logged_in): ?>
                    <a href="places.php" class="btn btn-sm btn-secondary">Add to Plan</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
