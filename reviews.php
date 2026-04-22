<?php
require_once 'includes/header.php';

// Fetch all reviews with user and place names
$stmt = $pdo->query("
    SELECT r.*, u.name as user_name, p.name as place_name, p.image_url 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN places p ON r.place_id = p.id 
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section-header text-center mb-5">
    <h2 class="display-4">Traveler Reviews</h2>
    <p class="text-muted">See what others are saying about their experiences in Kandy.</p>
</div>

<?php if (empty($reviews)): ?>
    <div class="card p-5 text-center">
        <div style="font-size: 3rem; color: #eee;" class="mb-3"><i class="fa-solid fa-comments"></i></div>
        <h3>No reviews yet</h3>
        <p class="text-muted">Be the first to share your experience by visiting a destination details page!</p>
        <a href="places.php" class="btn btn-primary mt-3">Explore Places</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 grid-cols-2">
        <?php foreach ($reviews as $review): ?>
            <div class="card p-4">
                <div class="flex gap-4 items-center mb-4">
                    <img src="<?= htmlspecialchars($review['image_url']) ?>" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover;">
                    <div>
                        <h4 class="mb-0"><?= htmlspecialchars($review['place_name']) ?></h4>
                        <div style="color: #fbbf24;">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fa-<?= $i < $review['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <p class="mb-3" style="font-style: italic; color: #444;">"<?= htmlspecialchars($review['comment']) ?>"</p>
                <div class="flex justify-between items-center mt-auto pt-3 border-top" style="border-color: #f1f1f1;">
                    <span class="text-muted small"><i class="fa-solid fa-user"></i> By <?= htmlspecialchars($review['user_name']) ?></span>
                    <span class="text-muted small"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
