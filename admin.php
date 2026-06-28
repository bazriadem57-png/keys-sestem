<?php
// تعديل المسار لضمان عمل الملف على أي سيرفر
require_once __DIR__ . '/config.php';

// --- 1. إعدادات الأمان ---
$admin_password = "adembz57"; 
session_start();

// --- إعداد سعر الصرف ---
$point_rate = 100; 

if (isset($_POST['login'])) {
    if ($_POST['pass'] == $admin_password) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "INVALID ADMIN KEY!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- 2. إعدادات الاتصال (يتم سحبها من config.php) ---
// تأكد أن $conn معرفة في ملف config.php
// إذا لم تكن موجودة، أضف السطر التالي هنا (مع بيانات Railway):
// $conn = new mysqli('HOST', 'USER', 'PASSWORD', 'DB_NAME', 'PORT');

$new_key = ""; 

// --- 3. العمليات البرمجية ---
if (isset($_SESSION['logged_in'])) {
    // إضافة موزع جديد
    if (isset($_POST['add_reseller'])) {
        $res_user = mysqli_real_escape_string($conn, trim($_POST['res_user']));
        $res_pass = mysqli_real_escape_string($conn, trim($_POST['res_pass']));
        if(!empty($res_user) && !empty($res_pass)){
            $sql = "INSERT INTO resellers (username, password, points) VALUES ('$res_user', '$res_pass', 0)";
            $conn->query($sql);
            $msg = "Reseller Added Successfully!";
        }
    }

    // إضافة أو تنقيص النقاط
    if (isset($_POST['update_points'])) {
        $res_id = intval($_POST['res_id']);
        $pts = intval($_POST['pts']);
        $action = $_POST['action']; 

        if ($action == 'add') {
            $conn->query("UPDATE resellers SET points = points + $pts WHERE id = $res_id");
            $msg = "Points Added: +$pts";
        } else {
            $conn->query("UPDATE resellers SET points = points - $pts WHERE id = $res_id");
            $msg = "Points Deducted: -$pts";
        }
    }

    // حالة السيرفر
    if (isset($_POST['toggle_maintenance'])) {
        $new_status = intval($_POST['m_status']);
        $conn->query("UPDATE server_status SET maintenance_mode = $new_status WHERE id = 1");
        $msg = "Server Status Updated!";
    }

    // توليد كود
    if (isset($_POST['generate_direct'])) {
        $days = intval($_POST['days']);
        $new_key = "EXE-ADMIN-" . rand(1000, 9999);
        date_default_timezone_set('Africa/Algiers');
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$days days"));
        $conn->query("INSERT INTO keys_table (key_code, status, expiry_date) VALUES ('$new_key', 'active', '$expiry_date')");
    }
}
?>
