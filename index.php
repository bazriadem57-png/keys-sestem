<?php
// --- 1. إعدادات الاتصال المحدثة لسيرفر Railway ---
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

session_start();
$point_rate = 100; // 1 دولار = 100 نقطة

// ميزة تسجيل الخروج (تدمير الجلسة بالكامل)
if (isset($_GET['logout'])) {
    $_SESSION = array(); 
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
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

// --- عملية الشراء الاحترافية ---
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
        #bgvideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; object-fit: cover; z-index: -2; opacity: 0.22; will-change: transform; }
        .blur { position: absolute; width: 100%; height: 100%; background: radial-gradient(circle, rgba(98,0,234,0.1) 0%, rgba(9,2,20,0.95) 80%); z-index: -1; }
        .container { position: relative; width: <?php echo isset($_SESSION['reseller']) ? '920px' : '440px'; ?>; max-height: 95vh; overflow-y: auto; background: rgba(5, 15, 25, 0.93); padding: 35px 30px; border-radius: 30px; border: 1px solid rgba(186, 104, 200, 0.3); backdrop-filter: blur(25px); box-shadow: 0 0 40px rgba(123, 31, 162, 0.2); transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .container::-webkit-scrollbar { width: 4px; }
        .container::-webkit-scrollbar-thumb { background: rgba(186, 104, 200, 0.4); border-radius: 10px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(186, 104, 200, 0.2); padding-bottom: 15px; }
        .space-logo { font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 28px; letter-spacing: 2px; color: #ffffff; text-shadow: 0 0 15px rgba(156, 39, 176, 0.6); text-transform: uppercase; }
        .welcome-text { font-size: 11px; color: #b0bec5; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; }
        .balance-card { background: rgba(186, 104, 200, 0.05); border: 1px solid rgba(224, 64, 251, 0.4); padding: 12px 25px; border-radius: 15px; color: #e040fb; font-weight: bold; font-size: 15px; display: inline-flex; align-items: center; font-family: 'Orbitron', sans-serif; text-shadow: 0 0 10px rgba(224, 64, 251, 0.3); direction: ltr !important; }
        h3 { margin-top: 20px; margin-bottom: 15px; font-family: 'Orbitron', sans-serif; font-size: 11px; color: #ba68c8; border-left: 3px solid #7b1fa2; padding-left: 8px; letter-spacing: 1px; text-transform: uppercase; }
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .product-card { background: rgba(9, 2, 20, 0.7); border: 1px solid rgba(186, 104, 200, 0.2); border-radius: 18px; padding: 20px 15px; display: flex; flex-direction: column; align-items: center; text-align: center; transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); background: rgba(15, 5, 30, 0.85); border-color: #ffffff; }
        .price { font-size: 18px; color: #00ff99; font-weight: 700; font-family: 'Orbitron', sans-serif; }
        .buy-btn { width: 100%; padding: 13px; margin-top: 15px; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: none; border-radius: 12px; color: white; font-weight: 600; cursor: pointer; font-family: 'Orbitron', sans-serif; }
        .buy-small-btn { width: 100%; background: linear-gradient(90deg, #7b1fa2, #6a1b9a); border: none; color: white; padding: 10px 0; border-radius: 10px; font-weight: bold; cursor: pointer; font-family: 'Orbitron', sans-serif; font-size: 11px; margin-top: 15px; }
        .error-box { background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #f44336; padding: 12px; border-radius: 12px; text-align: center; margin-bottom: 20px; font-size: 12px; font-family: 'Orbitron', sans-serif; }
        .history { background: rgba(9, 2, 20, 0.8); border-radius: 15px; padding: 15px; border: 1px solid rgba(186, 104, 200, 0.3); }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { text-align: left; padding: 10px 8px; color: #b0bec5; font-family: 'Orbitron', sans-serif; }
        td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(9, 2, 20, 0.85); display: flex; justify-content: center; align-items: center; z-index: 9999; backdrop-filter: blur(8px); }
        .modal-card { background: rgba(9, 2, 20, 0.95); padding: 35px 25px; border-radius: 25px; border: 2px solid #e040fb; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 0 30px rgba(224, 64, 251, 0.4); }
        .key-display { font-size: 18px; color: #00ff99; border: 1px dashed #00ff99; padding: 15px; margin: 20px 0; cursor: pointer; border-radius: 12px; background: rgba(0, 255, 153, 0.03); font-family: monospace; }
        .logout-btn-top { background: rgba(211, 47, 47, 0.1); border: 1px solid #d32f2f; color: #f44336; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-family: 'Orbitron', sans-serif; font-size: 11px; }
        .telegram-sidebar { position: fixed; right: 20px; bottom: 20px; z-index: 999; background: #0088cc; border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px rgba(0, 136, 204, 0.5); }
    </style>
</head>
<body>
<video autoplay muted loop id="bgvideo"><source src="hacker.mp4" type="video/mp4"></video>
<div class="blur"></div>

<a href="https://t.me/SilentExE_x" target="_blank" class="telegram-sidebar">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-1-.65-.35-1 .22-1.62.15-.15 2.7-2.48 2.75-2.7.01-.03.01-.14-.05-.2-.06-.06-.15-.04-.22-.02-.1.02-1.68 1.07-4.75 3.14-.45.31-.86.46-1.23.45-.41-.01-1.2-.23-1.79-.42-.72-.23-1.29-.36-1.24-.75.03-.2.31-.4 1.83-1.03 5.46-2.37 9.1-3.93 10.93-4.69.52-.21 1.07-.3 1.53-.29.46.01.97.26 1.14.7.11.27.09.58.05.89z"/></svg>
</a>

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
        $balance_usd = ($data['points'] ?? 0) / $point_rate;
    ?>
        <div class="header">
            <div class="header-titles">
                <div class="space-logo" style="font-size: 24px;">SILENT STORE</div>
                <div class="welcome-text">Logged in as: <?php echo htmlspecialchars($u); ?></div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="balance-card">BALANCE: $<?php echo number_format($balance_usd, 2); ?></div>
                <a href="?logout=1" class="logout-btn-top">LOGOUT</a>
            </div>
        </div>

        <?php if ($error) echo "<div class='error-box'>$error</div>"; ?>

        <h3>AVAILABLE PASSES</h3>
        <div class="products-grid">
            <?php foreach ($products as $days => $price): ?>
                <div class="product-card">
                    <div class="prod-info">
                        <h4><?php echo $days; ?> DAY PASS</h4>
                        <div class="price">$<?php echo number_format($price, 2); ?></div>
                    </div>
                    <form method="POST" style="width:100%;">
                        <input type="hidden" name="days" value="<?php echo $days; ?>">
                        <button type="submit" name="buy_key" class="buy-small-btn">PURCHASE</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>RECENT ORDERS</h3>
        <div class="history">
            <table>
                <thead><tr><th>LICENSE KEY</th><th>EXPIRY</th><th>STATUS</th></tr></thead>
                <tbody>
                    <?php
                    $history = $conn->query("SELECT key_code, expiry_date FROM keys_table WHERE reseller_name='$u' ORDER BY id DESC LIMIT 6");
                    if($history && $history->num_rows > 0):
                        while($row = $history->fetch_assoc()):
                            $is_expired = (time() > strtotime($row['expiry_date']));
                    ?>
                    <tr>
                        <td style="font-family:monospace; color:#ba68c8;"><?php echo $row['key_code']; ?></td>
                        <td><?php echo date('M d, H:i', strtotime($row['expiry_date'])); ?></td>
                        <td><b style="color:<?php echo $is_expired ? '#f44336' : '#00ff99'; ?>"><?php echo $is_expired ? 'EXPIRED' : 'ACTIVE'; ?></b></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#666;">No orders.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($generated_key): ?>
<div class="modal-overlay">
    <div class="modal-card">
        <h2>KEY GENERATED</h2>
        <div class="key-display" id="keyText"><?php echo $generated_key; ?></div>
        <a href="?clear_key=1" class="buy-btn">CLOSE</a>
    </div>
</div>
<?php endif; ?>

<script>
    function copyKey() { const text = document.getElementById("keyText").innerText; navigator.clipboard.writeText(text); alert("Copied!"); }
</script>
</body>
</html>
