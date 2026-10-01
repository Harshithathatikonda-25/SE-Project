<?php
include 'config/database.php';
$exportQueries = [
	'vehicles' => ['filename' => 'gateflow-vehicles.csv', 'sql' => 'SELECT vehicle_number, owner_name, vehicle_type, department, phone, status, created_at FROM vehicles ORDER BY vehicle_id DESC'],
	'movements' => ['filename' => 'gateflow-movements.csv', 'sql' => 'SELECT vehicle_number, entry_time, exit_time, access_type, status, created_at FROM entry_exit ORDER BY record_id DESC'],
	'alerts' => ['filename' => 'gateflow-alerts.csv', 'sql' => 'SELECT vehicle_number, alert_type, description, severity, status, created_at FROM alerts ORDER BY alert_id DESC'],
	'crowd' => ['filename' => 'gateflow-crowd-observations.csv', 'sql' => 'SELECT location, crowd_count, density_level, recorded_at FROM crowd_monitoring ORDER BY monitoring_id DESC'],
];
if (isset($_GET['export']) && is_string($_GET['export']) && isset($exportQueries[$_GET['export']])) {
	$export = $exportQueries[$_GET['export']];
	$exportResult = $conn->query($export['sql']);
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
	$output = fopen('php://output', 'wb');
	fputcsv($output, array_map(static fn($field) => $field->name, $exportResult->fetch_fields()));
	while ($row = $exportResult->fetch_assoc()) {
		fputcsv($output, array_values($row));
	}
	fclose($output);
	exit;
}
$vehicles = (int) $conn->query("SELECT COUNT(*) total FROM vehicles")->fetch_assoc()['total'];
$records = (int) $conn->query("SELECT COUNT(*) total FROM entry_exit")->fetch_assoc()['total'];
$alerts = (int) $conn->query("SELECT COUNT(*) total FROM alerts WHERE status <> 'Resolved'")->fetch_assoc()['total'];
$todayMovements = (int) $conn->query("SELECT COUNT(*) total FROM entry_exit WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];
$recent = $conn->query("SELECT vehicle_number, entry_time, exit_time, status, access_type FROM entry_exit ORDER BY record_id DESC LIMIT 8");
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Reports & Exports</h2><p>Operational summary with downloadable CSV data for each module.</p></div><span class="system-online">● Live database</span></div>
<section class="stats-grid">
<div class="stat-card"><span>Registered Vehicles</span><strong><?= $vehicles ?></strong></div>
<div class="stat-card"><span>Movement records</span><strong><?= $records ?></strong></div>
<div class="stat-card"><span>Movements today</span><strong><?= $todayMovements ?></strong></div>
<div class="stat-card alert-stat"><span>Open alerts</span><strong><?= $alerts ?></strong></div>
</section>
<section class="panel"><div class="module-panel-heading"><div><h3>Download reports</h3><p>Export complete records as CSV for spreadsheets and audits.</p></div></div><div class="quick-actions report-downloads">
<a href="reports.php?export=vehicles">↓ Vehicle register CSV</a><a href="reports.php?export=movements">↓ Entry / exit CSV</a><a href="reports.php?export=alerts">↓ Alerts CSV</a><a href="reports.php?export=crowd">↓ Crowd data CSV</a>
</div></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Recent movement summary</h3><p>Latest 8 movement records with direct links to the full module.</p></div><a href="entry_exit.php">Open movement log →</a></div><div class="table-wrap"><table><thead><tr><th>Vehicle</th><th>Entry</th><th>Exit</th><th>Access</th><th>Status</th></tr></thead><tbody>
<?php if ($recent->num_rows === 0): ?><tr><td colspan="5" class="empty-state">No movement data is available yet.</td></tr><?php endif; ?>
<?php while ($row = $recent->fetch_assoc()): ?><tr><td><?= htmlspecialchars($row['vehicle_number'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['entry_time'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['exit_time'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['access_type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endwhile; ?>
</tbody></table></div></section>
</main></body></html>
