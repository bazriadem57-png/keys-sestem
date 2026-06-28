<?php
session_start();
require_once 'config.php';

$point_rate = 100; // 1 دولار = 100 نقطة

// ميزة تسجيل الخروج
if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// مسح الكود من الجلسة عند إغلاق المودال
if (isset($_GET['clear_key'])) {
    unset($_SESSION['generated_key']);
    unset($_SESSION['expiry_info']);
    header("Location: index.php");
    exit();
}

// تسجيل دخول الموزع
if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, trim($_POST['user']));
    $pass = mysqli_real_escape_string($conn, trim($_POST['pass']));
    
    $res = $conn->query("SELECT * FROM resellers WHERE username='$user' AND password='$pass'");
    if ($res && $res->num_rows > 0) {
        $_SESSION['reseller'] = $user;
        $_SESSION['play_welcome'] = true; 
        header("Location: index.php");
        exit();
    } else {
        $error = "INVALID CREDENTIALS!";
    }
}

// مصفوفة الأسعار الثابتة
$products = [
    1 => 1.00, 3 => 2.50, 4 => 3.20, 5 => 4.00, 
    6 => 4.80, 7 => 5.50, 15 => 10.00, 30 => 18.00
];

// عملية الشراء
if (isset($_SESSION['reseller']) && isset($_POST['buy_key'])) {
    $user = $_SESSION['reseller'];
    $days = intval($_POST['days']); 
    
    if (isset($products[$days])) {
        $price_usd = $products[$days];
        $cost_points = $price_usd * $point_rate;
        
        $check = $conn->query("SELECT points FROM resellers WHERE username='$user'");
        $row = $check->fetch_assoc();
        
        if ($row && $row['points'] >= $cost_points) {
            $temp_key = "SILENT-" . strtoupper(bin2hex(random_bytes(2))) . "-" . rand(1000, 9999);
            date_default_timezone_set('Africa/Algiers');
            $expiry = date('Y-m-d H:i:s', strtotime("+$days days"));
            
            $update = $conn->query("UPDATE resellers SET points = points - $cost_points WHERE username='$user' AND points >= $cost_points");
            
            if ($update && $conn->affected_rows > 0) {
                $conn->query("INSERT INTO keys_table (key_code, status, expiry_date, reseller_name) VALUES ('$temp_key', 'active', '$expiry', '$user')");
                
                $_SESSION['generated_key'] = $temp_key;
                $_SESSION['expiry_info'] = "VALID FOR: $days DAY(S)";
                
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['buy_error'] = "INSUFFICIENT BALANCE (NEED " . number_format($cost_points) . " POINTS / $$price_usd)";
            header("Location: index.php");
            exit();
        }
    }
}

$error = isset($_SESSION['buy_error']) ? $_SESSION['buy_error'] : (isset($error) ? $error : null);
unset($_SESSION['buy_error']);

$generated_key = isset($_SESSION['generated_key']) ? $_SESSION['generated_key'] : "";
$expiry_info = isset($_SESSION['expiry_info']) ? $_SESSION['expiry_info'] : "";

$should_play_sound = false;
if (isset($_SESSION['play_welcome'])) {
    $should_play_sound = true;
    unset($_SESSION['play_welcome']);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silent Store | Reseller</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #090214; color: white; font-family: 'Poppins', Arial, sans-serif; height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden; }
        #bgvideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; object-fit: cover; z-index: -2; opacity: 0.22; }
        .blur { position: absolute; width: 100%; height: 100%; background: radial-gradient(circle, rgba(98,0,234,0.1) 0%, rgba(9,2,20,0.95) 80%); z-index: -1; }
        .container { position: relative; width: <?php echo isset($_SESSION['reseller']) ? '920px' : '440px'; ?>; max-height: 95vh; overflow-y: auto; background: rgba(5, 15, 25, 0.93); padding: 35px 30px; border-radius: 30px; border: 1px solid rgba(186, 104, 200, 0.3); backdrop-filter: blur(25px); box-shadow: 0 0 40px rgba(123, 31, 162, 0.2); transition: width 0.4s ease; }
        .container::-webkit-scrollbar { width: 4px; }
        .container::-webkit-scrollbar-thumb { background: rgba(186, 104, 200, 0.4); border-radius: 10px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(186, 104, 200, 0.2); padding-bottom: 15px; }
        .space-logo { font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 28px; letter-spacing: 2px; color: #ffffff; text-shadow: 0 0 15px rgba(156, 39, 176, 0.6); text-transform: uppercase; }
        .welcome-text { font-size: 11px; color: #b0bec5; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; }
        .balance-card { background: rgba(186, 104, 200, 0.05); border: 1px solid rgba(224, 64, 251, 0.4); padding: 12px 25px; border-radius: 15px; color: #e040fb; font-weight: bold; font-size: 15px; display: inline-flex; align-items: center; font-family: 'Orbitron', sans-serif; text-shadow: 0 0 10px rgba(224, 64, 251, 0.3); direction: ltr !important; }
        h3 { margin-top: 20px; margin-bottom: 15px; font-family: 'Orbitron', sans-serif; font-size: 11px; color: #ba68c8; border-left: 3px solid #7b1fa2; padding-left: 8px; letter-spacing: 1px; text-transform: uppercase; }
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .product-card { background: rgba(9, 2, 20, 0.7); border: 1px solid rgba(186, 104, 200, 0.2); border-radius: 18px; padding: 20px 15px; display: flex; flex-direction: column; justify-content: space-between; align-items: center; text-align: center; transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); border-color: #ffffff; box-shadow: 0 10px 25px rgba(123, 31, 162, 0.3); }
        .prod-info h4 { font-family: 'Orbitron', sans-serif; color: #fff; font-size: 12px; letter-spacing: 1px; margin-bottom: 5px; }
        .price { font-size: 18px; color: #00ff99; font-weight: 700; font-family: 'Orbitron', sans-serif; }
        .buy-btn { width: 100%; padding: 13px; margin-top: 15px; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Orbitron', sans-serif; letter-spacing: 1.5px; }
        .buy-small-btn { width: 100%; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 10px 0; border-radius: 10px; font-weight: bold; cursor: pointer; font-family: 'Orbitron', sans-serif; font-size: 11px; margin-top: 15px; }
        .error-box { background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #f44336; padding: 12px; border-radius: 12px; text-align: center; margin-bottom: 20px; font-size: 12px; font-family: 'Orbitron', sans-serif; }
        .history { background: rgba(9, 2, 20, 0.8); border-radius: 15px; padding: 15px; border: 1px solid rgba(186, 104, 200, 0.3); }
        .table-wrapper { max-height: 160px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { text-align: left; padding: 10px 8px; color: #b0bec5; border-bottom: 1px solid rgba(255,255,255,0.1); font-family: 'Orbitron', sans-serif; }
        td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(9, 2, 20, 0.85); display: flex; justify-content: center; align-items: center; z-index: 9999; backdrop-filter: blur(8px); }
        .modal-card { background: rgba(9, 2, 20, 0.95); padding: 35px 25px; border-radius: 25px; border: 2px solid #e040fb; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 0 30px rgba(224, 64, 251, 0.4); }
        .key-display { font-size: 18px; color: #00ff99; border: 1px dashed #00ff99; padding: 15px; margin: 20px 0; cursor: pointer; font-weight: bold; border-radius: 12px; background: rgba(0, 255, 153, 0.03); word-break: break-all; font-family: monospace; }
        input { width: 100%; padding: 13px 16px; margin-top: 6px; background: #000; border: 1px solid rgba(186, 104, 200, 0.4); border-radius: 12px; color: white; font-size: 13px; outline: none; font-family: 'Orbitron', sans-serif; }
        .logout-btn-top { background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #f44336; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-family: 'Orbitron', sans-serif; font-size: 11px; font-weight: bold; }
        @media (max-width: 950px) { .container { width: 92%; max-width: 480px; padding: 25px 20px; } .products-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
<div class="blur"></div>
<div class="container">
    <?php if (!isset($_SESSION['reseller'])): ?>
        <div class="login-form">
            <div class="space-logo">RESELLER LOGIN</div>
            <?php if ($error) echo "<div class='error-box'>$error</div>"; ?>
            <form method="POST">
                <input type="text" name="user" placeholder="Username" required>
                <input type="password" name="pass" placeholder="Password" required>
                <button type="submit" name="login" class="buy-btn">LOGIN</button>
            </form>
        </div>
    <?php else: 
        $u = $_SESSION['reseller'];
        $data = $conn->query("SELECT points FROM resellers WHERE username='$u'")->fetch_assoc();
        $pts = $data['points'] ?? 0;
        $balance_usd = $pts / $point_rate;
    ?>
        <div class="header">
            <div>
                <div class="space-logo" style="font-size: 24px;">SILENT STORE</div>
                <div class="welcome-text">Logged in as: <?php echo htmlspecialchars($u); ?></div>
            </div>
            <div>
                <div class="balance-card">BALANCE: $<?php echo number_format($balance_usd, 2); ?></div>
                <a href="?logout=1" class="logout-btn-top">LOGOUT</a>
            </div>
        </div>
        <h3>AVAILABLE PASSES</h3>
        <div class="products-grid">
            <?php foreach ($products as $days => $price): ?>
                <div class="product-card">
                    <h4><?php echo $days; ?> DAY PASS</h4>
                    <div class="price">$<?php echo number_format($price, 2); ?></div>
                    <form method="POST">
                        <input type="hidden" name="days" value="<?php echo $days; ?>">
                        <button type="submit" name="buy_key" class="buy-small-btn">PURCHASE</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <h3>RECENT ORDERS</h3>
        <div class="history"><div class="table-wrapper"><table>
            <thead><tr><th>LICENSE KEY</th><th>EXPIRY</th><th>STATUS</th></tr></thead>
            <tbody>
                <?php
                $history = $conn->query("SELECT key_code, expiry_date FROM keys_table WHERE reseller_name='$u' ORDER BY id DESC LIMIT 6");
                if($history && $history->num_rows > 0):
                    while($row = $history->fetch_assoc()):
                        $is_expired = (time() > strtotime($row['expiry_date']));
                ?>
                <tr>
                    <td style="font-family:monospace; color:#ba68c8; font-weight:600;"><?php echo $row['key_code']; ?></td>
                    <td><?php echo date('M d, H:i', strtotime($row['expiry_date'])); ?></td>
                    <td><b style="color:<?php echo $is_expired ? '#f44336' : '#00ff99'; ?>"><?php echo $is_expired ? 'EXPIRED' : 'ACTIVE'; ?></b></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3" style="text-align:center;">No keys purchased yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div></div>
    <?php endif; ?>
</div>

<?php if ($generated_key): ?>
<div class="modal-overlay"><div class="modal-card">
    <h2 style="color:#00ff99; font-family:'Orbitron';">KEY GENERATED</h2>
    <div class="key-display" id="keyText" onclick="copyKey()"><?php echo $generated_key; ?></div>
    <a href="?clear_key=1" class="buy-btn" style="text-decoration:none; display:block;">CLOSE</a>
</div></div>
<script>
function copyKey() {
    navigator.clipboard.writeText(document.getElementById("keyText").innerText);
    alert("Key copied! ✅");
}
</script>
<?php endif; ?>
</body>
</html>
