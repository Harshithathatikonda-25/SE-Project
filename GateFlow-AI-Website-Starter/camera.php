<?php
session_start();
include 'config/database.php';
$cameraNames = ['Gate Camera 01', 'Gate Camera 02', 'Parking Camera'];
if (empty($_SESSION['camera_csrf'])) {
	$_SESSION['camera_csrf'] = bin2hex(random_bytes(32));
}
$feedback = $_SESSION['camera_feedback'] ?? '';
unset($_SESSION['camera_feedback']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$camera = $_POST['camera_name'] ?? '';
	$eventType = trim($_POST['event_type'] ?? '');
	$description = trim($_POST['description'] ?? '');
	if (!hash_equals($_SESSION['camera_csrf'], $_POST['csrf_token'] ?? '')) {
		$_SESSION['camera_feedback'] = 'Request verification failed. Please try again.';
	} elseif (!in_array($camera, $cameraNames, true) || $eventType === '' || strlen($eventType) > 100 || strlen($description) > 2000) {
		$_SESSION['camera_feedback'] = 'Choose a camera and enter a valid event type and description.';
	} else {
		$insert = $conn->prepare('INSERT INTO camera_events (camera_name, event_type, description) VALUES (?, ?, ?)');
		$insert->bind_param('sss', $camera, $eventType, $description);
		$insert->execute();
		$insert->close();
		$_SESSION['camera_feedback'] = 'Camera event logged for ' . $camera . '.';
	}
	header('Location: camera.php');
	exit;
}

$eventsToday = (int) $conn->query("SELECT COUNT(*) AS total FROM camera_events WHERE DATE(event_time) = CURDATE()")->fetch_assoc()['total'];
$recentEvents = $conn->query('SELECT * FROM camera_events ORDER BY event_id DESC LIMIT 10');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CCTV Monitoring - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>CCTV Monitoring</h2><p>Camera previews and recorded events. Connect a camera stream to enable live video.</p></div><span class="system-online">Preview mode</span></div>
<?php if ($feedback !== ''): ?><div class="module-feedback" role="status"><?= $escape($feedback) ?></div><?php endif; ?>
<section class="stats-grid" aria-label="Camera summary"><div class="stat-card"><span>Configured cameras</span><strong><?= count($cameraNames) ?></strong></div><div class="stat-card alert-stat"><span>Live feeds connected</span><strong>0</strong></div><div class="stat-card"><span>Events logged today</span><strong><?= $eventsToday ?></strong></div></section>
<div class="camera-grid">
<?php foreach ($cameraNames as $index => $camera): ?><article class="panel camera-module-card"><div class="module-panel-heading"><div><h3><?= $escape($camera) ?></h3><p><?= $index === 2 ? 'Parking area preview' : 'Campus gate preview' ?></p></div><span class="badge yellow">OFFLINE</span></div><div class="camera-screen"><strong>Camera Offline</strong><small>Connect the camera stream to view live footage</small></div></article><?php endforeach; ?>
</div>
<section class="panel"><div class="module-panel-heading"><div><h3>Log camera event</h3><p>Record a manually reviewed camera event while live analytics are offline.</p></div></div><form method="post" class="inline-operation-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['camera_csrf']) ?>"><label>Camera<select name="camera_name" required><?php foreach ($cameraNames as $camera): ?><option><?= $escape($camera) ?></option><?php endforeach; ?></select></label><label>Event type<input name="event_type" required maxlength="100" placeholder="Vehicle detected"></label><label>Description<input name="description" maxlength="2000" placeholder="Add an operator note"></label><button class="btn-primary" type="submit">Log event</button></form></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Recent camera events</h3><p>Latest 10 manually or automatically logged events.</p></div></div><div class="table-wrap"><table><thead><tr><th>Camera</th><th>Event</th><th>Description</th><th>Time</th></tr></thead><tbody><?php if ($recentEvents->num_rows === 0): ?><tr><td colspan="4" class="empty-state">No events recorded yet.</td></tr><?php endif; ?><?php while ($event = $recentEvents->fetch_assoc()): ?><tr><td><?= $escape($event['camera_name']) ?></td><td><?= $escape($event['event_type']) ?></td><td><?= $escape($event['description']) ?></td><td><?= $escape($event['event_time']) ?></td></tr><?php endwhile; ?></tbody></table></div></section>
</main></body></html>
