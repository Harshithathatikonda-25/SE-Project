<?php
include 'config/database.php';

$totalVehicles = (int) $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
$insideVehicles = (int) $conn->query("SELECT COUNT(*) AS total FROM entry_exit WHERE status = 'INSIDE'")->fetch_assoc()['total'];
$exitedVehicles = (int) $conn->query("SELECT COUNT(*) AS total FROM entry_exit WHERE status = 'EXITED' AND DATE(exit_time) = CURDATE()")->fetch_assoc()['total'];
$totalAlerts = (int) $conn->query("SELECT COUNT(*) AS total FROM alerts WHERE status = 'New'")->fetch_assoc()['total'];
$recent = $conn->query("SELECT e.*, v.vehicle_type FROM entry_exit e LEFT JOIN vehicles v ON v.vehicle_number = e.vehicle_number ORDER BY e.record_id DESC LIMIT 5");
$alerts = $conn->query("SELECT vehicle_number, alert_type, severity, created_at FROM alerts ORDER BY alert_id DESC LIMIT 3");
$vehicleTypes = $conn->query("SELECT vehicle_type, COUNT(*) AS total FROM vehicles GROUP BY vehicle_type ORDER BY total DESC");
$hourlyResult = $conn->query("SELECT hour_bucket, SUM(entries) AS entries, SUM(exits) AS exits FROM (SELECT HOUR(entry_time) AS hour_bucket, COUNT(*) AS entries, 0 AS exits FROM entry_exit WHERE DATE(entry_time) = CURDATE() GROUP BY HOUR(entry_time) UNION ALL SELECT HOUR(exit_time) AS hour_bucket, 0 AS entries, COUNT(*) AS exits FROM entry_exit WHERE exit_time IS NOT NULL AND DATE(exit_time) = CURDATE() GROUP BY HOUR(exit_time)) AS movement_events GROUP BY hour_bucket");
$hourly = [];
while ($hour = $hourlyResult->fetch_assoc()) {
    $entries = (int) $hour['entries'];
    $exits = (int) $hour['exits'];
    $hourly[(int) $hour['hour_bucket']] = ['total' => $entries + $exits, 'entries' => $entries, 'exits' => $exits];
}
$maxHourly = max([1, ...array_column($hourly, 'total')]);
$chartHours = [8, 10, 12, 14, 16, 18];
$chartLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM'];
$today = date('D, d M Y');
$now = date('h:i:s A');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GateFlow-AI | Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/dashboard-theme.css">
<link rel="stylesheet" href="css/light-theme.css">
</head>
<body class="app-theme dashboard-shell">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content dashboard-page" id="overview">
    <header class="dashboard-topbar">
        <form class="dashboard-search" action="vehicles.php" method="get" role="search">
            <span aria-hidden="true">⌕</span><input type="search" name="search" placeholder="Search plate, owner, or vehicle type…" aria-label="Search vehicles">
        </form>
        <div class="topbar-tools">
            <span class="theme-pill" aria-label="Dark theme">☼ <span></span> ☾</span>
            <a class="notification-button" href="alerts.php" aria-label="View alerts">♟<i><?= $totalAlerts ?></i></a>
            <div class="user-menu"><span class="user-avatar">H</span><span><strong>Harshitha</strong><small>Admin</small></span><span class="user-chevron">⌄</span></div>
        </div>
    </header>

    <section class="welcome-hero" aria-label="Campus overview">
        <div class="welcome-copy">
            <p>Good <?= (int) date('G') < 12 ? 'Morning' : ((int) date('G') < 17 ? 'Afternoon' : 'Evening') ?>,</p>
            <h1>Harshitha <span aria-hidden="true">👋</span></h1>
            <p class="welcome-subtitle">GateFlow-AI keeps our campus safe with<br>AI-powered vehicle monitoring.</p>
            <div class="date-chip" data-live-clock data-timezone="<?= $escape(date_default_timezone_get()) ?>"><span class="calendar-icon">▦</span><span><small data-clock-date><?= $escape($today) ?></small><strong data-clock-time><?= $escape($now) ?></strong></span></div>
        </div>
    </section>

    <section class="dashboard-metrics" aria-label="Campus activity metrics">
        <article class="metric-card metric-vehicles"><span class="metric-icon">▰</span><div><span class="metric-label">Total Vehicles</span><strong><?= number_format($totalVehicles) ?></strong><small>Registered in system</small></div></article>
        <article class="metric-card metric-inside"><span class="metric-icon">⇥</span><div><span class="metric-label">Vehicles Inside</span><strong><?= number_format($insideVehicles) ?></strong><small>Currently on campus</small></div></article>
        <article class="metric-card metric-exited"><span class="metric-icon">⇤</span><div><span class="metric-label">Vehicles Exited Today</span><strong><?= number_format($exitedVehicles) ?></strong><small>Recorded exits</small></div></article>
        <article class="metric-card metric-alerts"><span class="metric-icon">♟</span><div><span class="metric-label">Active Alerts</span><strong><?= number_format($totalAlerts) ?></strong><small>Requires attention</small></div></article>
    </section>

    <section class="dashboard-middle">
        <article class="dashboard-card activity-card">
            <div class="card-heading"><h2>Recent Entry / Exit</h2><a href="entry_exit.php">View All <span>→</span></a></div>
            <div class="dashboard-table-wrap"><table class="dashboard-table">
                <thead><tr><th>#</th><th>Number Plate</th><th>Vehicle Type</th><th>Entry/Exit</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php $latestPlate = null; if ($recent->num_rows === 0): ?><tr><td colspan="6" class="empty-state">No vehicle activity recorded yet.</td></tr>
                <?php else: $rowNumber = 0; while ($row = $recent->fetch_assoc()): $rowNumber++; if ($rowNumber === 1) $latestPlate = $row['vehicle_number']; $isInside = $row['status'] === 'INSIDE'; ?>
                    <tr><td class="row-number"><?= sprintf('%02d', $rowNumber) ?></td><td class="plate-cell"><?= $escape($row['vehicle_number']) ?></td><td><span class="vehicle-type-icon">▰</span> <?= $escape($row['vehicle_type'] ?: 'Vehicle') ?></td><td><span class="movement-pill <?= $isInside ? 'movement-entry' : 'movement-exit' ?>"><?= $isInside ? '↪ Entry' : '↩ Exit' ?></span></td><td><?= $escape(date('h:i A', strtotime($row['exit_time'] ?: $row['entry_time'] ?: $row['created_at']))) ?></td><td><span class="state-pill <?= $isInside ? 'state-inside' : 'state-exited' ?>"><i></i><?= $isInside ? 'Inside' : 'Exited' ?></span></td></tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table></div>
        </article>

        <article class="dashboard-card camera-card">
            <div class="card-heading camera-card-heading"><div><h2>Campus Entrance</h2><small>Live Camera Feed</small></div><a href="camera.php">⛶ Full Screen</a></div>
            <div class="camera-preview" id="dashboard-camera-preview">
                <video id="dashboard-camera-video" autoplay muted playsinline hidden></video>
                <span class="campus-photo-badge">PHOTO</span>
                <span class="camera-feed-status" id="dashboard-camera-status"><i></i> LIVE</span>
                <span class="plate-detection"><?= $latestPlate ? $escape($latestPlate) : 'NO PLATE DETECTED' ?></span>
                <span class="scan-frame" aria-hidden="true"></span>
                <span class="camera-placeholder-icon" id="dashboard-camera-icon" aria-hidden="true" hidden>▣</span>
                <strong class="camera-placeholder-title" id="dashboard-camera-title" hidden>Camera not connected</strong>
                <span class="camera-placeholder-label" id="dashboard-camera-message" role="status">CAMERA PREVIEW · CONNECT A LIVE FEED</span>
                <button class="camera-connect-button" id="dashboard-camera-connect" type="button">Connect device camera</button>
            </div>
            <div class="camera-caption"><strong>▧ &nbsp;Gate Camera 01</strong><span id="dashboard-camera-caption"><?= $escape(date('h:i:s A')) ?></span></div>
        </article>
    </section>

    <section class="dashboard-bottom">
        <article class="dashboard-card distribution-card">
            <div class="card-heading"><h2>Vehicle Type Distribution</h2><a href="vehicles.php">View fleet →</a></div>
            <?php $typeRows = []; $typeTotal = 0; while ($type = $vehicleTypes->fetch_assoc()) { $typeRows[] = $type; $typeTotal += (int) $type['total']; } ?>
            <?php $donutStops = []; $donutColors = ['#3465f5', '#168df5', '#02cf9a', '#ffad24', '#f34f9a']; $offset = 0; foreach ($typeRows as $index => $type) { $share = $typeTotal ? (int) round((int) $type['total'] / $typeTotal * 100) : 0; $donutStops[] = $donutColors[$index % count($donutColors)] . ' ' . $offset . '% ' . ($offset + $share) . '%'; $offset += $share; } ?>
            <div class="distribution-content"><div class="donut-chart" style="--donut: <?= $escape($donutStops ? implode(', ', $donutStops) : '#273750 0% 100%') ?>"><span><?= number_format($typeTotal) ?><small>vehicles</small></span></div><ul class="legend-list">
                <?php foreach ($typeRows as $index => $type): $share = $typeTotal ? (int) round((int) $type['total'] / $typeTotal * 100) : 0; ?><li><i style="--legend-color:<?= $donutColors[$index % count($donutColors)] ?>"></i><span><?= $escape($type['vehicle_type'] ?: 'Other') ?></span><strong><?= $share ?>%</strong></li><?php endforeach; ?>
                <?php if (!$typeRows): ?><li class="no-types">No vehicle types registered</li><?php endif; ?>
            </ul></div>
        </article>

        <article class="dashboard-card chart-card">
            <div class="card-heading"><h2>Entry vs Exit (Today)</h2><div class="chart-legend"><span><i></i> Entry</span><span><i></i> Exit</span></div></div>
            <div class="bar-chart" role="img" aria-label="Today's vehicle movement by time of day">
                <div class="chart-y-labels"><span><?= $maxHourly ?></span><span><?= (int) ceil($maxHourly * .66) ?></span><span><?= (int) ceil($maxHourly * .33) ?></span><span>0</span></div>
                <div class="chart-plot"><div class="chart-gridlines"><i></i><i></i><i></i><i></i></div><div class="bar-groups">
                    <?php foreach ($chartHours as $index => $hour): $data = $hourly[$hour] ?? ['entries' => 0, 'exits' => 0]; $entryHeight = max(3, (int) round($data['entries'] / $maxHourly * 100)); $exitHeight = max(3, (int) round($data['exits'] / $maxHourly * 100)); ?>
                    <div class="bar-group"><div class="bars"><i class="bar-entry" style="height:<?= $entryHeight ?>%" title="<?= $data['entries'] ?> entries"></i><i class="bar-exit" style="height:<?= $exitHeight ?>%" title="<?= $data['exits'] ?> exits"></i></div><small><?= $chartLabels[$index] ?></small></div>
                    <?php endforeach; ?>
                </div></div>
            </div>
        </article>

        <article class="dashboard-card alerts-card">
            <div class="card-heading"><h2>Alerts</h2><a href="alerts.php">View All →</a></div>
            <div class="alert-list">
                <?php if ($alerts->num_rows === 0): ?><p class="no-alerts">No alerts. All clear.</p>
                <?php else: while ($alert = $alerts->fetch_assoc()): $severityClass = strtolower($alert['severity']); ?>
                    <a class="alert-row" href="alerts.php"><span class="alert-thumb">⚠</span><span class="alert-copy"><strong><?= $escape($alert['alert_type']) ?></strong><small><?= $escape($alert['vehicle_number'] ?: 'Campus') ?> · <?= $escape(date('h:i A', strtotime($alert['created_at']))) ?></small></span><span class="alert-tag alert-<?= $escape($severityClass) ?>"><?= $escape($alert['severity']) ?></span></a>
                <?php endwhile; endif; ?>
            </div>
        </article>
    </section>
