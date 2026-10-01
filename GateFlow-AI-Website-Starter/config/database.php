<?php
$appTimezone = getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
date_default_timezone_set($appTimezone);

$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$database = "gateflow_ai";

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$mysqlTimezoneOffset = date('P');
$conn->query("SET time_zone = '" . $conn->real_escape_string($mysqlTimezoneOffset) . "'");
?>
