<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $place_id = (int)($_POST['place_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if (!$user_id) {
        setFlash('error', 'You must be logged in to post a review.');
        header("Location: login.php");
        exit;
    }

    if ($place_id && $comment) {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, place_id, rating, comment) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $place_id, $rating, $comment])) {
            setFlash('success', 'Review posted successfully! Thank you for sharing.');
        } else {
            setFlash('error', 'Failed to post review.');
        }
    } else {
        setFlash('error', 'Please provide a comment and rating.');
    }

    header("Location: place_details.php?id=" . $place_id);
    exit;
}
?>
