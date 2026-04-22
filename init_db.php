<?php
require_once 'config.php';

// Create tables
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("DROP TABLE IF EXISTS plan_items");
$pdo->exec("DROP TABLE IF EXISTS plans");
$pdo->exec("DROP TABLE IF EXISTS places");
$pdo->exec("DROP TABLE IF EXISTS users");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

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
    popularity_rank INT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8)
)";
$pdo->exec($placesTable);

$plansTable = "
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    transport_mode VARCHAR(20) DEFAULT 'driving',
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

// Seeding Kandy places
$pdo->exec("DELETE FROM places"); // Clear old generic places
$places = [
    ['Sri Dalada Maligawa', 'The Temple of the Sacred Tooth Relic is a Buddhist temple in the city of Kandy, Sri Lanka.', 'Kandy', 'https://images.unsplash.com/photo-1546708973-b339540b5162?auto=format&fit=crop&w=800&q=80', 1, 7.2936, 80.6413],
    ['Sri Dalada Museum', 'The Alut Maligawa has a museum with a collection of gifts from around the world.', 'Kandy', 'https://images.unsplash.com/photo-1588100142319-335384bc19b5?auto=format&fit=crop&w=800&q=80', 2, 7.2934, 80.6411],
    ['Kandy Lake', 'A beautiful artificial lake in the heart of the hill city of Kandy.', 'Kandy center', 'https://images.unsplash.com/photo-1625736113271-e2329b3986a4?auto=format&fit=crop&w=800&q=80', 3, 7.2925, 80.6400],
    ['Hanthana Mountain Range', 'A popular hiking destination offering scenic views of Kandy.', 'Kandy Outskirts', 'https://images.unsplash.com/photo-1580974511812-4b71971430ce?auto=format&fit=crop&w=800&q=80', 4, 7.2564, 80.6321],
    ['Udawatta Kele Sanctuary', 'A historic forest reserve on a hill-ridge in the city of Kandy.', 'Kandy', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=80', 5, 7.2985, 80.6415],
    ['Bahirawakanda Buddha Statue', 'Massive white Buddha statue offering panoramic views of the city.', 'Bahirawakanda', 'https://images.unsplash.com/photo-1625736113110-df96c00f681a?auto=format&fit=crop&w=800&q=80', 6, 7.2908, 80.6300],
    ['Three Temples Loop', 'Gadaladeniya, Embekka and Lankathilaka temples showcasing Gampola era architecture.', 'Peradeniya Outskirts', 'https://images.unsplash.com/photo-1590059397733-463e26bb52e3?auto=format&fit=crop&w=800&q=80', 7, 7.2587, 80.5756],
    ['Victoria Dam', 'The tallest dam in Sri Lanka, located in Teldeniya.', 'Teldeniya', 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80', 8, 7.2289, 80.7876],
    ['Hunnas Falls', 'A scenic waterfall located at the Hunnasgiriya mountain range.', 'Elkaduwa', 'https://images.unsplash.com/photo-1433086966358-54859d0ed716?auto=format&fit=crop&w=800&q=80', 9, 7.3787, 80.6865],
    ['Knuckles Mountain Range', 'A UNESCO World Heritage site known for its unique biodiversity and hiking trails.', 'Matale/Kandy', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=800&q=80', 10, 7.4250, 80.7833]
];

$insert = $pdo->prepare("INSERT INTO places (name, description, location, image_url, popularity_rank, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($places as $place) {
    $insert->execute($place);
}

setFlash('success', 'Database initialized successfully!');
header("Location: index.php");
exit;
?>
