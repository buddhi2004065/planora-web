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
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 60 * 1.1515 * 1.609344; // Kilometers
}

// Update transport mode if requested
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transport'])) {
    $new_mode = $_POST['transport_mode'];
    $uStmt = $pdo->prepare("UPDATE plans SET transport_mode = ? WHERE id = ? AND user_id = ?");
    $uStmt->execute([$new_mode, $plan_id, $_SESSION['user_id']]);
    $plan['transport_mode'] = $new_mode;
    setFlash('success', 'Transport mode updated!');
}

// Fetch plan items joined with places (including lat/lng)
$itemStmt = $pdo->prepare("
    SELECT pi.*, p.name, p.location, p.image_url, p.latitude, p.longitude
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
    $totalDistance += calculateDistance($items[$i]['latitude'], $items[$i]['longitude'], $items[$i+1]['latitude'], $items[$i+1]['longitude']);
}

// Time estimate based on transport
$speed = 30; // Driving default
if ($plan['transport_mode'] == 'walking') $speed = 4;
if ($plan['transport_mode'] == 'public') $speed = 20;
$totalTimeHours = $totalDistance / $speed;
$totalTimeMinutes = round($totalTimeHours * 60) + (count($items) * 60); // +1 hour spent at each place

require_once 'includes/header.php';
?>

<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<!-- Leaflet Routing Machine CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<div class="dashboard-header flex-column md-flex-row gap-4" style="align-items: flex-start;">
    <div>
        <h2 class="form-title" style="text-align: left; margin-bottom: 0.5rem;"><?= htmlspecialchars($plan['plan_name']) ?></h2>
        <div class="flex gap-4 flex-wrap">
            <span class="badge" style="background:var(--primary-light); color:var(--primary-color); padding:0.5rem 1rem; border-radius:30px; font-weight:700;">
                <i class="fa-solid fa-road"></i> <span id="route-distance"><?= round($totalDistance, 2) ?></span> km
            </span>
            <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:0.5rem 1rem; border-radius:30px; font-weight:700;">
                <i class="fa-solid fa-clock"></i> <span id="route-time">~<?= floor($totalTimeMinutes/60) ?>h <?= $totalTimeMinutes%60 ?>m</span>
            </span>
        </div>
    </div>
    
    <div class="flex flex-column gap-2" style="min-width: 200px;">
        <form method="POST" class="flex gap-2 items-center">
            <select name="transport_mode" class="form-control" style="padding: 0.5rem;">
                <option value="driving" <?= $plan['transport_mode'] == 'driving' ? 'selected' : '' ?>>🚗 Driving</option>
                <option value="public" <?= $plan['transport_mode'] == 'public' ? 'selected' : '' ?>>🚌 Public Transport</option>
                <option value="walking" <?= $plan['transport_mode'] == 'walking' ? 'selected' : '' ?>>🚶 Walking</option>
            </select>
            <button type="submit" name="update_transport" class="btn btn-primary btn-sm">Update</button>
        </form>
        <div class="flex gap-2">
            <a href="places.php" class="btn btn-outline btn-sm btn-block"><i class="fa-solid fa-plus"></i> Add</a>
            <a href="dashboard.php" class="btn btn-secondary btn-sm btn-block">Back</a>
        </div>
    </div>
</div>

<div id="map" style="height: 500px; width: 100%; border-radius: var(--border-radius-lg); margin-bottom: 3rem; border: 4px solid white; box-shadow: var(--shadow);"></div>

