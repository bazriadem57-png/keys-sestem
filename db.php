<?php
// إعدادات الاتصال بسيرفر Railway الجديد الخاص بك
$host = 'caboose.proxy.rlwy.net';
$port = 48796;
$user = 'root';
$pass = 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA';
$db   = 'railway';

// بدء الاتصال
$conn = new mysqli($host, $user, $pass, $db, $port);

// التحقق من نجاح الاتصال
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode([
        "status" => "error", 
        "message" => "فشل الاتصال بقاعدة البيانات: " . $conn->connect_error
    ]));
}

// 🛠️ إنشاء جدول المفاتيح (keys_table) تلقائياً إذا لم يكن موجوداً
$query1 = "CREATE TABLE IF NOT EXISTS `keys_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subscription_key` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(50) DEFAULT 'unused',
    `expiry_date` DATETIME NULL,
    `device_id` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// 🛠️ إنشاء جدول حالة السيرفر (server_status) تلقائياً إذا لم يكن موجوداً
$query2 = "CREATE TABLE IF NOT EXISTS `server_status` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `status` VARCHAR(50) DEFAULT 'online',
    `message` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// تنفيذ الأوامر في السيرفر
$conn->query($query1);
$conn->query($query2);

// ملاحظة: اترك هذا الملف ليتم استدعاؤه في باقي ملفات مشروعك (مثل login.php أو generate.php) باستخدام include('connect.php');
?>
