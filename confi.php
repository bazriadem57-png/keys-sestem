<?php
// بيانات الاتصال بقاعدة البيانات
$host = 'caboose.proxy.rlwy.net';
$port = 48796;
$user = 'root';
$pass = 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA';
$db   = 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DATABASE CONNECTION FAILED: " . $conn->connect_error);
}
?>
