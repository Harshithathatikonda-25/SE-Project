<?php
session_start();
include 'config/database.php';
if (empty($_SESSION['alerts_csrf'])) {
	$_SESSION['alerts_csrf'] = bin2hex(random_bytes(32));
}
$feedback = $_SESSION['alerts_feedback'] ?? '';
unset($_SESSION['alerts_feedback']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	$alertId = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);
	$nextStatus = $_POST['status'] ?? '';
	if (!hash_equals($_SESSION['alerts_csrf'], $token)) {
		$_SESSION['alerts_feedback'] = 'Request verification failed. Please try again.';
	} elseif (!$alertId || !in_array($nextStatus, ['Acknowledged', 'Resolved'], true)) {
		$_SESSION['alerts_feedback'] = 'Invalid alert operation.';
	} else {
		$update = $conn->prepare("UPDATE alerts SET status = ? WHERE alert_id = ? AND status <> 'Resolved'");
		$update->bind_param('si', $nextStatus, $alertId);
		$update->execute();
		$_SESSION['alerts_feedback'] = $update->affected_rows ? 'Alert updated to ' . $nextStatus . '.' : 'Alert is already resolved or no longer exists.';
		$update->close();
	}
	header('Location: alerts.php');
	exit;
}

$newCount = (int) $conn->query("SELECT COUNT(*) AS total FROM alerts WHERE status = 'New'")->fetch_assoc()['total'];
$criticalCount = (int) $conn->query("SELECT COUNT(*) AS total FROM alerts WHERE severity IN ('High', 'Critical') AND status <> 'Resolved'")->fetch_assoc()['total'];
$resolvedCount = (int) $conn->query("SELECT COUNT(*) AS total FROM alerts WHERE status = 'Resolved'")->fetch_assoc()['total'];
$result = $conn->query("SELECT * FROM alerts ORDER BY alert_id DESC LIMIT 100");
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alerts - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Alerts & Incidents</h2><p>Review security events, acknowledge incidents, and track resolution.</p></div><a class="btn-secondary" href="reports.php">View reports</a></div>
<?php if ($feedback !== ''): ?><div class="module-feedback" role="status"><?= $escape($feedback) ?></div><?php endif; ?>
<section class="stats-grid" aria-label="Alert summary"><div class="stat-card"><span>New alerts</span><strong><?= $newCount ?></strong></div><div class="stat-card alert-stat"><span>High / critical open</span><strong><?= $criticalCount ?></strong></div><div class="stat-card"><span>Resolved</span><strong><?= $resolvedCount ?></strong></div></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Incident queue</h3><p>Latest 100 alerts. Acknowledge new items and resolve them after review.</p></div></div><div class="table-wrap"><table><thead><tr><th>Vehicle</th><th>Alert</th><th>Description</th><th>Severity</th><th>Status</th><th>Time</th><th>Operation</th></tr></thead><tbody>
<?php if ($result->num_rows === 0): ?><tr><td colspan="7" class="empty-state">No incidents recorded. The alert queue is clear.</td></tr><?php endif; ?>
<?php while ($r = $result->fetch_assoc()): $severityClass = in_array($r['severity'], ['High', 'Critical'], true) ? 'red' : ($r['severity'] === 'Medium' ? 'yellow' : 'green'); ?><tr>
<td><?= $escape($r['vehicle_number'] ?: '-') ?></td><td><strong><?= $escape($r['alert_type']) ?></strong></td><td><?= $escape($r['description']) ?></td>
<td><span class="badge <?= $severityClass ?>"><?= $escape($r['severity']) ?></span></td><td><?= $escape($r['status']) ?></td><td><?= $escape($r['created_at']) ?></td><td>
<?php if ($r['status'] !== 'Resolved'): ?><form method="post" class="table-operation-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['alerts_csrf']) ?>"><input type="hidden" name="alert_id" value="<?= (int) $r['alert_id'] ?>"><button class="<?= $r['status'] === 'New' ? 'btn-secondary' : 'btn-primary' ?>" type="submit" name="status" value="<?= $r['status'] === 'New' ? 'Acknowledged' : 'Resolved' ?>"><?= $r['status'] === 'New' ? 'Acknowledge' : 'Resolve' ?></button></form><?php else: ?><span class="muted">Closed</span><?php endif; ?></td>
</tr><?php endwhile; ?></tbody></table></div></section>
</main></body></html>
