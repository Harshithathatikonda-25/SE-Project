<?php
include 'config/database.php';
$result = $conn->query("SELECT user_id,name,email,role,created_at FROM users ORDER BY user_id DESC");
$userSummary = $conn->query("SELECT COUNT(*) AS total, SUM(role = 'Admin') AS admins, SUM(role = 'Security Guard') AS guards FROM users")->fetch_assoc();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>User & Role Directory</h2><p>Review administrator and security personnel accounts.</p></div><span class="badge yellow">Authentication not enabled</span></div>
<section class="stats-grid" aria-label="User summary"><div class="stat-card"><span>Total accounts</span><strong><?= (int) $userSummary['total'] ?></strong></div><div class="stat-card"><span>Administrators</span><strong><?= (int) $userSummary['admins'] ?></strong></div><div class="stat-card"><span>Security guards</span><strong><?= (int) $userSummary['guards'] ?></strong></div></section>
<section class="panel"><div class="module-panel-heading"><div><h3>Registered personnel</h3><p>Account directory only; sign-in and permission enforcement have not been configured.</p></div></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead><tbody>
<?php if ($result->num_rows === 0): ?><tr><td colspan="5" class="empty-state">No user accounts have been added yet.</td></tr><?php endif; ?>
<?php while($r=$result->fetch_assoc()): ?><tr><td><?= (int) $r['user_id'] ?></td><td><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge <?= $r['role'] === 'Admin' ? 'green' : 'gray' ?>"><?= htmlspecialchars($r['role'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endwhile; ?>
</tbody></table></div></section>
</main></body></html>
