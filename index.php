<?php
// إعدادات الاتصال المباشرة
$host = 'caboose.proxy.rlwy.net';
$port = 48796;
$user = 'root';
$pass = 'kvEqKBkeduTQTNEoGSQSYExCPSVtZhrA';
$db   = 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) { die("Database Connection Failed"); }

session_start();
$point_rate = 100;

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_GET['clear_key'])) {
    unset($_SESSION['generated_key']);
    unset($_SESSION['expiry_info']);
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $u = mysqli_real_escape_string($conn, trim($_POST['user']));
    $p = mysqli_real_escape_string($conn, trim($_POST['pass']));
    $res = $conn->query("SELECT * FROM resellers WHERE username='$u' AND password='$p'");
    if ($res && $res->num_rows > 0) {
        $_SESSION['reseller'] = $u;
        header("Location: index.php");
        exit();
    } else { $error = "INVALID CREDENTIALS!"; }
}

$products = [1 => 1.00, 3 => 2.50, 4 => 3.20, 5 => 4.00, 6 => 4.80, 7 => 5.50, 15 => 10.00, 30 => 18.00];

if (isset($_SESSION['reseller']) && isset($_POST['buy_key'])) {
    $u = $_SESSION['reseller'];
    $days = intval($_POST['days']);
    $price = $products[$days];
    $cost = $price * $point_rate;
    
    $check = $conn->query("SELECT points FROM resellers WHERE username='$u'");
    $row = $check->fetch_assoc();
    
    if ($row && $row['points'] >= $cost) {
        $key = "SILENT-" . strtoupper(bin2hex(random_bytes(2))) . "-" . rand(1000, 9999);
        $expiry = date('Y-m-d H:i:s', strtotime("+$days days"));
        $conn->query("UPDATE resellers SET points = points - $cost WHERE username='$u'");
        $conn->query("INSERT INTO keys_table (key_code, status, expiry_date, reseller_name) VALUES ('$key', 'active', '$expiry', '$u')");
        $_SESSION['generated_key'] = $key;
        header("Location: index.php");
        exit();
    } else { $_SESSION['buy_error'] = "INSUFFICIENT BALANCE!"; header("Location: index.php"); exit(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Silent Store</title></head>
<body>
    <div class="container">
        </div>
</body>
</html>
