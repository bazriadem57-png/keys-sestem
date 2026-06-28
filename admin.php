<?php
// إعدادات الاتصال المباشرة (Railway)
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

// --- 1. إعدادات الأمان ---
$admin_password = "adembz57"; 
session_start();

// --- إعداد سعر الصرف (1 دولار = 100 نقطة) ---
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silent ExE - PURPLE ADMIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;800&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body, html { margin: 0; padding: 0; width: 100%; height: 100vh; background-color: #090214; font-family: 'Poppins', Arial, sans-serif; color: white; display: flex; justify-content: center; align-items: center; overflow: hidden; }
        #bgvideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; object-fit: cover; z-index: -2; opacity: 0.25; will-change: transform; }
        .blur { position: absolute; width: 100%; height: 100%; background: radial-gradient(circle, rgba(98,0,234,0.12) 0%, rgba(9,2,20,0.92) 80%); z-index: -1; }
        .container { position: relative; width: 460px; max-height: 95vh; overflow-y: auto; background: rgba(255, 255, 255, 0.03); padding: 35px 25px; border-radius: 40px; border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px); box-shadow: 0 0 40px rgba(123, 31, 162, 0.25); }
        .admin-card { text-align: center; }
        h2 { font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 26px; letter-spacing: 2px; color: #ffffff; text-shadow: 0 0 15px rgba(156, 39, 176, 0.6); margin-bottom: 25px; }
        .section-box { margin-bottom: 20px; padding: 18px; border-radius: 20px; background: rgba(9, 2, 20, 0.6); border: 1px solid rgba(186, 104, 200, 0.2); text-align: left; }
        .section-title { font-family: 'Orbitron', sans-serif; font-size: 11px; color: #ba68c8; border-left: 3px solid #7b1fa2; padding-left: 8px; margin-bottom: 12px; display: block; text-transform: uppercase; font-weight: bold; }
        input, select { width: 100%; padding: 13px 16px; margin-top: 6px; margin-bottom: 12px; background: rgba(9, 2, 20, 0.7); border: 1px solid rgba(186, 104, 200, 0.3); border-radius: 15px; color: white; font-size: 14px; outline: none; }
        button { width: 100%; padding: 13px; margin-top: 5px; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Orbitron', sans-serif; }
        .btn-group { display: flex; gap: 10px; }
        .btn-red { background: linear-gradient(90deg, #b71c1c, #d32f2f); }
        .msg { background: rgba(0, 255, 153, 0.08); border: 1px solid #00ff99; color: #00ff99; padding: 12px; border-radius: 15px; font-size: 13px; font-family: 'Orbitron', sans-serif; margin-bottom: 15px; }
        .error-box { background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #f44336; padding: 12px; border-radius: 15px; font-size: 13px; font-weight: bold; margin-bottom: 15px; }
        .key-display { font-size: 16px; color: #00ff99; border: 1px dashed #00ff99; padding: 12px; margin-top: 10px; font-weight: bold; border-radius: 12px; background: rgba(0, 255, 153, 0.03); font-family: monospace; text-align: center; }
        .logout-btn { display: block; margin-top: 20px; color: #d32f2f; text-decoration: none; font-size: 12px; font-family: 'Orbitron', sans-serif; font-weight: 600; }
    </style>
</head>
<body>

<video autoplay muted loop id="bgvideo"><source src="hacker.mp4" type="video/mp4"></video>
<div class="blur"></div>

<div class="container">
    <div class="admin-card">
        <?php if (!isset($_SESSION['logged_in'])): ?>
            <h2>SYSTEM AUTH</h2>
            <?php if (isset($error)) echo "<div class='error-box'>$error</div>"; ?>
            <form method="POST">
                <input type="password" name="pass" placeholder="ENTER ACCESS KEY" required>
                <button type="submit" name="login">UNLOCK PANEL</button>
            </form>
        <?php else: ?>
            <h2>MASTER PANEL</h2>
            <?php if (isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

            <div class="section-box">
                <span class="section-title">Server Maintenance</span>
                <form method="POST">
                    <select name="m_status">
                        <?php
                        $status_res = $conn->query("SELECT maintenance_mode FROM server_status WHERE id = 1");
                        $status_row = $status_res->fetch_assoc();
                        $current_m = $status_row['maintenance_mode'];
                        ?>
                        <option value="0" <?php if($current_m == 0) echo 'selected'; ?>>ONLINE STATUS</option>
                        <option value="1" <?php if($current_m == 1) echo 'selected'; ?>>MAINTENANCE MODE</option>
                    </select>
                    <button type="submit" name="toggle_maintenance">APPLY CHANGES</button>
                </form>
            </div>

            <div class="section-box">
                <span class="section-title">Direct Generator</span>
                <form method="POST">
                    <select name="days">
                        <option value="1">1 DAY LICENSE</option>
                        <option value="7">7 DAYS LICENSE</option>
                        <option value="30">30 DAYS LICENSE</option>
                    </select>
                    <button type="submit" name="generate_direct">GENERATE KEY</button>
                </form>
                <?php if ($new_key) echo "<div class='key-display'>$new_key</div>"; ?>
            </div>

            <div class="section-box">
                <span class="section-title">Create Reseller</span>
                <form method="POST" style="margin-bottom:20px;">
                    <input type="text" name="res_user" placeholder="NEW USERNAME" required>
                    <input type="text" name="res_pass" placeholder="NEW PASSWORD" required>
                    <button type="submit" name="add_reseller">CREATE ACCOUNT</button>
                </form>

                <span class="section-title">Manage Balance</span>
                <form method="POST">
                    <select name="res_id" required>
                        <option value="">SELECT USER ACCOUNT</option>
                        <?php
                        $list = $conn->query("SELECT id, username, points FROM resellers ORDER BY id DESC");
                        while($row = $list->fetch_assoc()) {
                            $usd = number_format($row['points'] / $point_rate, 2);
                            echo "<option value='".$row['id']."'>".$row['username']." (".$row['points']." PTS = $".$usd.")</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="pts" placeholder="ENTER NUMBER OF POINTS" required min="1">
                    <input type="hidden" id="action_type" name="action" value="add">
                    <div class="btn-group">
                        <button type="submit" name="update_points" value="add">ADD (+)</button>
                        <button type="submit" name="update_points" value="sub" class="btn-red" onclick="document.getElementById('action_type').value='sub'">SUB (-)</button>
                    </div>
                </form>
            </div>

            <a href="?logout=1" class="logout-btn">LOCK PANEL SESSION</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
