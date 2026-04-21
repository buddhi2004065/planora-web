<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', 'All fields are required.');
    } elseif ($password !== $confirm_password) {
        setFlash('error', 'Passwords do not match.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            setFlash('error', 'Email already exists.');
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($insert->execute([$name, $email, $hashed])) {
                setFlash('success', 'Registration successful! You can now log in.');
                header("Location: login.php");
                exit;
            } else {
                setFlash('error', 'Something went wrong. Please try again.');
            }
        }
    }
}

require_once 'includes/header.php';
?>
<div class="form-container">
    <h2 class="mb-4 text-center">Create an Account</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <p class="text-center mt-3">Already have an account? <a href="login.php">Log in</a></p>
</div>
<?php require_once 'includes/footer.php'; ?>