</main>
<script>
(() => {
    const video = document.getElementById('dashboard-camera-video');
    const connectButton = document.getElementById('dashboard-camera-connect');
    const status = document.getElementById('dashboard-camera-status');
    const icon = document.getElementById('dashboard-camera-icon');
    const title = document.getElementById('dashboard-camera-title');
    const message = document.getElementById('dashboard-camera-message');
    const caption = document.getElementById('dashboard-camera-caption');
    let cameraStream = null;

    const stopCamera = () => {
        cameraStream?.getTracks().forEach(track => track.stop());
        cameraStream = null;
        if (!video) return;
        video.srcObject = null;
        video.hidden = true;
        status.classList.remove('camera-connected');
        status.innerHTML = '<i></i> NOT CONNECTED';
        icon.hidden = false;
        title.hidden = false;
        message.hidden = false;
        message.textContent = 'Allow camera access to preview this device’s camera';
        connectButton.textContent = 'Connect device camera';
        caption.textContent = 'Waiting for permission';
    };

    connectButton?.addEventListener('click', async () => {
        if (cameraStream) {
            stopCamera();
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            message.textContent = 'Camera preview needs a supported browser on localhost or HTTPS.';
            return;
        }

        connectButton.disabled = true;
        connectButton.textContent = 'Requesting permission…';
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = cameraStream;
            video.hidden = false;
            status.classList.add('camera-connected');
            status.innerHTML = '<i></i> LIVE PREVIEW';
            icon.hidden = true;
            title.hidden = true;
            message.hidden = true;
            connectButton.textContent = 'Stop camera';
            connectButton.disabled = false;
            caption.textContent = 'Device camera · preview only';
        } catch (error) {
            status.classList.remove('camera-connected');
            status.innerHTML = '<i></i> NOT CONNECTED';
            const messages = {
                NotAllowedError: 'Camera permission was denied. Allow access in the browser address bar and retry.',
                NotFoundError: 'No camera was found on this device.',
                NotReadableError: 'The camera is busy in another application.'
            };
            message.textContent = messages[error.name] || 'Could not open the camera. Check the device and browser permissions.';
            connectButton.textContent = 'Try again';
            connectButton.disabled = false;
        }
    });
    window.addEventListener('pagehide', stopCamera);

    const clock = document.querySelector('[data-live-clock]');
    if (!clock) return;
    const dateOutput = clock.querySelector('[data-clock-date]');
    const timeOutput = clock.querySelector('[data-clock-time]');
    const timezone = clock.dataset.timezone || undefined;
    const dateFormatter = new Intl.DateTimeFormat('en-IN', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric', timeZone: timezone });
    const timeFormatter = new Intl.DateTimeFormat('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: timezone });
    const updateClock = () => {
        const now = new Date();
        dateOutput.textContent = dateFormatter.format(now);
        timeOutput.textContent = timeFormatter.format(now);
    };
    updateClock();
    window.setInterval(updateClock, 1000);
})();
</script>
</body>
</html>
