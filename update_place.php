<?php
require_once 'config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    setFlash('error', 'Authentication failed. Only Super Admins can perform this action.');
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $place_id = (int)$_POST['place_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    $location = trim($_POST['location']);
    $dress_code = trim($_POST['dress_code']);
    $best_time = trim($_POST['best_time']);
    $ticket_price = trim($_POST['ticket_price']);
    $restaurants = trim($_POST['restaurants']);

    $stmt = $pdo->prepare("UPDATE places SET name = ?, description = ?, image_url = ?, location = ?, dress_code = ?, best_time = ?, ticket_price = ?, restaurants = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $image_url, $location, $dress_code, $best_time, $ticket_price, $restaurants, $place_id])) {
        setFlash('success', 'Destination updated successfully!');
    } else {
        setFlash('error', 'Failed to update destination.');
    }
    
    header("Location: place_details.php?id=" . $place_id);
    exit;
}
?>
