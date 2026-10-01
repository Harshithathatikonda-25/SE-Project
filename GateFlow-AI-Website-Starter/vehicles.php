<?php
include 'config/database.php';
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
	$searchPattern = '%' . $search . '%';
	$vehicleQuery = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_number LIKE ? OR owner_name LIKE ? OR vehicle_type LIKE ? ORDER BY vehicle_id DESC");
	$vehicleQuery->bind_param('sss', $searchPattern, $searchPattern, $searchPattern);
	$vehicleQuery->execute();
	$result = $vehicleQuery->get_result();
} else {
	$result = $conn->query("SELECT * FROM vehicles ORDER BY vehicle_id DESC");
}
$vehicleSummary = $conn->query("SELECT COUNT(*) AS total, SUM(status = 'Authorized') AS authorized, SUM(status = 'Pending') AS pending, SUM(status = 'Blocked') AS blocked FROM vehicles")->fetch_assoc();
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vehicles - GateFlow-AI</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/dashboard-theme.css">
<link rel="stylesheet" href="css/light-theme.css">
</head><body class="app-theme">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="topbar"><div><h2>Vehicle Management</h2><p>Registered and authorized campus vehicles</p></div><a class="btn-primary" href="add_vehicle.php">+ Add Vehicle</a></div>
<?php if (($_GET['updated'] ?? '') === '1'): ?><div class="module-feedback" role="status">Vehicle details updated successfully.</div><?php endif; ?>
<section class="stats-grid" aria-label="Vehicle status summary">
<div class="stat-card"><span>Registered vehicles</span><strong><?= (int) $vehicleSummary['total'] ?></strong></div>
<div class="stat-card"><span>Authorized</span><strong><?= (int) $vehicleSummary['authorized'] ?></strong></div>
<div class="stat-card"><span>Pending review</span><strong><?= (int) $vehicleSummary['pending'] ?></strong></div>
<div class="stat-card alert-stat"><span>Blocked</span><strong><?= (int) $vehicleSummary['blocked'] ?></strong></div>
</section>
<div class="panel">
<?php if ($search !== ''): ?><p>Search results for <strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong> · <a href="vehicles.php">Clear search</a></p><?php endif; ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Vehicle Number</th><th>Owner</th><th>Type</th><th>Department</th><th>Status</th><th>Operation</th></tr></thead>
<tbody>
<?php if ($result->num_rows === 0): ?><tr><td colspan="7" class="empty-state">No vehicles found.</td></tr><?php endif; ?>
<?php while($r=$result->fetch_assoc()): ?>
<tr>
<td><?= $r['vehicle_id'] ?></td>
<td><strong><?= htmlspecialchars($r['vehicle_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
<td><?= htmlspecialchars($r['owner_name'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($r['vehicle_type'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($r['department'], ENT_QUOTES, 'UTF-8') ?></td>
<td><span class="badge <?= $r['status']==='Authorized'?'green':($r['status']==='Blocked'?'red':'yellow') ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td><a class="table-action-link" href="edit_vehicle.php?id=<?= (int) $r['vehicle_id'] ?>">Edit details / status</a></td>
</tr>
<?php endwhile; ?>
</tbody></table></div>
</div>
</main></body></html>
