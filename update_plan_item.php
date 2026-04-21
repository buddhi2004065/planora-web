<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)$_POST['item_id'];
    $plan_id = (int)$_POST['plan_id'];
    $day_number = (int)$_POST['day_number'];
    $notes = trim($_POST['notes']);
    
    // Verify ownership via plan_id
    $stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$plan_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $update = $pdo->prepare("UPDATE plan_items SET day_number = ?, notes = ? WHERE id = ? AND plan_id = ?");
        if ($update->execute([$day_number, $notes, $item_id, $plan_id])) {
            setFlash('success', 'Plan item updated.');
        } else {
            setFlash('error', 'Failed to update item.');
        }
    } else {
        setFlash('error', 'Access denied.');
    }
    header("Location: view_plan.php?id=" . $plan_id);
    exit;
}
header("Location: dashboard.php");
exit;
?>
