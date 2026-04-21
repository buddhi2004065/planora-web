<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    setFlash('error', 'Please log in to create a plan.');
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_name = trim($_POST['plan_name']);
    
    if (empty($plan_name)) {
        setFlash('error', 'Plan name is required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO plans (user_id, plan_name) VALUES (?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $plan_name])) {
            $plan_id = $pdo->lastInsertId();
            setFlash('success', 'Plan created successfully! Add some places to your plan.');
            header("Location: view_plan.php?id=" . $plan_id);
            exit;
        } else {
            setFlash('error', 'Failed to create plan.');
        }
    }
}

require_once 'includes/header.php';
?>
<div class="dashboard-header">
    <h2>Create New Plan</h2>
    <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Plan Name</label>
            <input type="text" name="plan_name" class="form-control" placeholder="e.g., Summer Trip to Europe" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Plan</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
