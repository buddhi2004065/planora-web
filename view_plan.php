<?php
require_once 'config.php';
$is_logged_in = isset($_SESSION['user_id']);

if (!$is_logged_in) {
    header("Location: login.php");
    exit;
}

$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? AND user_id = ?");
$stmt->execute([$plan_id, $_SESSION['user_id']]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    setFlash('error', 'Plan not found or access denied.');
    header("Location: dashboard.php");
    exit;
}

// Haversine distance function
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos(max(-1, min(1, $dist)));
    $dist = rad2deg($dist);
    return $dist * 60 * 1.1515 * 1.609344;
}

// Update transport mode
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transport'])) {
    $new_mode = $_POST['transport_mode'];
    $uStmt = $pdo->prepare("UPDATE plans SET transport_mode = ? WHERE id = ? AND user_id = ?");
    $uStmt->execute([$new_mode, $plan_id, $_SESSION['user_id']]);
    $plan['transport_mode'] = $new_mode;
    setFlash('success', 'Transport mode updated!');
}

// Fetch plan items
$itemStmt = $pdo->prepare("
    SELECT pi.*, p.name, p.location, p.image_url, p.latitude, p.longitude, p.description
    FROM plan_items pi
    JOIN places p ON pi.place_id = p.id
    WHERE pi.plan_id = ?
    ORDER BY pi.day_number ASC, pi.id ASC
");
$itemStmt->execute([$plan_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalDistance = 0;
for ($i = 0; $i < count($items) - 1; $i++) {
    $totalDistance += calculateDistance(
        $items[$i]['latitude'], $items[$i]['longitude'],
        $items[$i+1]['latitude'], $items[$i+1]['longitude']
    );
}

$speed = 30;
if ($plan['transport_mode'] == 'walking') $speed = 4;
if ($plan['transport_mode'] == 'public')  $speed = 20;
$totalTimeHours   = $totalDistance / max(1, $speed);
$totalTimeMinutes = round($totalTimeHours * 60) + (count($items) * 60);

$maxDay = 0;
foreach ($items as $item) $maxDay = max($maxDay, $item['day_number']);

require_once 'includes/header.php';
?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css">
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Design Tokens ─────────────────────────────────────────── */
:root {
  --brand:        #e8304a;
  --brand-light:  #fff0f3;
  --brand-dark:   #b81e33;
  --brand-glow:   rgba(232,48,74,0.15);
  --ink:          #140d0f;
  --ink-2:        #5c4a50;
  --ink-3:        #9e8e93;
  --surface:      #fffbfc;
  --surface-2:    #f9f0f2;
  --surface-3:    #f0e4e8;
  --white:        #ffffff;
  --border:       rgba(20,13,15,0.09);
  --border-2:     rgba(20,13,15,0.16);
  --success:      #059669;
  --success-bg:   #ecfdf5;
  --warning:      #d97706;
  --warning-bg:   #fffbeb;
  --info:         #2563eb;
  --info-bg:      #eff6ff;
  --radius-sm:    8px;
  --radius-md:    14px;
  --radius-lg:    20px;
  --radius-xl:    28px;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body:    'DM Sans', system-ui, sans-serif;
  --shadow-sm:    0 1px 4px rgba(20,13,15,0.06);
  --shadow-md:    0 4px 20px rgba(20,13,15,0.08), 0 1px 4px rgba(20,13,15,0.04);
  --shadow-lg:    0 12px 48px rgba(20,13,15,0.10), 0 4px 12px rgba(20,13,15,0.05);
  --shadow-brand: 0 8px 32px rgba(232,48,74,0.22);
  --t:            all 0.22s cubic-bezier(0.22,1,0.36,1);
}

*, *::before, *::after { box-sizing: border-box; }

/* ── Page wrapper ──────────────────────────────────────────── */
.vp-page {
  font-family: var(--font-body);
  background: var(--surface);
  color: var(--ink);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

/* ── Top bar ───────────────────────────────────────────────── */
.vp-topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 68px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.vp-topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.vp-back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--ink-2);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  padding: 7px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--white);
  transition: var(--t);
}
.vp-back-btn:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }

