<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    setFlash('error', 'Please log in to access your dashboard.');
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>
<div class="dashboard-header">
    <h2>My Travel Plans</h2>
    <a href="create_plan.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create New Plan</a>
</div>

<?php if(empty($plans)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-map-location-dot"></i>
        <h3>No plans yet!</h3>
        <p>Start planning your next adventure today.</p>
        <a href="create_plan.php" class="btn btn-primary mt-3">Create Plan</a>
    </div>
<?php else: ?>
<div class="grid grid-cols-1 grid-cols-2">
    <?php foreach($plans as $plan): ?>
    <?php 
        // Get item count
        $itemStmt = $pdo->prepare("SELECT COUNT(*) FROM plan_items WHERE plan_id = ?");
        $itemStmt->execute([$plan['id']]);
        $itemCount = $itemStmt->fetchColumn();
    ?>
    <div class="card">
        <div class="card-body">
            <div class="flex justify-between items-center mb-3">
                <div class="plan-icon" style="background: var(--primary-light); color: var(--primary-color); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fa-solid fa-map"></i>
                </div>
                <div class="text-right">
                    <span class="card-badge" style="position: static;"><?= $itemCount ?> Destination<?= $itemCount != 1 ? 's' : '' ?></span>
                </div>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($plan['plan_name']) ?></h3>
            <p class="text-muted"><i class="fa-regular fa-calendar-days"></i> Created on <?= date('M d, Y', strtotime($plan['created_at'])) ?></p>
            
            <div class="flex gap-2 mt-4">
                <a href="view_plan.php?id=<?= $plan['id'] ?>" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-folder-open"></i> Manage Plan
                </a>
                <a href="delete_plan.php?id=<?= $plan['id'] ?>" class="btn btn-secondary" style="padding: 0.8rem;" onclick="return confirm('Delete this plan?');">
                    <i class="fa-solid fa-trash-can" style="color: var(--danger);"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
