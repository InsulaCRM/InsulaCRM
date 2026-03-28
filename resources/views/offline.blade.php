<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0054a6">
    <title>Offline - InsulaCRM</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
        }

        .offline-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 3rem 2rem;
            max-width: 420px;
            width: 100%;
        }

        .offline-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .offline-icon svg {
            width: 40px;
            height: 40px;
            color: #dc2626;
        }

        .brand {
            font-size: 0.875rem;
            font-weight: 700;
            color: #0054a6;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        p {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn-retry {
            display: inline-block;
            background: #0054a6;
            color: #fff;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-retry:hover {
            background: #003d7a;
        }

        .btn-retry:active {
            transform: scale(0.98);
        }

        .footer-text {
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="brand">InsulaCRM</div>

        <div class="offline-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"></line>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                <path d="M10.71 5.05A16 16 0 0 1 22.56 9"></path>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                <line x1="12" y1="20" x2="12.01" y2="20"></line>
            </svg>
        </div>

        <h1>You're Offline</h1>
        <p>It looks like you've lost your internet connection. Please check your network settings and try again.</p>

        <button class="btn-retry" onclick="window.location.reload()">Try Again</button>
    </div>

    <div class="footer-text">InsulaCRM - Real Estate CRM</div>
</body>
</html>
