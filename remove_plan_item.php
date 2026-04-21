<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Need to get plan_id to redirect and verify user
$stmt = $pdo->prepare("
    SELECT pi.plan_id 
    FROM plan_items pi
    JOIN plans p ON pi.plan_id = p.id
    WHERE pi.id = ? AND p.user_id = ?
");
$stmt->execute([$item_id, $_SESSION['user_id']]);
$plan_id = $stmt->fetchColumn();

if ($plan_id) {
    $delete = $pdo->prepare("DELETE FROM plan_items WHERE id = ?");
    if ($delete->execute([$item_id])) {
        setFlash('success', 'Item removed from plan.');
    } else {
        setFlash('error', 'Failed to remove item.');
    }
    header("Location: view_plan.php?id=" . $plan_id);
} else {
    setFlash('error', 'Item not found or access denied.');
    header("Location: dashboard.php");
}
exit;
?>
