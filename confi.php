<?php
// إعدادات الاتصال الموحدة لسيرفر Railway الجديد الخاص بك
$host = 'caboose.proxy.rlwy.net';
$port = 48796;
$user = 'root';
$pass = 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA';
$db   = 'railway';

// بدء الاتصال
$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

// التحقق من الاتصال
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(["status" => "error", "message" => "DATABASE CONNECTION FAILED!"]));
}

// 🛠️ تهيئة جدول الموزعين (resellers) تلقائياً
$conn->query("CREATE TABLE IF NOT EXISTS `resellers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `points` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 🛠️ تهيئة جدول المفاتيح (keys_table) متوافق مع لوحة الموزعين والتطبيق
$conn->query("CREATE TABLE IF NOT EXISTS `keys_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key_code` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(50) DEFAULT 'active',
    `expiry_date` DATETIME NULL,
    `device_id` VARCHAR(255) NULL,
    `reseller_name` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 🛠️ تهيئة جدول حالة السيرفر (server_status)
$conn->query("CREATE TABLE IF NOT EXISTS `server_status` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `maintenance_mode` INT DEFAULT 0,
    `message` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// إدخال السطر الأول للصيانة الافتراضية إذا كانت القاعدة فارغة
$conn->query("INSERT IGNORE INTO server_status (id, maintenance_mode) VALUES (1, 0);");
?>
