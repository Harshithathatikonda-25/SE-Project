<?php
session_start();
include 'config/database.php';
$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$vehicleId) {
    http_response_code(400);
    exit('A valid vehicle ID is required.');
}
$findVehicle = $conn->prepare('SELECT vehicle_id, vehicle_number, owner_name, vehicle_type, department, phone, status FROM vehicles WHERE vehicle_id = ?');
$findVehicle->bind_param('i', $vehicleId);
$findVehicle->execute();
$vehicle = $findVehicle->get_result()->fetch_assoc();
$findVehicle->close();
if (!$vehicle) {
    http_response_code(404);
    exit('Vehicle not found.');
}
if (empty($_SESSION['edit_vehicle_csrf'])) {
    $_SESSION['edit_vehicle_csrf'] = bin2hex(random_bytes(32));
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $ownerName = trim($_POST['owner_name'] ?? '');
    $vehicleType = $_POST['vehicle_type'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? '';
    if (!hash_equals($_SESSION['edit_vehicle_csrf'], $_POST['csrf_token'] ?? '')) {
        $message = 'Request verification failed. Refresh the page and try again.';
    } elseif (!preg_match('/^[A-Z0-9 -]{2,20}$/', $vehicleNumber) || $ownerName === '' || strlen($ownerName) > 100 || !in_array($vehicleType, ['Car', 'Bike', 'Bus', 'Other'], true) || !in_array($status, ['Authorized', 'Pending', 'Blocked'], true) || strlen($department) > 100 || strlen($phone) > 20) {
        $message = 'Check the vehicle number, owner, type, department, phone, and status values.';
    } else {
        $update = $conn->prepare('UPDATE vehicles SET vehicle_number = ?, owner_name = ?, vehicle_type = ?, department = ?, phone = ?, status = ? WHERE vehicle_id = ?');
        $update->bind_param('ssssssi', $vehicleNumber, $ownerName, $vehicleType, $department, $phone, $status, $vehicleId);
        try {
            $update->execute();
            $update->close();
            header('Location: vehicles.php?updated=1');
            exit;
        } catch (mysqli_sql_exception $exception) {
            $update->close();
            $message = 'Could not update this vehicle. That plate number may already be assigned to another vehicle.';
        }
    }
    $vehicle = array_merge($vehicle, ['vehicle_number' => $vehicleNumber, 'owner_name' => $ownerName, 'vehicle_type' => $vehicleType, 'department' => $department, 'phone' => $phone, 'status' => $status]);
}
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Vehicle - GateFlow-AI</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/dashboard-theme.css"><link rel="stylesheet" href="css/light-theme.css"></head>
<body class="app-theme"><?php include 'includes/sidebar.php'; ?><main class="main-content">
<div class="topbar"><div><h2>Edit Vehicle</h2><p>Update the plate registry, owner details, or access status.</p></div><a class="btn-secondary" href="vehicles.php">Back to vehicles</a></div>
<section class="panel form-panel">
<?php if ($message !== ''): ?><div class="error" role="alert"><?= $escape($message) ?></div><?php endif; ?>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['edit_vehicle_csrf']) ?>">
<label>Vehicle Number<input name="vehicle_number" required maxlength="20" pattern="[A-Za-z0-9 -]{2,20}" value="<?= $escape($vehicle['vehicle_number']) ?>"></label>
<label>Owner Name<input name="owner_name" required maxlength="100" value="<?= $escape($vehicle['owner_name']) ?>"></label>
<label>Vehicle Type<select name="vehicle_type"><?php foreach (['Car', 'Bike', 'Bus', 'Other'] as $type): ?><option <?= $vehicle['vehicle_type'] === $type ? 'selected' : '' ?>><?= $escape($type) ?></option><?php endforeach; ?></select></label>
<label>Department<input name="department" maxlength="100" value="<?= $escape($vehicle['department']) ?>"></label>
<label>Phone<input name="phone" maxlength="20" value="<?= $escape($vehicle['phone']) ?>"></label>
<label>Access Status<select name="status"><?php foreach (['Authorized', 'Pending', 'Blocked'] as $status): ?><option <?= $vehicle['status'] === $status ? 'selected' : '' ?>><?= $escape($status) ?></option><?php endforeach; ?></select></label>
<div><button class="btn-primary" type="submit">Save changes</button> <a class="btn-secondary" href="vehicles.php">Cancel</a></div>
</form></section></main></body></html>
