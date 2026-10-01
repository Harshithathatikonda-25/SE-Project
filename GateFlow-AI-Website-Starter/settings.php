<?php
include 'config/database.php';
$databaseName = $database ?? 'gateflow_ai';
$databaseReady = $conn->query('SELECT 1') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Settings - GateFlow-AI</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/dashboard-theme.css">
<link rel="stylesheet" href="css/light-theme.css">
</head>
<body class="app-theme">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
    <div class="topbar">
        <div><h2>System Settings</h2><p>Review service connections and integration readiness.</p></div>
        <span class="system-online">● Configuration loaded</span>
    </div>
    <section class="stats-grid" aria-label="System status">
        <div class="stat-card"><span>Database</span><strong><?= $databaseReady ? 'Online' : 'Offline' ?></strong></div>
        <div class="stat-card"><span>Camera integration</span><strong>Not connected</strong></div>
        <div class="stat-card alert-stat"><span>Physical gate controller</span><strong>Not connected</strong></div>
    </section>
    <section class="panel">
        <div class="module-panel-heading"><div><h3>Current configuration</h3><p>Connection secrets are intentionally not shown on this page.</p></div></div>
        <div class="settings-list">
            <div><span>Application</span><strong>GateFlow-AI campus vehicle security</strong></div>
            <div><span>Database name</span><strong><?= htmlspecialchars($databaseName, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div><span>Database connection</span><strong><span class="badge <?= $databaseReady ? 'green' : 'red' ?>"><?= $databaseReady ? 'Connected' : 'Unavailable' ?></span></strong></div>
            <div><span>License plate recognition</span><strong>Interface ready · inference service not connected</strong></div>
            <div><span>Camera feeds</span><strong>Preview mode · RTSP / OpenCV integration required</strong></div>
            <div><span>Access control</span><strong>Demo decisions are logged · no physical gate commands</strong></div>
            <div><span>User authentication</span><strong>Not enabled</strong></div>
        </div>
    </section>
    <section class="panel">
        <div class="module-panel-heading"><div><h3>Integration links</h3><p>Open a module to review its data and current capabilities.</p></div></div>
        <div class="quick-actions">
            <a href="anpr.php">Open ANPR</a><a href="camera.php">Camera monitoring</a><a href="crowd_monitoring.php">Crowd monitoring</a><a href="users.php">User directory</a>
        </div>
    </section>
</main>
</body>
</html>
