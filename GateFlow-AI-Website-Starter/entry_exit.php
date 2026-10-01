<?php
session_start();
include 'config/database.php';

if (empty($_SESSION['movement_csrf'])) {
	$_SESSION['movement_csrf'] = bin2hex(random_bytes(32));
}
$feedback = $_SESSION['movement_feedback'] ?? '';
unset($_SESSION['movement_feedback']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['movement_csrf'], $token)) {
		$_SESSION['movement_feedback'] = 'Request verification failed. Please try again.';
	} elseif (($_POST['action'] ?? '') === 'manual_entry') {
		$plate = strtoupper(trim($_POST['vehicle_number'] ?? ''));
		$vehicleQuery = $conn->prepare("SELECT status FROM vehicles WHERE vehicle_number = ? LIMIT 1");
		$vehicleQuery->bind_param('s', $plate);
		$vehicleQuery->execute();
		$vehicle = $vehicleQuery->get_result()->fetch_assoc();
		$vehicleQuery->close();

		if (!$vehicle || $vehicle['status'] !== 'Authorized') {
			$_SESSION['movement_feedback'] = 'Only registered, authorized vehicles can be entered.';
		} else {
			$insideQuery = $conn->prepare("SELECT record_id FROM entry_exit WHERE vehicle_number = ? AND status = 'INSIDE' LIMIT 1");
			$insideQuery->bind_param('s', $plate);
			$insideQuery->execute();
			$alreadyInside = $insideQuery->get_result()->fetch_assoc();
			$insideQuery->close();
			if ($alreadyInside) {
				$_SESSION['movement_feedback'] = 'This vehicle is already recorded as inside.';
			} else {
				$insertQuery = $conn->prepare("INSERT INTO entry_exit (vehicle_number, entry_time, status, access_type) VALUES (?, NOW(), 'INSIDE', 'Manual')");
				$insertQuery->bind_param('s', $plate);
				$insertQuery->execute();
				$insertQuery->close();
				$_SESSION['movement_feedback'] = 'Manual entry recorded for ' . $plate . '.';
			}
		}
	} elseif (($_POST['action'] ?? '') === 'mark_exit') {
		$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
		if (!$recordId) {
			$_SESSION['movement_feedback'] = 'Invalid movement record.';
		} else {
			$exitQuery = $conn->prepare("UPDATE entry_exit SET exit_time = NOW(), status = 'EXITED' WHERE record_id = ? AND status = 'INSIDE'");
			$exitQuery->bind_param('i', $recordId);
			$exitQuery->execute();
			$_SESSION['movement_feedback'] = $exitQuery->affected_rows ? 'Vehicle exit recorded.' : 'This record was already closed or no longer exists.';
			$exitQuery->close();
		}
	}
	header('Location: entry_exit.php');
	exit;
}

$insideCount = (int) $conn->query("SELECT COUNT(*) AS total FROM entry_exit WHERE status = 'INSIDE'")->fetch_assoc()['total'];
$entriesToday = (int) $conn->query("SELECT COUNT(*) AS total FROM entry_exit WHERE DATE(entry_time) = CURDATE()")->fetch_assoc()['total'];
$exitsToday = (int) $conn->query("SELECT COUNT(*) AS total FROM entry_exit WHERE DATE(exit_time) = CURDATE()")->fetch_assoc()['total'];
$authorizedVehicles = $conn->query("SELECT vehicle_number FROM vehicles WHERE status = 'Authorized' ORDER BY vehicle_number");
$result = $conn->query("SELECT * FROM entry_exit ORDER BY record_id DESC LIMIT 100");
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entry / Exit - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Entry / Exit Management</h2><p>Track live occupancy and complete vehicle movements.</p></div><a class="btn-primary" href="anpr.php">Open ANPR</a></div>
<?php if ($feedback !== ''): ?><div class="module-feedback" role="status"><?= $escape($feedback) ?></div><?php endif; ?>
<section class="stats-grid" aria-label="Movement summary"><div class="stat-card"><span>Currently inside</span><strong><?= $insideCount ?></strong></div><div class="stat-card"><span>Entries today</span><strong><?= $entriesToday ?></strong></div><div class="stat-card"><span>Exits today</span><strong><?= $exitsToday ?></strong></div></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Record a manual entry</h3><p>Only authorized vehicles are available for selection.</p></div></div>
<form method="post" class="inline-operation-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['movement_csrf']) ?>"><input type="hidden" name="action" value="manual_entry"><label for="vehicle-number">Authorized vehicle</label><select id="vehicle-number" name="vehicle_number" required><option value="">Select a vehicle</option><?php while ($vehicle = $authorizedVehicles->fetch_assoc()): ?><option value="<?= $escape($vehicle['vehicle_number']) ?>"><?= $escape($vehicle['vehicle_number']) ?></option><?php endwhile; ?></select><button class="btn-primary" type="submit">Record entry</button></form>
</section>
<section class="panel"><div class="module-panel-heading"><div><h3>Movement history</h3><p>Latest 100 entry and exit records. Open movements can be closed below.</p></div></div><div class="table-wrap"><table>
<thead><tr><th>Vehicle</th><th>Entry Time</th><th>Exit Time</th><th>Access</th><th>Status</th><th>Operation</th></tr></thead>
<tbody><?php if ($result->num_rows === 0): ?><tr><td colspan="6" class="empty-state">No movement has been recorded yet.</td></tr><?php endif; ?>
<?php while ($r = $result->fetch_assoc()): ?><tr>
<td><strong><?= $escape($r['vehicle_number']) ?></strong></td><td><?= $escape($r['entry_time'] ?: '-') ?></td><td><?= $escape($r['exit_time'] ?: '-') ?></td><td><?= $escape($r['access_type']) ?></td>
<td><span class="badge <?= $r['status'] === 'INSIDE' ? 'green' : 'gray' ?>"><?= $escape($r['status']) ?></span></td><td><?php if ($r['status'] === 'INSIDE'): ?><form method="post" class="table-operation-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['movement_csrf']) ?>"><input type="hidden" name="action" value="mark_exit"><input type="hidden" name="record_id" value="<?= (int) $r['record_id'] ?>"><button class="btn-secondary" type="submit">Mark exit</button></form><?php else: ?><span class="muted">Complete</span><?php endif; ?></td>
</tr><?php endwhile; ?></tbody></table></div></section>
</main></body></html>
