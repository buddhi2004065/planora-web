<?php
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM places ORDER BY name ASC");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If logged in, fetch user's plans for the 'Add to Plan' dropdown
$plans = [];
if ($is_logged_in) {
    $planStmt = $pdo->prepare("SELECT id, plan_name FROM plans WHERE user_id = ? ORDER BY created_at DESC");
    $planStmt->execute([$_SESSION['user_id']]);
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="mb-5">
    <h2 class="text-center">All Destinations</h2>
    <p class="text-center text-muted">Explore our curated list of world-famous destinations.</p>
</div>

<div class="grid grid-cols-2">
    <?php foreach($places as $place): ?>
    <div class="card" style="flex-direction: row; align-items: stretch; height: auto;">
        <img src="<?= htmlspecialchars($place['image_url']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" style="width: 250px; height: 100%; object-fit: cover;">
        <div class="card-body" style="padding: 1.5rem;">
            <div class="flex justify-between items-center mb-2">
                <h3 class="card-title mb-0"><?= htmlspecialchars($place['name']) ?></h3>
                <span class="rank-badge">Rank #<?= htmlspecialchars($place['popularity_rank']) ?></span>
            </div>
            <p class="text-muted mb-2"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($place['location']) ?></p>
            <p class="card-text"><?= htmlspecialchars($place['description']) ?></p>
            
            <?php if($is_logged_in): ?>
                <?php if(empty($plans)): ?>
                    <a href="create_plan.php" class="btn btn-sm btn-primary">Create a plan first</a>
                <?php else: ?>
                    <form action="add_to_plan.php" method="POST" class="mt-3 flex gap-2">
                        <input type="hidden" name="place_id" value="<?= $place['id'] ?>">
                        <select name="plan_id" class="form-control" style="padding: 0.4rem; height: auto;" required>
                            <option value="">Select Plan...</option>
                            <?php foreach($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['plan_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Add</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-secondary mt-3">Log in to add</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
