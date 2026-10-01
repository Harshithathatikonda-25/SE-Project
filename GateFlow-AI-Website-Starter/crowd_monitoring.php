<?php
session_start();
include 'config/database.php';
if (empty($_SESSION['crowd_csrf'])) {
	$_SESSION['crowd_csrf'] = bin2hex(random_bytes(32));
}
$feedback = $_SESSION['crowd_feedback'] ?? '';
unset($_SESSION['crowd_feedback']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$location = trim($_POST['location'] ?? '');
	$count = filter_input(INPUT_POST, 'crowd_count', FILTER_VALIDATE_INT);
	$density = $_POST['density_level'] ?? '';
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['crowd_csrf'], $token)) {
		$_SESSION['crowd_feedback'] = 'Request verification failed. Please try again.';
	} elseif ($location === '' || strlen($location) > 100 || $count === false || $count === null || $count < 0 || !in_array($density, ['Low', 'Medium', 'High', 'Critical'], true)) {
		$_SESSION['crowd_feedback'] = 'Enter a location, a non-negative count, and a valid density level.';
	} else {
		$insert = $conn->prepare('INSERT INTO crowd_monitoring (location, crowd_count, density_level) VALUES (?, ?, ?)');
		$insert->bind_param('sis', $location, $count, $density);
		$insert->execute();
		$insert->close();
		$_SESSION['crowd_feedback'] = 'Crowd observation saved for ' . $location . '.';
	}
	header('Location: crowd_monitoring.php');
	exit;
}

$latest = $conn->query("SELECT location, crowd_count, density_level, recorded_at FROM crowd_monitoring ORDER BY monitoring_id DESC LIMIT 1")->fetch_assoc();
$highDensity = (int) $conn->query("SELECT COUNT(*) AS total FROM crowd_monitoring WHERE density_level IN ('High', 'Critical') AND recorded_at >= NOW() - INTERVAL 24 HOUR")->fetch_assoc()['total'];
$locationsTracked = (int) $conn->query("SELECT COUNT(DISTINCT location) AS total FROM crowd_monitoring")->fetch_assoc()['total'];
$result = $conn->query("SELECT * FROM crowd_monitoring ORDER BY monitoring_id DESC LIMIT 50");
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Crowd Monitoring - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Crowd & Traffic Monitoring</h2><p>Review recent location counts and density observations.</p></div><span class="system-online">● Monitoring module</span></div>
<?php if ($feedback !== ''): ?><div class="module-feedback" role="status"><?= $escape($feedback) ?></div><?php endif; ?>
<section class="stats-grid" aria-label="Crowd summary"><div class="stat-card"><span>Latest crowd count</span><strong><?= (int) ($latest['crowd_count'] ?? 0) ?></strong></div><div class="stat-card alert-stat"><span>High density observations · 24h</span><strong><?= $highDensity ?></strong></div><div class="stat-card"><span>Locations tracked</span><strong><?= $locationsTracked ?></strong></div></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Record crowd observation</h3><p>Manual entry until an automated camera analytics feed is connected.</p></div></div><form method="post" class="inline-operation-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['crowd_csrf']) ?>"><label>Location<input name="location" required maxlength="100" placeholder="Main gate"></label><label>People count<input name="crowd_count" type="number" min="0" required value="0"></label><label>Density<select name="density_level" required><option>Low</option><option>Medium</option><option>High</option><option>Critical</option></select></label><button class="btn-primary" type="submit">Save observation</button></form></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Recent observations</h3><p>Latest 50 recorded crowd and traffic observations.</p></div></div><div class="table-wrap"><table><thead><tr><th>Location</th><th>Count</th><th>Density</th><th>Recorded At</th></tr></thead><tbody>
<?php if ($result->num_rows === 0): ?><tr><td colspan="4" class="empty-state">No crowd data yet. Save an observation or connect a camera analytics feed.</td></tr><?php endif; ?>
<?php while ($r = $result->fetch_assoc()): $densityClass = in_array($r['density_level'], ['High', 'Critical'], true) ? 'red' : ($r['density_level'] === 'Medium' ? 'yellow' : 'green'); ?><tr><td><?= $escape($r['location']) ?></td><td><?= (int) $r['crowd_count'] ?></td><td><span class="badge <?= $densityClass ?>"><?= $escape($r['density_level']) ?></span></td><td><?= $escape($r['recorded_at']) ?></td></tr><?php endwhile; ?>
</tbody></table></div></section>
</main></body></html>
