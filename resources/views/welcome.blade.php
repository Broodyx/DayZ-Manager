<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#84cc16">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>DayZ Manager</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b120d;color:#f4f7f2;font:16px/1.6 system-ui,sans-serif}
        main{max-width:760px;padding:4rem 2rem;text-align:center}h1{font-size:clamp(2.8rem,8vw,5.5rem);margin:0;color:#a3e635;letter-spacing:-.05em}
        p{font-size:clamp(1.1rem,3vw,1.5rem);color:#cbd5c0}a{display:inline-block;margin-top:1rem;padding:.75rem 1.25rem;border-radius:.5rem;background:#84cc16;color:#17210d;font-weight:700;text-decoration:none}
    </style>
</head>
<body>
<main>
    <h1>DayZ Manager</h1>
    <p>Správa, validace a úprava DayZ serverových konfigurací.</p>
    <a href="/admin">Přejít do administrace</a>
</main>
<script>if('serviceWorker' in navigator){addEventListener('load',()=>navigator.serviceWorker.register('/sw.js'));}</script>
</body>
</html>