<script>
    var map = L.map('map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var waypoints = [];
    <?php foreach($items as $index => $item): if($item['latitude']): ?>
        waypoints.push(L.latLng(<?= $item['latitude'] ?>, <?= $item['longitude'] ?>));
    <?php endif; endforeach; ?>

    if (waypoints.length > 1) {
        var control = L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                profile: '<?= $plan['transport_mode'] == 'walking' ? 'foot' : 'car' ?>'
            }),
            lineOptions: {
                styles: [{color: 'var(--primary-color)', opacity: 0.8, weight: 6}]
            },
            createMarker: function(i, wp, n) {
                var iconType = 'fa-location-dot';
                var iconColor = 'var(--primary-color)';
                var label = (i + 1);
                
                if (i === 0) {
                    iconType = 'fa-flag-checkered';
                    iconColor = '#10b981'; // Green for start
                    label = 'START';
                } else if (i === n - 1) {
                    iconType = 'fa-flag';
                    iconColor = '#ff2d75'; // Pink for end
                    label = 'END';
                }
                
                var icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color:${iconColor}; color:white; padding:5px 10px; border-radius:30px; font-size:12px; font-weight:800; white-space:nowrap; border:2px solid white; box-shadow:0 2px 5px rgba(0,0,0,0.2);">
                              <i class="fa-solid ${iconType}"></i> ${label}
                           </div>`,
                    iconSize: [80, 42],
                    iconAnchor: [40, 21]
                });
                
                return L.marker(wp.latLng, {icon: icon}).bindPopup("<b>" + (i + 1) + ". " + 
                    (<?php echo json_encode(array_column($items, 'name')); ?>[i] || 'Point') + "</b>");
            },
            routeWhileDragging: false,
            addWaypoints: false,
            show: false
        }).addTo(map);

        control.on('routesfound', function(e) {
            var routes = e.routes;
            var summary = routes[0].summary;
            var distKm = (summary.totalDistance / 1000).toFixed(2);
            var timeSec = summary.totalTime;
            
            // Adjust time for walking/public/stay time
            var stayTime = <?= count($items) ?> * 60; // 1 hour per place
            var totalMin = Math.round(timeSec / 60) + stayTime;
            
            document.getElementById('route-distance').innerText = distKm;
            document.getElementById('route-time').innerText = "~" + Math.floor(totalMin/60) + "h " + (totalMin%60) + "m";
            
            map.fitBounds(L.latLngBounds(waypoints));
        });
    } else if (waypoints.length === 1) {
        L.marker(waypoints[0]).addTo(map).bindPopup("<b>1. <?= htmlspecialchars($items[0]['name'] ?? 'Point') ?></b>").openPopup();
        map.setView(waypoints[0], 15);
    } else {
        map.setView([7.2906, 80.6337], 13);
    }
</script>

<?php if(empty($items)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clipboard-list"></i>
        <h3>This plan is empty</h3>
        <p>Go to destinations and add places to start building your itinerary.</p>
        <a href="places.php" class="btn btn-primary mt-3">Browse Destinations</a>
    </div>
<?php else: ?>
    <div class="timeline">
        <?php 
        $currentDay = -1;
        foreach($items as $item): 
            if ($item['day_number'] != $currentDay) {
                $currentDay = $item['day_number'];
                echo "<div class='timeline-item' style='padding-top: 0; padding-bottom: 0;'>";
                echo "<div class='timeline-day'>Day {$currentDay}</div>";
                echo "</div>";
            }
        ?>
        <div class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="timeline-details">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="m-0"><?= htmlspecialchars($item['name']) ?></h3>
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('edit-<?= $item['id'] ?>').style.display='block'" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></button>
                            <a href="remove_plan_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this place from your plan?')"><i class="fa-solid fa-xmark"></i></a>
                        </div>
                    </div>
                    <p class="text-muted"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?></p>
                    <?php if(!empty($item['notes'])): ?>
                        <div class="mt-3 p-3 bg-main" style="background:#f9fafb; border-radius: 6px; border-left: 3px solid var(--primary-color);">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($item['notes'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Edit Form (Hidden by default) -->
                    <div id="edit-<?= $item['id'] ?>" style="display:none; margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                        <form action="update_plan_item.php" method="POST">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="form-group mb-0">
                                    <label class="form-label text-sm">Day Number</label>
                                    <input type="number" name="day_number" value="<?= $item['day_number'] ?>" class="form-control" min="1" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label text-sm">Notes / Activities</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g., Guide tour at 10 AM"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="mt-2" style="text-align: right;">
                                <button type="button" onclick="document.getElementById('edit-<?= $item['id'] ?>').style.display='none'" class="btn btn-sm btn-secondary">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
