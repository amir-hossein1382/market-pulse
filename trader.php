<?php
session_start();

// تنظیمات
define('FINNHUB_API_KEY', 'cvs3o99r01qvc2mujen0cvs3o99r01qvc2mujeng'); // کلید API
$users = [
    'investor1' => 'password123', // نام کاربری و رمز نمونه
    'investor2' => 'password456'
];

// چک کردن لاگین
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// مدیریت لاگین
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    global $users;
    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION['user'] = $username;
    } else {
        $error = 'نام کاربری یا رمز اشتباهه!';
    }
}

// خروج
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: trader.php');
}

// تحلیل بازار
$result = '';
if (isLoggedIn() && isset($_POST['symbol'])) {
    $symbol = strtoupper(trim($_POST['symbol']));
    
    // دریافت قیمت فعلی
    $quoteUrl = "https://finnhub.io/api/v1/quote?symbol=$symbol&token=" . FINNHUB_API_KEY;
    $quoteData = @json_decode(@file_get_contents($quoteUrl), true);
    
    if (!$quoteData || !$quoteData['c']) {
        $result = 'نماد اشتباهه یا داده‌ای نیست!';
    } else {
        // دریافت داده‌های تاریخی
        $histUrl = "https://finnhub.io/api/v1/stock/candle?symbol=$symbol&resolution=D&count=50&token=" . FINNHUB_API_KEY;
        $histData = @json_decode(@file_get_contents($histUrl), true);
        
        $signal = 'خنثی';
        $explanation = '';
        
        if ($histData && $histData['s'] === 'ok') {
            $closes = $histData['c'];
            
            // محاسبه میانگین متحرک
            function calculateMovingAverage($prices, $period) {
                $ma = [];
                for ($i = $period - 1; $i < count($prices); $i++) {
                    $slice = array_slice($prices, $i - $period + 1, $period);
                    $avg = array_sum($slice) / $period;
                    $ma[] = $avg;
                }
                return $ma;
            }
            
            $maShort = calculateMovingAverage($closes, 10);
            $maLong = calculateMovingAverage($closes, 50);
            
            $lastShort = end($maShort);
            $lastLong = end($maLong);
            
            if ($lastShort > $lastLong) {
                $signal = 'خرید';
                $explanation = 'میانگین متحرک کوتاه‌مدت بالاتر از بلندمدته (روند صعودی).';
            } elseif ($lastShort < $lastLong) {
                $signal = 'فروش';
                $explanation = 'میانگین متحرک کوتاه‌مدت پایین‌تر از بلندمدته (روند نزولی).';
            } else {
                $explanation = 'روند مشخص نیست.';
            }
            
            $result = "قیمت فعلی $symbol: $" . number_format($quoteData['c'], 2) . "\n";
            $result .= "سیگنال: $signal\n";
            $result .= "توضیح: $explanation";
        } else {
            $result = "خطا در دریافت داده‌های تاریخی!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ربات معامله‌گر</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            direction: rtl;
            margin: 20px;
        }
        input, button {
            padding: 10px;
            margin: 5px;
            font-size: 16px;
        }
        .error, .result {
            margin-top: 20px;
            font-size: 18px;
        }
        .error { color: red; }
        .result { color: green; }
    </style>
</head>
<body>
    <?php if (!isLoggedIn()): ?>
        <h1>ورود به ربات معامله‌گر</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="نام کاربری" required>
            <input type="password" name="password" placeholder="رمز عبور" required>
            <button type="submit" name="login">ورود</button>
        </form>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    <?php else: ?>
        <h1>ربات معامله‌گر</h1>
        <p>خوش اومدی، <?php echo $_SESSION['user']; ?>! <a href="?logout=1">خروج</a></p>
        <form method="POST">
            <input type="text" name="symbol" placeholder="نماد (مثل AAPL)" required>
            <button type="submit">شروع تحلیل</button>
        </form>
        <?php if ($result): ?>
            <div class="result"><?php echo nl2br(htmlspecialchars($result)); ?></div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
