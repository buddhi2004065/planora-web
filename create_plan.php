<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    setFlash('error', 'Please log in to create a plan.');
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    setFlash('error', 'Super Admins cannot create travel plans.');
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_name = trim($_POST['plan_name']);
    $transport_mode = $_POST['transport_mode'] ?? 'driving';
    
    if (empty($plan_name)) {
        setFlash('error', 'Plan name is required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO plans (user_id, plan_name, transport_mode) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $plan_name, $transport_mode])) {
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
    <h3 class="mb-4" style="text-align: center;">Name your journey</h3>
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Plan Name</label>
            <input type="text" name="plan_name" class="form-control" placeholder="e.g., Kandy One Day Tour" required>
        </div>
        <div class="form-group">
            <label class="form-label">Preferred Transport</label>
            <select name="transport_mode" class="form-control">
                <option value="driving">🚗 Personal Vehicle / Taxi</option>
                <option value="public">🚌 Public Transport (Bus/Train)</option>
                <option value="walking">🚶 Walking Tour</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Trip <i class="fa-solid fa-chevron-right"></i></button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