.vp-topbar-divider { width: 1px; height: 24px; background: var(--border); }

.vp-plan-name {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: -0.02em;
}

.vp-topbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.vp-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 100px;
}
.vp-badge-brand  { background: var(--brand-light); color: var(--brand); }
.vp-badge-blue   { background: var(--info-bg);     color: var(--info); }
.vp-badge-green  { background: var(--success-bg);  color: var(--success); }

/* ── Layout ────────────────────────────────────────────────── */
.vp-body {
  display: grid;
  grid-template-columns: 380px 1fr;
  min-height: calc(100vh - 68px);
}

/* ── Sidebar ───────────────────────────────────────────────── */
.vp-sidebar {
  background: var(--white);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.vp-sidebar-head {
  padding: 24px 28px 20px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.vp-transport-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: var(--ink-3);
  margin-bottom: 10px;
}

.vp-transport-form {
  display: flex;
  gap: 8px;
}

.vp-transport-select {
  flex: 1;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 500;
  color: var(--ink);
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 8px 12px;
  appearance: none;
  cursor: pointer;
  transition: var(--t);
}
.vp-transport-select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); }

.vp-btn-update {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  background: var(--brand);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 8px 16px;
  cursor: pointer;
  transition: var(--t);
  white-space: nowrap;
}
.vp-btn-update:hover { background: var(--brand-dark); }

/* ── Stats row ─────────────────────────────────────────────── */
.vp-stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.vp-stat {
  padding: 16px 20px;
  border-right: 1px solid var(--border);
  text-align: center;
}
.vp-stat:last-child { border-right: none; }

.vp-stat-val {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1;
  margin-bottom: 3px;
}
.vp-stat-val em { font-style: normal; color: var(--brand); font-size: 14px; }

.vp-stat-key {
  font-size: 10px;
  font-weight: 500;
  color: var(--ink-3);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

/* ── Day tabs ──────────────────────────────────────────────── */
.vp-day-tabs {
  display: flex;
  gap: 4px;
  padding: 14px 20px 0;
  flex-shrink: 0;
  overflow-x: auto;
  scrollbar-width: none;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0;
}
.vp-day-tabs::-webkit-scrollbar { display: none; }

.vp-day-tab {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-3);
  background: none;
  border: none;
  padding: 8px 14px 12px;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: var(--t);
  white-space: nowrap;
  flex-shrink: 0;
}
.vp-day-tab:hover { color: var(--ink); }
.vp-day-tab.active { color: var(--brand); border-bottom-color: var(--brand); }

/* ── Timeline ──────────────────────────────────────────────── */
.vp-timeline {
  flex: 1;
  overflow-y: auto;
  padding: 20px 20px 32px;
  scrollbar-width: thin;
  scrollbar-color: var(--border) transparent;
}

.vp-day-group { display: none; }
.vp-day-group.active { display: block; }

