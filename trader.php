<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QUANTUM TRADER | Trading Bot</title>
    <style>
        :root {
            --primary-color: #00ff88;
            --dark-bg: #0f0f1a;
            --darker-bg: #070710;
            --text-color: #ffffff;
            --secondary-text: #aaaaaa;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            border: 1px solid var(--primary-color);
        }
        
        h1 {
            font-size: 2.5rem;
            background: linear-gradient(to right, var(--primary-color), #00a2ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        input, button {
            padding: 10px;
            margin: 10px;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid var(--primary-color);
            background: var(--darker-bg);
            color: var(--text-color);
        }
        
        button {
            cursor: pointer;
            background: var(--primary-color);
            color: #000;
            border: none;
        }
        
        button:hover {
            background: #00cc70;
        }
        
        #result, #error {
            margin-top: 20px;
            font-size: 1.2rem;
        }
        
        #error {
            color: #ff4d4d;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>QUANTUM TRADING BOT</h1>
        
        <div id="login-section">
            <input type="text" id="username" placeholder="Username">
            <input type="password" id="password" placeholder="Password">
            <button onclick="login()">Login</button>
            <div id="error"></div>
        </div>
        
        <div id="bot-section" class="hidden">
            <p>Welcome, <span id="user-display"></span>! <button onclick="logout()">Logout</button></p>
            <input type="text" id="symbol" placeholder="Crypto Symbol (e.g., BTC-USD)">
            <button onclick="analyze()">Analyze</button>
            <div id="result"></div>
        </div>
    </div>
    
    <script>
        // لاگین ساده (برای دمو)
        const users = {
            'investor1': 'password123',
            'investor2': 'password456'
        };
        
        function login() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error');
            
            if (users[username] && users[username] === password) {
                localStorage.setItem('user', username);
                document.getElementById('login-section').classList.add('hidden');
                document.getElementById('bot-section').classList.remove('hidden');
                document.getElementById('user-display').textContent = username;
                errorDiv.textContent = '';
            } else {
                errorDiv.textContent = 'Invalid username or password!';
            }
        }
        
        function logout() {
            localStorage.removeItem('user');
            document.getElementById('login-section').classList.remove('hidden');
            document.getElementById('bot-section').classList.add('hidden');
            document.getElementById('result').textContent = '';
        }
        
        // چک کردن لاگین موقع لود
        if (localStorage.getItem('user')) {
            document.getElementById('login-section').classList.add('hidden');
            document.getElementById('bot-section').classList.remove('hidden');
            document.getElementById('user-display').textContent = localStorage.getItem('user');
        }
        
        async function analyze() {
            const symbol = document.getElementById('symbol').value.toUpperCase();
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = 'Analyzing...';
            
            try {
                // فرض می‌کنیم یه پراکسی داریم
                // بعداً باید با PHP یا Firebase جایگزین بشه
                const quoteUrl = `https://finnhub.io/api/v1/quote?symbol=${symbol}&token=YOUR_API_KEY`;
                const histUrl = `https://finnhub.io/api/v1/crypto/candle?symbol=BINANCE:${symbol}&resolution=D&count=50&token=YOUR_API_KEY`;
                
                // برای تست، فعلاً API رو مستقیم فرض می‌کنیم (امن نیست!)
                // باید پراکسی اضافه بشه
                const quoteResponse = await fetch(quoteUrl);
                const quoteData = await quoteResponse.json();
                
                if (!quoteData.c) {
                    resultDiv.textContent = 'Invalid symbol or no data!';
                    return;
                }
                
                const histResponse = await fetch(histUrl);
                const histData = await histResponse.json();
                
                let signal = 'Neutral';
                let explanation = '';
                
                if (histData.s === 'ok') {
                    const closes = histData.c;
                    
                    function calculateMovingAverage(prices, period) {
                        const ma = [];
                        for (let i = period - 1; i < prices.length; i++) {
                            const slice = prices.slice(i - period + 1, i + 1);
                            const avg = slice.reduce((sum, p) => sum + p, 0) / period;
                            ma.push(avg);
                        }
                        return ma;
                    }
                    
                    const maShort = calculateMovingAverage(closes, 10);
                    const maLong = calculateMovingAverage(closes, 50);
                    
                    const lastShort = maShort[maShort.length - 1];
                    const lastLong = maLong[maLong.length - 1];
                    
                    if (lastShort > lastLong) {
                        signal = 'Buy';
                        explanation = 'Short-term MA is above long-term MA (Bullish trend).';
                    } else if (lastShort < lastLong) {
                        signal = 'Sell';
                        explanation = 'Short-term MA is below long-term MA (Bearish trend).';
                    } else {
                        explanation = 'No clear trend.';
                    }
                    
                    resultDiv.textContent = `
                        Current Price: $${quoteData.c.toFixed(2)}
                        Signal: ${signal}
                        Explanation: ${explanation}
                    `;
                } else {
                    resultDiv.textContent = 'Error fetching historical data!';
                }
            } catch (error) {
                resultDiv.textContent = `Error: ${error.message}`;
            }
        }
    </script>
</body>
</html>
