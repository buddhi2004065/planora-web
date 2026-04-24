<?php
require_once 'config.php';

$place_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM places WHERE id = ?");
$stmt->execute([$place_id]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    setFlash('error', 'Place not found.');
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Fetch user's plans for the 'Add to Plan' logic
$plans = [];
if ($is_logged_in) {
    $planStmt = $pdo->prepare("SELECT id, plan_name FROM plans WHERE user_id = ? ORDER BY created_at DESC");
    $planStmt->execute([$_SESSION['user_id']]);
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle Edit Mode Toggle (ONLY FOR ADMINS)
$is_edit_mode = $is_admin && isset($_GET['edit']) && $_GET['edit'] == 1;

require_once 'includes/header.php';
?>

<?php if($is_edit_mode): ?>
    <div class="edit-overlay" style="background: rgba(255,255,255,0.9); padding: 3rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow); margin-bottom: 3rem; border: 2px dashed var(--primary-color);">
        <h2 class="mb-4"><i class="fa-solid fa-pen-to-square"></i> Edit Destination</h2>
        <form action="update_place.php" method="POST">
            <input type="hidden" name="place_id" value="<?= $place['id'] ?>">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Destination Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($place['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($place['location']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="image_url" class="form-control" value="<?= htmlspecialchars($place['image_url']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Dress Code</label>
                    <input type="text" name="dress_code" class="form-control" value="<?= htmlspecialchars($place['dress_code'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Best Time to Visit</label>
                    <input type="text" name="best_time" class="form-control" value="<?= htmlspecialchars($place['best_time'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Ticket Price</label>
                    <input type="text" name="ticket_price" class="form-control" value="<?= htmlspecialchars($place['ticket_price'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nearby Restaurants (comma separated)</label>
                <input type="text" name="restaurants" class="form-control" value="<?= htmlspecialchars($place['restaurants'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($place['description']) ?></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="place_details.php?id=<?= $place['id'] ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="place-details-page">
    <div class="flex flex-column md-flex-row gap-5">
        <!-- Image Section -->
        <div class="place-image-col" style="flex: 1;">
            <div class="card" style="padding: 0; overflow: hidden; border-radius: var(--border-radius-xl);">
                <img src="<?= htmlspecialchars($place['image_url']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" style="width: 100%; height: 500px; object-fit: cover;">
            </div>
        </div>

        <!-- Info Section -->
        <div class="place-info-col" style="flex: 1;">
            <nav class="breadcrumb mb-3">
                <a href="index.php" style="color: var(--text-muted);">Home</a> / 
                <a href="places.php" style="color: var(--text-muted);">Places</a> / 
                <span style="color: var(--primary-color); font-weight: 700;"><?= htmlspecialchars($place['name']) ?></span>
            </nav>

            <div class="flex justify-between items-center mb-4">
                <h1 class="display-4 mb-0"><?= htmlspecialchars($place['name']) ?></h1>
                <div class="flex gap-2">
                    <?php if($is_admin): ?>
                    <a href="place_details.php?id=<?= $place['id'] ?>&edit=1" class="btn btn-secondary btn-sm" title="Edit this destination">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                    <?php endif; ?>
                    <span class="card-badge" style="position: static; font-size: 1rem;">Rank #<?= htmlspecialchars($place['popularity_rank']) ?></span>
                </div>
            </div>

            <p class="text-xl text-muted mb-4"><i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i> <?= htmlspecialchars($place['location']) ?></p>
            
            <div class="description-box p-4 bg-white mb-4" style="border-radius: var(--border-radius-lg); border: 1px solid #eee; line-height: 1.8;">
                <h3 class="mb-3">About this destination</h3>
                <p><?= nl2br(htmlspecialchars($place['description'])) ?></p>
            </div>

            <!-- Quick Guide Table/Grid -->
            <div class="card p-4 mb-5" style="border-color: #f1f1f1;">
                <h4 class="mb-4">Traveler's Quick Guide</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="info-item">
                        <small class="text-muted block mb-1"><i class="fa-solid fa-shirt"></i> Dress Code</small>
                        <p class="font-bold"><?= htmlspecialchars($place['dress_code'] ?? 'N/A') ?></p>
                    </div>
                    <div class="info-item">
                        <small class="text-muted block mb-1"><i class="fa-solid fa-sun"></i> Best Time</small>
                        <p class="font-bold"><?= htmlspecialchars($place['best_time'] ?? 'N/A') ?></p>
                    </div>
                    <div class="info-item">
                        <small class="text-muted block mb-1"><i class="fa-solid fa-ticket"></i> Entry Fee</small>
                        <p class="font-bold"><?= htmlspecialchars($place['ticket_price'] ?? 'Free') ?></p>
                    </div>
                    <div class="info-item">
                        <small class="text-muted block mb-1"><i class="fa-solid fa-utensils"></i> Nearby Eats</small>
                        <p class="font-bold"><?= htmlspecialchars($place['restaurants'] ?? 'Local stalls') ?></p>
                    </div>
                </div>
            </div>

            <?php if($is_logged_in && !$is_admin): ?>
                <div class="card p-4" style="background: var(--primary-light); border: 1px solid var(--primary-color); border-radius: var(--border-radius-lg);">
                    <h4 class="mb-3">Add to your itinerary</h4>
                    <?php if(empty($plans)): ?>
                        <a href="create_plan.php" class="btn btn-primary btn-block">Create a plan first</a>
                    <?php else: ?>
                        <form action="add_to_plan.php" method="POST" class="flex gap-2">
                            <input type="hidden" name="place_id" value="<?= $place['id'] ?>">
                            <select name="plan_id" class="form-control" required>
                                <option value="" disabled selected>Select a plan...</option>
                                <?php foreach($plans as $plan): ?>
                                    <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['plan_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Add to Plan</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif(!$is_admin): ?>
                <div class="card p-4 text-center">
                    <p class="mb-3">Want to visit <?= htmlspecialchars($place['name']) ?>?</p>
                    <a href="login.php" class="btn btn-primary btn-block">Log in to Start Planning</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Section -->
    <?php if($place['latitude']): ?>
    <div class="mt-5">
        <h3 class="mb-4">Location on Map</h3>
        <div id="place-map" style="height: 400px; border-radius: var(--border-radius-xl); border: 4px solid white; box-shadow: var(--shadow);"></div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        var map = L.map('place-map').setView([<?= $place['latitude'] ?>, <?= $place['longitude'] ?>], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?= $place['latitude'] ?>, <?= $place['longitude'] ?>]).addTo(map)
            .bindPopup("<b><?= htmlspecialchars($place['name']) ?></b>").openPopup();
    </script>
    <?php endif; ?>

    <!-- Reviews Section -->
    <div class="mt-5 pt-5 border-top" style="border-color: #eee;">
        <div class="flex justify-between items-center mb-5">
            <h3><i class="fa-solid fa-star" style="color: #fbbf24;"></i> Reviews & Ratings</h3>
            <?php if ($is_logged_in && !$is_admin): ?>
                <button onclick="document.getElementById('review-form').scrollIntoView({behavior:'smooth'})" class="btn btn-outline btn-sm">Write a Review</button>
            <?php endif; ?>
        </div>

        <?php
        $rStmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.place_id = ? ORDER BY r.created_at DESC");
        $rStmt->execute([$place_id]);
        $placeReviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (empty($placeReviews)): ?>
            <p class="text-muted text-center py-5 bg-light rounded-xl">No reviews yet for this destination. Be the first to share your experience!</p>
        <?php else: ?>
            <div class="grid grid-cols-1 grid-cols-2 gap-4">
                <?php foreach ($placeReviews as $rev): ?>
                    <div class="card p-4">
                        <div class="flex justify-between mb-2">
                            <span class="font-bold"><?= htmlspecialchars($rev['user_name']) ?></span>
                            <span style="color: #fbbf24;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="fa-<?= $i <= $rev['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <p class="text-muted italic">"<?= htmlspecialchars($rev['comment']) ?>"</p>
                        <small class="text-muted block mt-2"><?= date('M d, Y', strtotime($rev['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Review Form -->
        <?php if ($is_logged_in && !$is_admin): ?>
            <div id="review-form" class="mt-5 card p-5" style="border: 2px solid var(--primary-light);">
                <h4 class="mb-4">Share your experience</h4>
                <form action="add_review.php" method="POST">
                    <input type="hidden" name="place_id" value="<?= $place_id ?>">
                    <div class="form-group" style="max-width: 300px;">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-control" required>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Very Good</option>
                            <option value="3">⭐⭐⭐ Good</option>
                            <option value="2">⭐⭐ Fair</option>
                            <option value="1">⭐ Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Comment</label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="What did you like the most? Any tips for other travelers?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Post Review <i class="fa-solid fa-paper-plane ml-2"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