.vp-day-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.vp-day-dot {
  width: 32px; height: 32px;
  background: var(--brand);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.vp-day-title {
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
  color: var(--ink);
}
.vp-day-count {
  font-size: 11px;
  color: var(--ink-3);
  font-weight: 500;
}

/* ── Place card ────────────────────────────────────────────── */
.vp-place-item {
  display: flex;
  gap: 0;
  position: relative;
  padding-left: 20px;
  margin-bottom: 4px;
}

.vp-place-item::before {
  content: '';
  position: absolute;
  left: 7px; top: 36px; bottom: -4px;
  width: 2px;
  background: linear-gradient(to bottom, var(--brand-light), transparent);
}
.vp-place-item:last-child::before { display: none; }

.vp-place-num {
  position: absolute;
  left: 0; top: 16px;
  width: 16px; height: 16px;
  background: var(--brand);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  font-size: 8px;
  font-weight: 800;
  flex-shrink: 0;
  z-index: 1;
}

.vp-place-card {
  flex: 1;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  margin-bottom: 12px;
  transition: var(--t);
}
.vp-place-card:hover {
  border-color: rgba(232,48,74,0.25);
  box-shadow: var(--shadow-md);
}

.vp-place-card-top {
  display: flex;
  gap: 0;
}

.vp-place-img {
  width: 90px;
  flex-shrink: 0;
  object-fit: cover;
  display: block;
  background: var(--surface-2);
}

.vp-place-info {
  flex: 1;
  padding: 12px 14px;
  min-width: 0;
}

.vp-place-loc {
  font-size: 10px;
  font-weight: 600;
  color: var(--brand);
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 3px;
}

.vp-place-name {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.25;
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vp-place-note {
  font-size: 11px;
  color: var(--ink-2);
  line-height: 1.5;
  background: var(--surface-2);
  border-left: 2px solid var(--brand);
  padding: 5px 8px;
  border-radius: 0 4px 4px 0;
  margin-bottom: 4px;
}

.vp-place-actions {
  display: flex;
  gap: 4px;
  padding: 8px 12px;
  border-top: 1px solid var(--border);
  background: var(--surface);
  justify-content: flex-end;
}

.vp-btn-sm {
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--ink-2);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: var(--t);
  text-decoration: none;
}
.vp-btn-sm:hover          { border-color: var(--brand); color: var(--brand); }
.vp-btn-sm.danger:hover   { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

/* ── Edit inline ───────────────────────────────────────────── */
.vp-edit-panel {
  display: none;
  padding: 12px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
  animation: slideDown 0.2s ease;
}
@keyframes slideDown { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform: translateY(0); } }

.vp-edit-panel.open { display: block; }

.vp-edit-row { display: grid; grid-template-columns: 80px 1fr; gap: 8px; margin-bottom: 8px; }
.vp-edit-label { font-size: 10px; font-weight: 600; color: var(--ink-3); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px; }
.vp-edit-input {
  font-family: var(--font-body);
  font-size: 12px;
  color: var(--ink);
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 6px 10px;
  width: 100%;
  transition: var(--t);
}
.vp-edit-input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); }

.vp-edit-footer { display: flex; gap: 6px; justify-content: flex-end; }
.vp-btn-save {
  font-family: var(--font-body);
  font-size: 11px; font-weight: 600;
  background: var(--brand); color: #fff;
  border: none; border-radius: 6px;
  padding: 6px 14px; cursor: pointer;
  transition: var(--t);
}
.vp-btn-save:hover { background: var(--brand-dark); }
.vp-btn-cancel {
  font-family: var(--font-body);
  font-size: 11px; font-weight: 500;
  background: var(--white); color: var(--ink-2);
  border: 1px solid var(--border); border-radius: 6px;
  padding: 6px 12px; cursor: pointer;
  transition: var(--t);
}
.vp-btn-cancel:hover { border-color: var(--ink-2); }

/* ── Sidebar Add btn ───────────────────────────────────────── */
.vp-sidebar-add {
  padding: 16px 20px;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}
.vp-btn-add {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  background: var(--brand-light);
  color: var(--brand);
  border: 1.5px dashed rgba(232,48,74,0.35);
  border-radius: var(--radius-md);
  padding: 11px;
  cursor: pointer;
  text-decoration: none;
  transition: var(--t);
}
.vp-btn-add:hover { background: #ffe0e5; border-color: var(--brand); }

/* ── Map panel ─────────────────────────────────────────────── */
.vp-map-panel {
  display: flex;
  flex-direction: column;
  background: var(--surface-2);
}

.vp-map-topbar {
  padding: 16px 28px;
  background: var(--white);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.vp-map-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-2);
  display: flex;
  align-items: center;
  gap: 6px;
}

.vp-map-title i { color: var(--brand); }

.vp-route-pills {
  display: flex;
  gap: 6px;
}

#map {
  flex: 1;
  min-height: 420px;
}

/* ── Legend ────────────────────────────────────────────────── */
.vp-legend {
  padding: 16px 24px;
  background: var(--white);
  border-top: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}
