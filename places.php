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
<div class="section-header text-center mb-5">
    <h2 class="display-4">Explore Destinations</h2>
    <p class="text-muted">Discover breathtaking locations and start planning your next journey today.</p>
</div>

<div class="grid grid-cols-1 grid-cols-2">
    <?php foreach($places as $place): ?>
    <div class="card hover-reveal" style="display: flex; flex-direction: column;">
        <div class="card-img-wrapper" style="height: 300px;">
            <img src="<?= htmlspecialchars($place['image_url']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" class="card-img">
            <div class="card-badge">#<?= htmlspecialchars($place['popularity_rank']) ?> in World</div>
        </div>
        <div class="card-body">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="card-title mb-1"><?= htmlspecialchars($place['name']) ?></h3>
                    <p class="text-muted"><i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i> <?= htmlspecialchars($place['location']) ?></p>
                </div>
            </div>
            <p class="card-text mb-4"><?= htmlspecialchars(substr($place['description'], 0, 150)) ?>...</p>
            
            <a href="place_details.php?id=<?= $place['id'] ?>" class="btn btn-outline btn-block mb-3">
                <i class="fa-solid fa-circle-info"></i> Learn More & Details
            </a>
            
            <div class="card-action-bar mt-4 pt-4 border-top" style="border-top: 1px solid #f1f1f1;">
                <?php if($is_logged_in): ?>
                    <?php if(empty($plans)): ?>
                        <a href="create_plan.php" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-plus"></i> Create a plan to add this
                        </a>
                    <?php else: ?>
                        <form action="add_to_plan.php" method="POST" class="flex flex-column gap-3">
                            <input type="hidden" name="place_id" value="<?= $place['id'] ?>">
                            <div class="flex gap-2">
                                <select name="plan_id" class="form-control" required>
                                    <option value="" disabled selected>Select a plan...</option>
                                    <?php foreach($plans as $plan): ?>
                                        <option value="<?= $plan['id'] ?>"><?= htmlspecialchars($plan['plan_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-calendar-plus"></i> Add
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-block">
                        <i class="fa-solid fa-lock"></i> Log in to create plan
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
