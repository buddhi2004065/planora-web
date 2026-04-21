<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    header("Location: login.php");
    exit;
}

$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? AND user_id = ?");
$stmt->execute([$plan_id, $_SESSION['user_id']]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    setFlash('error', 'Plan not found or access denied.');
    header("Location: dashboard.php");
    exit;
}

// Fetch plan items joined with places
$itemStmt = $pdo->prepare("
    SELECT pi.*, p.name, p.location, p.image_url 
    FROM plan_items pi
    JOIN places p ON pi.place_id = p.id
    WHERE pi.plan_id = ?
    ORDER BY pi.day_number ASC, pi.id ASC
");
$itemStmt->execute([$plan_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>
<div class="dashboard-header">
    <div>
        <h2 class="mb-1"><?= htmlspecialchars($plan['plan_name']) ?></h2>
        <p class="text-muted">Created on <?= date('M d, Y', strtotime($plan['created_at'])) ?></p>
    </div>
    <div class="flex gap-2">
        <a href="places.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Places</a>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<?php if(empty($items)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clipboard-list"></i>
        <h3>This plan is empty</h3>
        <p>Go to destinations and add places to start building your itinerary.</p>
        <a href="places.php" class="btn btn-primary mt-3">Browse Destinations</a>
    </div>
<?php else: ?>
    <div class="timeline">
        <?php 
        $currentDay = -1;
        foreach($items as $item): 
            if ($item['day_number'] != $currentDay) {
                $currentDay = $item['day_number'];
                echo "<div class='timeline-item' style='padding-top: 0; padding-bottom: 0;'>";
                echo "<div class='timeline-day'>Day {$currentDay}</div>";
                echo "</div>";
            }
        ?>
        <div class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="timeline-details">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="m-0"><?= htmlspecialchars($item['name']) ?></h3>
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('edit-<?= $item['id'] ?>').style.display='block'" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></button>
                            <a href="remove_plan_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this place from your plan?')"><i class="fa-solid fa-xmark"></i></a>
                        </div>
                    </div>
                    <p class="text-muted"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?></p>
                    <?php if(!empty($item['notes'])): ?>
                        <div class="mt-3 p-3 bg-main" style="background:#f9fafb; border-radius: 6px; border-left: 3px solid var(--primary-color);">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($item['notes'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Edit Form (Hidden by default) -->
                    <div id="edit-<?= $item['id'] ?>" style="display:none; margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                        <form action="update_plan_item.php" method="POST">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-group mb-0">
                                    <label class="form-label text-sm">Day Number</label>
                                    <input type="number" name="day_number" value="<?= $item['day_number'] ?>" class="form-control" min="1" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label text-sm">Notes / Activities</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g., Guide tour at 10 AM"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="mt-2" style="text-align: right;">
                                <button type="button" onclick="document.getElementById('edit-<?= $item['id'] ?>').style.display='none'" class="btn btn-sm btn-secondary">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
