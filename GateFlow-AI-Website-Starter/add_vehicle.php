<?php
session_start();
include 'config/database.php';
$message = '';
$values = ['vehicle_number' => '', 'owner_name' => '', 'vehicle_type' => 'Car', 'department' => '', 'phone' => '', 'status' => 'Authorized'];
if (empty($_SESSION['add_vehicle_csrf'])) {
    $_SESSION['add_vehicle_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim((string) ($_POST[$key] ?? $default));
    }
    $values['vehicle_number'] = strtoupper($values['vehicle_number']);
    if (!hash_equals($_SESSION['add_vehicle_csrf'], $_POST['csrf_token'] ?? '')) {
        $message = 'Request verification failed. Refresh the page and try again.';
    } elseif ($values['vehicle_number'] === '' || $values['owner_name'] === '' || !in_array($values['vehicle_type'], ['Car', 'Bike', 'Bus', 'Other'], true) || !in_array($values['status'], ['Authorized', 'Pending', 'Blocked'], true)) {
        $message = 'Enter a vehicle number and owner, then select a valid type and status.';
    } else {
        $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, owner_name, vehicle_type, department, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $values['vehicle_number'], $values['owner_name'], $values['vehicle_type'], $values['department'], $values['phone'], $values['status']);
        try {
            $stmt->execute();
            $stmt->close();
            header('Location: vehicles.php');
            exit;
        } catch (mysqli_sql_exception $exception) {
            $message = 'Could not add vehicle. The vehicle number may already exist.';
        }
    }
}
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Vehicle - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Add Vehicle</h2><p>Register a vehicle in the GateFlow-AI database</p></div></div>
<div class="panel form-panel">
<?php if($message): ?><div class="error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="post" class="form-grid">
<input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['add_vehicle_csrf']) ?>">
<label>Vehicle Number<input name="vehicle_number" required maxlength="20" placeholder="TS09AB1234" value="<?= $escape($values['vehicle_number']) ?>"></label>
<label>Owner Name<input name="owner_name" required maxlength="100" value="<?= $escape($values['owner_name']) ?>"></label>
<label>Vehicle Type<select name="vehicle_type"><?php foreach (['Car', 'Bike', 'Bus', 'Other'] as $type): ?><option <?= $values['vehicle_type'] === $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach; ?></select></label>
<label>Department<input name="department" maxlength="100" value="<?= $escape($values['department']) ?>"></label>
<label>Phone<input name="phone" maxlength="20" value="<?= $escape($values['phone']) ?>"></label>
<label>Status<select name="status"><?php foreach (['Authorized', 'Pending', 'Blocked'] as $status): ?><option <?= $values['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label>
<div><button class="btn-primary" type="submit">Save Vehicle</button> <a class="btn-secondary" href="vehicles.php">Cancel</a></div>
</form></div></main></body></html>
