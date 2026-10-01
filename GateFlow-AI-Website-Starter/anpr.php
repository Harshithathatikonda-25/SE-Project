<?php
session_start();
include 'config/database.php';

$detectedPlate = strtoupper(trim($_GET['plate'] ?? ''));
$vehicle = null;
$alreadyInside = false;
if ($detectedPlate !== '' && preg_match('/^[A-Z0-9 -]{2,20}$/', $detectedPlate)) {
	$vehicleQuery = $conn->prepare('SELECT vehicle_number, owner_name, vehicle_type, department, status FROM vehicles WHERE vehicle_number = ?');
	$vehicleQuery->bind_param('s', $detectedPlate);
	$vehicleQuery->execute();
	$vehicle = $vehicleQuery->get_result()->fetch_assoc();
	$vehicleQuery->close();
	$insideQuery = $conn->prepare("SELECT record_id FROM entry_exit WHERE vehicle_number = ? AND status = 'INSIDE' LIMIT 1");
	$insideQuery->bind_param('s', $detectedPlate);
	$insideQuery->execute();
	$alreadyInside = (bool) $insideQuery->get_result()->fetch_assoc();
	$insideQuery->close();
} elseif ($detectedPlate !== '') {
	$detectedPlate = '';
}

$vehicleStatus = $vehicle['status'] ?? 'Unregistered';
$isAuthorized = $vehicleStatus === 'Authorized';
$isRecognized = $detectedPlate !== '';
$feedback = $_SESSION['anpr_feedback'] ?? '';
unset($_SESSION['anpr_feedback']);

if (empty($_SESSION['anpr_csrf'])) {
	$_SESSION['anpr_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$decision = $_POST['decision'] ?? '';
	$csrfToken = $_POST['csrf_token'] ?? '';
	$submittedPlate = strtoupper(trim($_POST['vehicle_number'] ?? ''));
	$vehicle = null;
	if (preg_match('/^[A-Z0-9 -]{2,20}$/', $submittedPlate)) {
		$decisionVehicleQuery = $conn->prepare('SELECT status FROM vehicles WHERE vehicle_number = ?');
		$decisionVehicleQuery->bind_param('s', $submittedPlate);
		$decisionVehicleQuery->execute();
		$vehicle = $decisionVehicleQuery->get_result()->fetch_assoc();
		$decisionVehicleQuery->close();
	}
	$vehicleStatus = $vehicle['status'] ?? 'Unregistered';
	$isAuthorized = $vehicleStatus === 'Authorized';

	if (!hash_equals($_SESSION['anpr_csrf'], $csrfToken)) {
		$_SESSION['anpr_feedback'] = 'The request could not be verified. Please try again.';
	} elseif (!preg_match('/^[A-Z0-9 -]{2,20}$/', $submittedPlate)) {
		$_SESSION['anpr_feedback'] = 'Enter a valid number plate before recording a decision.';
	} elseif ($decision === 'allow') {
		if (!$vehicle || !$isAuthorized) {
			$_SESSION['anpr_feedback'] = 'Entry was not recorded because this vehicle is ' . strtolower($vehicleStatus) . '.';
		} else {
			$insideQuery = $conn->prepare("SELECT record_id FROM entry_exit WHERE vehicle_number = ? AND status = 'INSIDE' LIMIT 1");
			$insideQuery->bind_param('s', $submittedPlate);
			$insideQuery->execute();
			$alreadyInside = $insideQuery->get_result()->fetch_assoc();
			$insideQuery->close();

			if ($alreadyInside) {
				$_SESSION['anpr_feedback'] = 'This vehicle is already recorded as inside.';
			} else {
				$entryQuery = $conn->prepare("INSERT INTO entry_exit (vehicle_number, entry_time, status, access_type) VALUES (?, NOW(), 'INSIDE', 'ANPR')");
				$entryQuery->bind_param('s', $submittedPlate);
				$entryQuery->execute();
				$entryQuery->close();
				$_SESSION['anpr_feedback'] = 'Entry recorded. No physical gate command was sent.';
			}
		}
	} elseif ($decision === 'deny') {
		$alertType = 'Gate access denied';
		$description = 'Entry denied by operator through the ANPR console.';
		$severity = $vehicle && $isAuthorized ? 'Medium' : 'High';
		$alertQuery = $conn->prepare("INSERT INTO alerts (vehicle_number, alert_type, description, severity, status) VALUES (?, ?, ?, ?, 'New')");
		$alertQuery->bind_param('ssss', $submittedPlate, $alertType, $description, $severity);
		$alertQuery->execute();
		$alertQuery->close();
		$_SESSION['anpr_feedback'] = 'Deny decision logged as an alert. No physical gate command was sent.';
	} else {
		$_SESSION['anpr_feedback'] = 'Unknown access decision.';
	}

	header('Location: anpr.php?plate=' . rawurlencode($submittedPlate));
	exit;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ANPR - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>ANPR Vehicle Detection</h2><p>Check a plate against the vehicle registry and record access decisions.</p></div><span class="badge yellow">Manual lookup · AI not connected</span></div>
<div class="content-grid">
<div class="panel camera-placeholder"><div class="camera-screen"><div>CAMERA FEED</div><small>Connect Python/OpenCV ANPR stream here</small></div></div>
<div class="panel"><h3>Plate lookup</h3>
<form method="get" class="inline-operation-form"><label>Number plate<input name="plate" value="<?= htmlspecialchars($detectedPlate, ENT_QUOTES, 'UTF-8') ?>" placeholder="Example: TS09AB1234" required maxlength="20" pattern="[A-Za-z0-9 -]{2,20}"></label><button class="btn-primary" type="submit">Check vehicle</button></form>
<?php if ($isRecognized): ?>
<div class="result-box"><span>Number plate</span><strong><?= htmlspecialchars($detectedPlate, ENT_QUOTES, 'UTF-8') ?></strong><span>Registration</span><b class="badge <?= $isAuthorized ? 'green' : ($vehicleStatus === 'Blocked' ? 'red' : 'yellow') ?>"><?= htmlspecialchars(strtoupper($vehicleStatus), ENT_QUOTES, 'UTF-8') ?></b><?php if ($vehicle): ?><span>Registered owner · type</span><strong class="result-detail"><?= htmlspecialchars($vehicle['owner_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($vehicle['vehicle_type'] ?: 'Vehicle', ENT_QUOTES, 'UTF-8') ?></strong><?php endif; ?><span>Campus status</span><b class="badge <?= $alreadyInside ? 'green' : 'gray' ?>"><?= $alreadyInside ? 'ALREADY INSIDE' : 'NOT RECORDED INSIDE' ?></b></div>
<?php if ($feedback !== ''): ?><p role="status" style="margin:16px 0;padding:12px;border-radius:10px;background:#e8f5ee;color:#126a38"><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<form method="post" class="quick-actions">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['anpr_csrf'], ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="vehicle_number" value="<?= htmlspecialchars($detectedPlate, ENT_QUOTES, 'UTF-8') ?>">
<button class="btn-primary" type="submit" name="decision" value="allow" <?= $isAuthorized && !$alreadyInside ? '' : 'disabled' ?>>Allow Entry</button>
<button class="btn-secondary" type="submit" name="decision" value="deny">Deny Entry</button>
</form>
<?php else: ?><p class="muted">Search any number plate to check whether it is registered, authorized, blocked, or already on campus.</p><?php endif; ?>
<p>Registry decisions are saved to MySQL. Automatic plate recognition and physical gate control need separate integrations.</p>
</div></div>
</main></body></html>
