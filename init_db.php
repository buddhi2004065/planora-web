<?php
require_once 'config.php';

// Create tables
$userTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($userTable);

$placesTable = "
CREATE TABLE IF NOT EXISTS places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    popularity_rank INT NOT NULL
)";
$pdo->exec($placesTable);

$plansTable = "
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$pdo->exec($plansTable);

$planItemsTable = "
CREATE TABLE IF NOT EXISTS plan_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    place_id INT NOT NULL,
    day_number INT DEFAULT 1,
    notes TEXT,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
)";
$pdo->exec($planItemsTable);

// Seeding standard places if none exist
$stmt = $pdo->query("SELECT COUNT(*) FROM places");
if ($stmt->fetchColumn() == 0) {
    $places = [
        ['Paris', 'The city of light, famous for the Eiffel Tower and Louvre.', 'France', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=600&q=80', 1],
        ['Tokyo', 'A bustling metropolis mixing the ultramodern and the traditional.', 'Japan', 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?auto=format&fit=crop&w=600&q=80', 2],
        ['Rome', 'Capital of Italy, known for its nearly 3,000 years of globally influential art and architecture.', 'Italy', 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?auto=format&fit=crop&w=600&q=80', 3],
        ['New York City', 'The Big Apple, featuring Times Square, Central Park, and the Statue of Liberty.', 'USA', 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?auto=format&fit=crop&w=600&q=80', 4],
        ['London', 'Historic city on the Thames River, home to Big Ben and the British Museum.', 'UK', 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=600&q=80', 5],
        ['Dubai', 'A city of skyscrapers, ports, and beaches, where big business takes place alongside sun-seeking tourism.', 'UAE', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=80', 6],
        ['Bali', 'An Indonesian island known for its forested volcanic mountains, iconic rice paddies, and beaches.', 'Indonesia', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=80', 7],
        ['Istanbul', 'A transcontinental city connecting Europe and Asia across the Bosphorus Strait.', 'Turkey', 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?auto=format&fit=crop&w=600&q=80', 8],
        ['Bangkok', 'Thailand’s capital, known for ornate shrines and vibrant street life.', 'Thailand', 'https://images.unsplash.com/photo-1508009603885-247a53f021e1?auto=format&fit=crop&w=600&q=80', 9],
        ['Barcelona', 'The cosmopolitan capital of Spain’s Catalonia region, known for its art and architecture.', 'Spain', 'https://images.unsplash.com/photo-1583422409516-bfceb5e3de9e?auto=format&fit=crop&w=600&q=80', 10],
    ];
    
    $insert = $pdo->prepare("INSERT INTO places (name, description, location, image_url, popularity_rank) VALUES (?, ?, ?, ?, ?)");
    foreach ($places as $place) {
        $insert->execute($place);
    }
}

setFlash('success', 'Database initialized successfully!');
header("Location: index.php");
exit;
?>
