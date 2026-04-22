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

    $stmt = $pdo->prepare("UPDATE places SET name = ?, description = ?, image_url = ?, location = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $image_url, $location, $place_id])) {
        setFlash('success', 'Destination updated successfully!');
    } else {
        setFlash('error', 'Failed to update destination.');
    }
    
    header("Location: place_details.php?id=" . $place_id);
    exit;
}
?>
