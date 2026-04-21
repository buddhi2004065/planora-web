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
    <div class="grid grid-cols-2">
        <?php foreach($plans as $plan): ?>
        <?php 
            // Get item count
            $itemStmt = $pdo->prepare("SELECT COUNT(*) FROM plan_items WHERE plan_id = ?");
            $itemStmt->execute([$plan['id']]);
            $itemCount = $itemStmt->fetchColumn();
        ?>
        <div class="plan-item">
            <div class="plan-item-info">
                <h3><?= htmlspecialchars($plan['plan_name']) ?></h3>
                <p><i class="fa-regular fa-calendar-days"></i> Created on <?= date('M d, Y', strtotime($plan['created_at'])) ?> &bull; <?= $itemCount ?> places</p>
            </div>
            <div class="plan-actions">
                <a href="view_plan.php?id=<?= $plan['id'] ?>" class="btn btn-secondary btn-sm" title="View Plan"><i class="fa-solid fa-eye"></i></a>
                <a href="delete_plan.php?id=<?= $plan['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this plan?');" title="Delete Plan"><i class="fa-solid fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
