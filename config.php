<?php
session_start();

$dotenvPath = __DIR__ . '/.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2) + ['', '']);
        if ($name === '') {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("$name=$value");
        }
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$sslCa = getenv('DB_SSL_CA');

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if (!empty($sslCa)) {
    $sslCaPath = $sslCa;
    if (!file_exists($sslCaPath)) {
        $sslCaPath = __DIR__ . '/' . ltrim($sslCa, "\/");
    }

    if (!file_exists($sslCaPath)) {
        die("Connection failed: SSL CA file not found at '$sslCa'. Please set DB_SSL_CA to a valid path.");
    }

    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Validate active session (Logout if user was deleted during DB reset)
if (isset($_SESSION['user_id'])) {
    $vStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $vStmt->execute([$_SESSION['user_id']]);
    if (!$vStmt->fetch()) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Function to set flash message
function setFlash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

// Function to print and clear flash messages
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $key => $message) {
            $alertClass = ($key === 'error') ? 'alert-error' : (($key === 'success') ? 'alert-success' : 'alert-info');
            echo "<div class='alert {$alertClass}'>{$message} <button class='close-alert' onclick='this.parentElement.style.display=\"none\";'>&times;</button></div>";
        }
        unset($_SESSION['flash']);
    }
}
?>
