<?php
$admin_password = "adembz57"; 
session_start();
require_once 'config.php';

$point_rate = 100; 

if (isset($_POST['login'])) {
    if ($_POST['pass'] == $admin_password) { $_SESSION['logged_in'] = true; } else { $error = "INVALID ADMIN KEY!"; }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit(); }

$new_key = ""; 
if (isset($_SESSION['logged_in'])) {
    // إضافة موزع جديد
    if (isset($_POST['add_reseller'])) {
        $res_user = mysqli_real_escape_string($conn, trim($_POST['res_user']));
        $res_pass = mysqli_real_escape_string($conn, trim($_POST['res_pass']));
        if(!empty($res_user) && !empty($res_pass)){
            $conn->query("INSERT INTO resellers (username, password, points) VALUES ('$res_user', '$res_pass', 0)");
            $msg = "Reseller Added Successfully!";
        }
    }
    // تعديل النقاط
    if (isset($_POST['update_points'])) {
        $res_id = intval($_POST['res_id']);
        $pts = intval($_POST['pts']);
        $action = $_POST['action']; 
        if ($action == 'add') {
            $conn->query("UPDATE resellers SET points = points + $pts WHERE id = $res_id");
            $msg = "Points Added!";
        } else {
            $conn->query("UPDATE resellers SET points = points - $pts WHERE id = $res_id");
            $msg = "Points Deducted!";
        }
    }
    // الصيانة
    if (isset($_POST['toggle_maintenance'])) {
        $new_status = intval($_POST['m_status']);
        $conn->query("UPDATE server_status SET maintenance_mode = $new_status WHERE id = 1");
        $msg = "Server Status Updated!";
    }
    // توليد مباشر
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
    <title>Silent ExE - MASTER PANEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Poppins&display=swap" rel="stylesheet">
    <style>
        body { background-color: #090214; color: white; font-family: 'Poppins'; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin:0; }
        .container { width: 460px; background: rgba(255, 255, 255, 0.03); padding: 35px 25px; border-radius: 40px; border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 0 40px rgba(123, 31, 162, 0.25); }
        h2 { font-family: 'Orbitron'; text-align: center; color: #ffffff; text-shadow: 0 0 15px rgba(156, 39, 176, 0.6); }
        .section-box { margin-bottom: 20px; padding: 18px; border-radius: 20px; background: rgba(9, 2, 20, 0.6); border: 1px solid rgba(186, 104, 200, 0.2); }
        input, select { width: 100%; padding: 13px; margin-bottom: 12px; background: #000; border: 1px solid rgba(186, 104, 200, 0.3); border-radius: 15px; color: white; }
        button { width: 100%; padding: 13px; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: none; border-radius: 15px; color: white; font-family: 'Orbitron'; cursor: pointer; }
        .btn-group { display: flex; gap: 10px; }
        .msg { color: #00ff99; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_SESSION['logged_in'])): ?>
        <h2>SYSTEM AUTH</h2>
        <form method="POST">
            <input type="password" name="pass" placeholder="ENTER ACCESS KEY" required>
            <button type="submit" name="login">UNLOCK PANEL</button>
        </form>
    <?php else: ?>
        <h2>MASTER PANEL</h2>
        <?php if (isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
        <div class="section-box">
            <form method="POST">
                <select name="m_status">
                    <option value="0">ONLINE STATUS</option>
                    <option value="1">MAINTENANCE MODE</option>
                </select>
                <button type="submit" name="toggle_maintenance">APPLY CHANGES</button>
            </form>
        </div>
        <div class="section-box">
            <form method="POST">
                <button type="submit" name="generate_direct">GENERATE ADMIN KEY</button>
            </form>
            <?php if ($new_key) echo "<div style='text-align:center; color:#00ff99; margin-top:10px;'>$new_key</div>"; ?>
        </div>
        <div class="section-box">
            <form method="POST">
                <input type="text" name="res_user" placeholder="NEW USERNAME" required>
                <input type="text" name="res_pass" placeholder="NEW PASSWORD" required>
                <button type="submit" name="add_reseller">CREATE RESELLER</button>
            </form>
        </div>
        <div class="section-box">
            <form method="POST">
                <select name="res_id" required>
                    <option value="">SELECT USER ACCOUNT</option>
                    <?php
                    $list = $conn->query("SELECT id, username, points FROM resellers ORDER BY id DESC");
                    while($row = $list->fetch_assoc()) { echo "<option value='".$row['id']."'>".$row['username']." (".$row['points']." PTS)</option>"; }
                    ?>
                </select>
                <input type="number" name="pts" placeholder="POINTS COUNT" required>
                <input type="hidden" id="action_type" name="action" value="add">
                <div class="btn-group">
                    <button type="submit" name="update_points" value="add">ADD</button>
                    <button type="submit" name="update_points" value="sub" style="background:red;" onclick="document.getElementById('action_type').value='sub'">SUB</button>
                </div>
            </form>
        </div>
        <p style="text-align:center;"><a href="?logout=1" style="color:red;">LOGOUT</a></p>
    <?php endif; ?>
</div>
</body>
</html>