.vp-legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--ink-2);
  font-weight: 500;
}
.vp-legend-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Empty state ───────────────────────────────────────────── */
.vp-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 32px;
  text-align: center;
}
.vp-empty-icon {
  width: 64px; height: 64px;
  background: var(--surface-2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
  color: var(--ink-3);
  margin: 0 auto 16px;
}
.vp-empty h3 {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 8px;
}
.vp-empty p {
  font-size: 14px;
  color: var(--ink-2);
  line-height: 1.6;
  max-width: 280px;
  margin-bottom: 24px;
}
.vp-btn-browse {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: var(--font-body); font-size: 14px; font-weight: 600;
  background: var(--brand); color: #fff;
  padding: 12px 24px; border-radius: var(--radius-md);
  text-decoration: none; transition: var(--t);
}
.vp-btn-browse:hover { background: var(--brand-dark); box-shadow: var(--shadow-brand); transform: translateY(-1px); }

/* ── Leaflet overrides ─────────────────────────────────────── */
.leaflet-routing-container { display: none !important; }
.leaflet-control-zoom { border-radius: var(--radius-sm) !important; border: 1px solid var(--border) !important; box-shadow: var(--shadow-sm) !important; }
.leaflet-control-zoom a { color: var(--ink) !important; }

/* ── Flash ─────────────────────────────────────────────────── */
.vp-flash {
  position: fixed; bottom: 24px; right: 24px; z-index: 999;
  padding: 12px 20px;
  border-radius: var(--radius-md);
  font-size: 13px; font-weight: 500;
  box-shadow: var(--shadow-lg);
  display: flex; align-items: center; gap: 8px;
  animation: slideUp 0.3s ease, fadeAway 0.4s ease 2.8s forwards;
}
.vp-flash-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(5,150,105,0.2); }
@keyframes slideUp   { from { transform: translateY(16px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes fadeAway  { to { opacity: 0; transform: translateY(-6px); pointer-events: none; } }

/* ── Responsive ────────────────────────────────────────────── */
@media (max-width: 900px) {
  .vp-body { grid-template-columns: 1fr; }
  .vp-map-panel { min-height: 360px; }
  #map { min-height: 300px; }
}
@media (max-width: 600px) {
  .vp-topbar { padding: 0 16px; height: 56px; }
  .vp-plan-name { font-size: 16px; }
  .vp-topbar-divider, .vp-topbar-right .vp-badge { display: none; }
}
</style>

<?php
// Collect flash
$flash = null;
if (function_exists('getFlash')) $flash = getFlash();
?>

<div class="vp-page">

  <?php if ($flash): ?>
  <div class="vp-flash vp-flash-success">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash) ?>
  </div>
  <?php endif; ?>

  <!-- Top bar -->
  <div class="vp-topbar">
    <div class="vp-topbar-left">
      <a href="dashboard.php" class="vp-back-btn">
        <i class="fa-solid fa-arrow-left"></i> Dashboard
      </a>
      <div class="vp-topbar-divider"></div>
      <div class="vp-plan-name"><?= htmlspecialchars($plan['plan_name']) ?></div>
    </div>
    <div class="vp-topbar-right">
      <span class="vp-badge vp-badge-brand" id="top-dist">
        <i class="fa-solid fa-road"></i>
        <span id="top-dist-val"><?= round($totalDistance, 1) ?></span> km
      </span>
      <span class="vp-badge vp-badge-blue" id="top-time">
        <i class="fa-solid fa-clock"></i>
        <span id="top-time-val">~<?= floor($totalTimeMinutes/60) ?>h <?= $totalTimeMinutes%60 ?>m</span>
      </span>
      <span class="vp-badge vp-badge-green">
        <i class="fa-solid fa-map-pin"></i>
        <?= count($items) ?> stop<?= count($items) != 1 ? 's' : '' ?>
      </span>
    </div>
  </div>

  <!-- Body: sidebar + map -->
  <div class="vp-body">

    <!-- ── SIDEBAR ── -->
    <div class="vp-sidebar">

      <!-- Transport -->
      <div class="vp-sidebar-head">
        <div class="vp-transport-label">Transport Mode</div>
        <form method="POST" class="vp-transport-form">
          <select name="transport_mode" class="vp-transport-select">
            <option value="driving" <?= $plan['transport_mode']=='driving' ? 'selected':'' ?>>🚗 Driving</option>
            <option value="public"  <?= $plan['transport_mode']=='public'  ? 'selected':'' ?>>🚌 Public Transport</option>
            <option value="walking" <?= $plan['transport_mode']=='walking' ? 'selected':'' ?>>🚶 Walking</option>
          </select>
          <button type="submit" name="update_transport" class="vp-btn-update">Update</button>
        </form>
      </div>

      <!-- Stats -->
      <div class="vp-stats-row">
        <div class="vp-stat">
          <div class="vp-stat-val"><span id="sb-dist"><?= round($totalDistance,1) ?></span><em> km</em></div>
          <div class="vp-stat-key">Distance</div>
        </div>
        <div class="vp-stat">
          <div class="vp-stat-val"><?= floor($totalTimeMinutes/60) ?><em>h <?= $totalTimeMinutes%60 ?>m</em></div>
          <div class="vp-stat-key">Est. Time</div>
        </div>
        <div class="vp-stat">
          <div class="vp-stat-val"><?= $maxDay ?><em> day<?= $maxDay!=1?'s':'' ?></em></div>
          <div class="vp-stat-key">Days</div>
        </div>
      </div>

      <?php if (!empty($items)): ?>

      <!-- Day Tabs -->
      <div class="vp-day-tabs" id="dayTabs">
        <button class="vp-day-tab active" data-day="all" onclick="switchDay('all', this)">All Days</button>
        <?php for ($d = 1; $d <= $maxDay; $d++): ?>
          <button class="vp-day-tab" data-day="<?= $d ?>" onclick="switchDay(<?= $d ?>, this)">Day <?= $d ?></button>
        <?php endfor; ?>
      </div>

      <!-- Timeline -->
      <div class="vp-timeline">
        <?php
        // Group items by day
        $byDay = [];
        foreach ($items as $item) {
            $byDay[$item['day_number']][] = $item;
        }
        ?>

        <!-- All days group -->
        <div class="vp-day-group active" id="group-all">
          <?php foreach ($byDay as $day => $dayItems): ?>
          <div class="vp-day-header">
            <div class="vp-day-dot"><?= $day ?></div>
            <div class="vp-day-title">Day <?= $day ?></div>
            <div class="vp-day-count"><?= count($dayItems) ?> stop<?= count($dayItems)!=1?'s':'' ?></div>
          </div>
          <?php $n = 0; foreach ($dayItems as $item): $n++; ?>
          <div class="vp-place-item">
            <div class="vp-place-num"><?= $n ?></div>
            <div class="vp-place-card" id="card-<?= $item['id'] ?>">
              <div class="vp-place-card-top">
                <img class="vp-place-img"
                     src="<?= htmlspecialchars($item['image_url']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="vp-place-info">
                  <div class="vp-place-loc"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?></div>
                  <div class="vp-place-name"><?= htmlspecialchars($item['name']) ?></div>
                  <?php if (!empty($item['notes'])): ?>
                  <div class="vp-place-note"><i class="fa-solid fa-note-sticky"></i> <?= htmlspecialchars(substr($item['notes'],0,80)) ?><?= strlen($item['notes'])>80?'…':'' ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="vp-place-actions">
                <button class="vp-btn-sm" onclick="toggleEdit('edit-<?= $item['id'] ?>', this)">
                  <i class="fa-solid fa-pen"></i> Edit
                </button>
                <a href="remove_plan_item.php?id=<?= $item['id'] ?>" class="vp-btn-sm danger"
                   onclick="return confirm('Remove this place from your plan?')">
                  <i class="fa-solid fa-xmark"></i> Remove
                </a>
              </div>
              <!-- Inline edit -->
              <div class="vp-edit-panel" id="edit-<?= $item['id'] ?>">
                <form action="update_plan_item.php" method="POST">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                  <div class="vp-edit-row">
                    <div>
                      <div class="vp-edit-label">Day</div>
                      <input type="number" name="day_number" value="<?= $item['day_number'] ?>"
                             class="vp-edit-input" min="1" required style="width:64px;">
                    </div>
                    <div>
                      <div class="vp-edit-label">Notes / Activities</div>
                      <textarea name="notes" class="vp-edit-input" rows="2"
                                placeholder="e.g., Guide tour at 10 AM"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <div class="vp-edit-footer">
                    <button type="button" class="vp-btn-cancel" onclick="toggleEdit('edit-<?= $item['id'] ?>')">Cancel</button>
                    <button type="submit" class="vp-btn-save"><i class="fa-solid fa-check"></i> Save</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <!-- Per-day groups -->
        <?php for ($d = 1; $d <= $maxDay; $d++): if (empty($byDay[$d])) continue; ?>
        <div class="vp-day-group" id="group-<?= $d ?>">
          <div class="vp-day-header">
            <div class="vp-day-dot"><?= $d ?></div>
            <div class="vp-day-title">Day <?= $d ?></div>
            <div class="vp-day-count"><?= count($byDay[$d]) ?> stop<?= count($byDay[$d])!=1?'s':'' ?></div>
          </div>
          <?php $n=0; foreach ($byDay[$d] as $item): $n++; ?>
          <div class="vp-place-item">
            <div class="vp-place-num"><?= $n ?></div>
            <div class="vp-place-card">
              <div class="vp-place-card-top">
                <img class="vp-place-img"
                     src="<?= htmlspecialchars($item['image_url']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="vp-place-info">
                  <div class="vp-place-loc"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?></div>
                  <div class="vp-place-name"><?= htmlspecialchars($item['name']) ?></div>
                  <?php if (!empty($item['notes'])): ?>
                  <div class="vp-place-note"><?= htmlspecialchars(substr($item['notes'],0,80)) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="vp-place-actions">
                <button class="vp-btn-sm" onclick="toggleEdit('edit-d<?= $d ?>-<?= $item['id'] ?>', this)">
                  <i class="fa-solid fa-pen"></i> Edit
                </button>
                <a href="remove_plan_item.php?id=<?= $item['id'] ?>" class="vp-btn-sm danger"
                   onclick="return confirm('Remove this place from your plan?')">
                  <i class="fa-solid fa-xmark"></i> Remove
                </a>
              </div>
              <div class="vp-edit-panel" id="edit-d<?= $d ?>-<?= $item['id'] ?>">
                <form action="update_plan_item.php" method="POST">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                  <div class="vp-edit-row">
                    <div>
                      <div class="vp-edit-label">Day</div>
                      <input type="number" name="day_number" value="<?= $item['day_number'] ?>"
                             class="vp-edit-input" min="1" required style="width:64px;">
                    </div>
                    <div>
                      <div class="vp-edit-label">Notes / Activities</div>
                      <textarea name="notes" class="vp-edit-input" rows="2"
                                placeholder="e.g., Guide tour at 10 AM"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <div class="vp-edit-footer">
                    <button type="button" class="vp-btn-cancel" onclick="toggleEdit('edit-d<?= $d ?>-<?= $item['id'] ?>')">Cancel</button>
                    <button type="submit" class="vp-btn-save"><i class="fa-solid fa-check"></i> Save</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endfor; ?>
      </div>

      <?php else: ?>
      <div class="vp-empty" style="flex:1;">
        <div class="vp-empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        <h3>Empty Plan</h3>
        <p>Add destinations to start building your itinerary.</p>
        <a href="places.php" class="vp-btn-browse"><i class="fa-solid fa-plus"></i> Browse Destinations</a>
      </div>
      <?php endif; ?>

      <!-- Add destination -->
      <div class="vp-sidebar-add">
        <a href="places.php" class="vp-btn-add">
          <i class="fa-solid fa-plus"></i> Add Destination
        </a>
      </div>
    </div>

    <!-- ── MAP PANEL ── -->
    <div class="vp-map-panel">
      <div class="vp-map-topbar">
        <div class="vp-map-title">
          <i class="fa-solid fa-map"></i>
          Route Map — <?= htmlspecialchars($plan['plan_name']) ?>
        </div>
        <div class="vp-route-pills">
          <span class="vp-badge vp-badge-brand" style="font-size:11px;">
            <?= ucfirst($plan['transport_mode']) ?>
          </span>
        </div>
      </div>

      <div id="map"></div>

      <div class="vp-legend">
        <div class="vp-legend-item"><div class="vp-legend-dot" style="background:#10b981;"></div> Start</div>
        <div class="vp-legend-item"><div class="vp-legend-dot" style="background:#e8304a;"></div> Stop</div>
        <div class="vp-legend-item"><div class="vp-legend-dot" style="background:#7c3aed;"></div> End</div>
        <div class="vp-legend-item" style="margin-left:auto; color:var(--ink-3); font-size:11px;">
          <i class="fa-solid fa-circle-info"></i> Data © OpenStreetMap contributors
        </div>
      </div>
    </div>

  </div><!-- .vp-body -->
</div><!-- .vp-page -->

<script>
/* ── Day tab switching ─────────────────────────────────────── */
function switchDay(day, btn) {
  document.querySelectorAll('.vp-day-group').forEach(g => g.classList.remove('active'));
  document.querySelectorAll('.vp-day-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('group-' + day).classList.add('active');
  btn.classList.add('active');
}

/* ── Edit panel toggle ─────────────────────────────────────── */
function toggleEdit(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('open');
}

/* ── Map ───────────────────────────────────────────────────── */
var map = L.map('map', { zoomControl: true });

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '',
    maxZoom: 18
}).addTo(map);

var waypoints = [];
var names     = <?= json_encode(array_column($items, 'name')) ?>;

<?php foreach ($items as $idx => $item): if ($item['latitude']): ?>
waypoints.push(L.latLng(<?= (float)$item['latitude'] ?>, <?= (float)$item['longitude'] ?>));
<?php endif; endforeach; ?>

function makeIcon(label, color) {
  return L.divIcon({
    className: '',
    html: `<div style="background:${color};color:#fff;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;border:2px solid rgba(255,255,255,0.9);box-shadow:0 3px 10px rgba(0,0,0,0.25);font-family:'DM Sans',sans-serif;">${label}</div>`,
    iconSize: [null, 30],
    iconAnchor: [0, 15]
  });
}

if (waypoints.length > 1) {
  var profile = '<?= $plan['transport_mode'] == 'walking' ? 'foot' : 'car' ?>';
  var ctrl = L.Routing.control({
    waypoints: waypoints,
    router: L.Routing.osrmv1({
      serviceUrl: 'https://router.project-osrm.org/route/v1',
      profile: profile
    }),
    lineOptions: {
      styles: [{ color: '#e8304a', opacity: 0.85, weight: 5 }],
      addWaypoints: false
    },
    createMarker: function(i, wp, n) {
      var color = '#e8304a';
      var label = '● ' + (i + 1);
      if (i === 0)     { color = '#10b981'; label = '▶ Start'; }
      if (i === n - 1) { color = '#7c3aed'; label = '⚑ End'; }
      return L.marker(wp.latLng, { icon: makeIcon(label, color) })
              .bindPopup('<strong>' + (i+1) + '. ' + (names[i] || 'Stop') + '</strong>',
                         { maxWidth: 200 });
    },
    routeWhileDragging: false,
    addWaypoints: false,
    show: false
  }).addTo(map);

  ctrl.on('routesfound', function(e) {
    var summary  = e.routes[0].summary;
    var distKm   = (summary.totalDistance / 1000).toFixed(1);
    var stay     = waypoints.length * 60;
    var totalMin = Math.round(summary.totalTime / 60) + stay;
    var hh = Math.floor(totalMin / 60), mm = totalMin % 60;

    ['top-dist-val','sb-dist'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.textContent = distKm;
    });
    var tv = document.getElementById('top-time-val');
    if (tv) tv.textContent = '~' + hh + 'h ' + mm + 'm';

    map.fitBounds(L.latLngBounds(waypoints), { padding: [40, 40] });
  });

} else if (waypoints.length === 1) {
  L.marker(waypoints[0], { icon: makeIcon('▶ Start', '#10b981') })
   .addTo(map)
   .bindPopup('<strong>' + (names[0] || 'Stop') + '</strong>').openPopup();
  map.setView(waypoints[0], 15);
} else {
  map.setView([7.2906, 80.6337], 13);
}

/* ── Auto-dismiss flash ────────────────────────────────────── */
setTimeout(function() {
  var f = document.querySelector('.vp-flash');
  if (f) f.remove();
}, 3400);
</script>

<?php require_once 'includes/footer.php'; ?>