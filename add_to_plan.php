<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    setFlash('error', 'Please log in.');
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_id = (int)$_POST['plan_id'];
    $place_id = (int)$_POST['place_id'];
    
    // Verify plan belongs to user
    $stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$plan_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() == 0) {
        setFlash('error', 'Invalid plan.');
        header("Location: index.php");
        exit;
    }
    
    // Check if place is already in plan
    $check = $pdo->prepare("SELECT id FROM plan_items WHERE plan_id = ? AND place_id = ?");
    $check->execute([$plan_id, $place_id]);
    
    if ($check->rowCount() > 0) {
        setFlash('info', 'Place is already in this plan. You can adjust its day/notes in the plan view.');
        header("Location: view_plan.php?id=" . $plan_id);
        exit;
    }
    
    // Add item
    $insert = $pdo->prepare("INSERT INTO plan_items (plan_id, place_id, day_number) VALUES (?, ?, 1)");
    if ($insert->execute([$plan_id, $place_id])) {
        setFlash('success', 'Place added to your plan!');
    } else {
        setFlash('error', 'Failed to add place.');
    }
    
    header("Location: view_plan.php?id=" . $plan_id);
    exit;
}
header("Location: dashboard.php");
exit;
?>
