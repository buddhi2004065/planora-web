<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
$stmt->execute([$plan_id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    $delete = $pdo->prepare("DELETE FROM plans WHERE id = ?");
    if ($delete->execute([$plan_id])) {
        setFlash('success', 'Plan deleted successfully.');
    } else {
        setFlash('error', 'Failed to delete plan.');
    }
} else {
    setFlash('error', 'Plan not found or access denied.');
}

header("Location: dashboard.php");
exit;
?>
