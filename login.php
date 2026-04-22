<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        header("Location: dashboard.php");
        exit;
    } else {
        setFlash('error', 'Invalid email or password.');
    }
}

require_once 'includes/header.php';
?>
<div class="form-container">
    <h2 class="form-title">Welcome Back</h2>
    <p class="text-center text-muted mb-4">Plan your next adventure by logging in.</p>
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="Email@example.com" required>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log In <i class="fa-solid fa-right-to-bracket"></i></button>
    </form>
    <p class="text-center mt-4">Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 700;">Sign up</a></p>
</div>
<?php require_once 'includes/footer.php'; ?>
