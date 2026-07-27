<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090d0a">
    <meta name="description" content="DayZ Manager převádí serverové XML, JSON, CFG a TXT soubory do bezpečných vizuálních editorů.">
    <link rel="icon" type="image/svg+xml" href="{{ secure_asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ secure_asset('icon-192.png') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>DayZ Manager · konfigurace serveru bez chaosu</title>
    <style>
        :root{color-scheme:dark;--bg:#090d0a;--surface:#111813;--surface-2:#172019;--line:#2e4127;--lime:#b6e94f;--lime-2:#8fc52b;--orange:#d97738;--text:#eef4ea;--muted:#a7b3a2}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(circle at 83% 0,rgba(143,197,43,.13),transparent 35rem),repeating-linear-gradient(115deg,transparent 0 90px,rgba(255,255,255,.012) 91px),var(--bg);color:var(--text);font:16px/1.55 Inter,ui-sans-serif,system-ui,sans-serif}
        a{color:inherit}.shell{width:min(1180px,calc(100% - 2rem));margin:auto}.topbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.2rem 0;border-bottom:1px solid rgba(182,233,79,.14)}.brand{font-weight:900;letter-spacing:-.04em;text-decoration:none;text-transform:uppercase}.brand span{color:var(--lime)}.topbar nav{display:flex;align-items:center;gap:1.2rem}.topbar nav a{color:var(--muted);font-size:.9rem;text-decoration:none}.button{display:inline-flex;align-items:center;justify-content:center;min-height:2.9rem;padding:.72rem 1.1rem;border:1px solid var(--lime-2);border-radius:.45rem;background:var(--lime);color:#14200f!important;font-weight:850;text-decoration:none;box-shadow:0 12px 30px rgba(143,197,43,.18)}
        .hero{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(300px,.7fr);gap:4rem;align-items:center;padding:clamp(4.5rem,10vw,8rem) 0}.kicker{margin:0 0 .8rem;color:var(--lime);font-size:.76rem;font-weight:850;letter-spacing:.18em}.hero h1{max-width:790px;margin:0;font-size:clamp(2.8rem,7vw,5.6rem);line-height:.94;letter-spacing:-.065em}.hero h1 em{color:var(--lime);font-style:normal}.lead{max-width:690px;margin:1.5rem 0;color:var(--muted);font-size:clamp(1.05rem,2vw,1.3rem)}.actions{display:flex;flex-wrap:wrap;gap:.8rem}.ghost{background:transparent;color:var(--text)!important;box-shadow:none}
        .console{position:relative;padding:1.3rem;border:1px solid var(--line);border-top:3px solid var(--lime);border-radius:.6rem;background:linear-gradient(145deg,rgba(182,233,79,.04),transparent 50%),var(--surface);box-shadow:0 35px 80px rgba(0,0,0,.36)}.console:after{position:absolute;top:-3px;right:1.4rem;width:3rem;height:3px;background:var(--orange);content:""}.console-head{display:flex;justify-content:space-between;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.13em}.console ol{display:grid;gap:.7rem;margin:1.4rem 0 0;padding:0;list-style:none}.console li{display:grid;grid-template-columns:2rem 1fr;gap:.7rem;align-items:center;padding:.8rem;border:1px solid rgba(167,179,162,.12);border-radius:.4rem;background:#0b110d}.console b{display:grid;place-items:center;width:2rem;height:2rem;border-radius:.3rem;background:rgba(182,233,79,.12);color:var(--lime)}.console span{display:block;font-weight:750}.console small{display:block;color:var(--muted)}
        .section{padding:5rem 0}.section-header{max-width:700px;margin-bottom:2rem}.section h2{margin:.3rem 0;font-size:clamp(2rem,4vw,3.3rem);letter-spacing:-.045em}.section-header p{color:var(--muted)}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.card{min-height:190px;padding:1.3rem;border:1px solid var(--line);border-radius:.55rem;background:var(--surface)}.card i{display:grid;place-items:center;width:2.3rem;height:2.3rem;border-radius:.35rem;background:rgba(182,233,79,.1);color:var(--lime);font-style:normal;font-weight:850}.card h3{margin:1rem 0 .45rem}.card p{margin:0;color:var(--muted);font-size:.92rem}.formats{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:1rem}.formats span{padding:.3rem .55rem;border:1px solid var(--line);border-radius:999px;color:#cbd7c6;font:700 .72rem ui-monospace,monospace}
        .cta{display:flex;align-items:center;justify-content:space-between;gap:2rem;margin:3rem 0 5rem;padding:2rem;border:1px solid var(--line);border-left:4px solid var(--lime);border-radius:.55rem;background:var(--surface-2)}.cta h2{margin:0 0 .3rem;font-size:1.7rem}.cta p{margin:0;color:var(--muted)}footer{padding:1.5rem 0;border-top:1px solid rgba(182,233,79,.14);color:var(--muted);font-size:.8rem}
        @media(max-width:850px){.topbar nav>a:not(.button){display:none}.hero{grid-template-columns:1fr;gap:2.5rem}.grid{grid-template-columns:1fr 1fr}.cta{align-items:flex-start;flex-direction:column}}@media(max-width:560px){.shell{width:min(100% - 1.25rem,1180px)}.topbar .button{min-height:2.5rem;padding:.55rem .75rem}.hero{padding:4rem 0}.grid{grid-template-columns:1fr}.actions .button{width:100%}.cta{padding:1.3rem}}
    </style>
</head>
<body>
<header class="shell topbar">
    <a class="brand" href="/">DayZ <span>Manager</span></a>
    <nav aria-label="Hlavní navigace">
        <a href="#funkce">Co umí</a>
        <a href="#workflow">Jak funguje</a>
        <a class="button" href="/admin">Otevřít administraci</a>
    </nav>
</header>
<main>
    <section class="shell hero">
        <div>
            <p class="kicker">SERVER CONFIGURATION WORKSPACE</p>
            <h1>Správa DayZ serveru <em>bez ručního chaosu.</em></h1>
            <p class="lead">Nahrajte původní konfiguraci, upravte ji ve srozumitelném editoru a každou změnu bezpečně uložte jako novou revizi. Raw data zůstávají vždy dostupná.</p>
            <div class="actions">
                <a class="button" href="/admin">Přejít k serverům →</a>
                <a class="button ghost" href="#workflow">Jak to funguje</a>
            </div>
        </div>
        <aside class="console" aria-label="Postup práce">
            <div class="console-head"><span>Nový server</span><span>3 kroky</span></div>
            <ol>
                <li><b>1</b><div><span>Založte server</span><small>Platforma, mapa a verze DayZ</small></div></li>
                <li><b>2</b><div><span>Nahrajte originály</span><small>XML, JSON, CFG, TXT nebo ZIP</small></div></li>
                <li><b>3</b><div><span>Upravujte bezpečně</span><small>Vizuální editor, validace a revize</small></div></li>
            </ol>
        </aside>
    </section>

    <section id="funkce" class="shell section">
        <div class="section-header">
            <p class="kicker">JEDEN PŘEHLED</p>
            <h2>Od pravidel serveru až po body na mapě</h2>
            <p>Rozhraní je uspořádané podle aktivního serveru. Každý soubor má vlastní vysvětlení, stav a odpovídající editor.</p>
        </div>
        <div class="grid">
            <article class="card"><i>01</i><h3>Vizuální konfigurace</h3><p>Formuláře, přepínače a bezpečné rozsahy místo ručního hledání atributů.</p><div class="formats"><span>XML</span><span>JSON</span><span>CFG</span><span>TXT</span></div></article>
            <article class="card"><i>02</i><h3>Mapa a spawny</h3><p>Samostatné vrstvy, světové X/Z souřadnice a zápis změn do správného zdrojového souboru.</p></article>
            <article class="card"><i>03</i><h3>Historie bez rizika</h3><p>Každé uložení vytvoří novou revizi. Původní soubor zůstane dostupný pro kontrolu i návrat.</p></article>
            <article class="card"><i>04</i><h3>Platformní kontrola</h3><p>PlayStation, Xbox a PC/Steam mají odlišné možnosti. Editor na rozdíly průběžně upozorňuje.</p></article>
            <article class="card"><i>05</i><h3>Raw data kdykoli</h3><p>Vizuální editor nezakrývá originál. Raw obsah lze zkontrolovat, kopírovat a exportovat.</p></article>
            <article class="card"><i>06</i><h3>Checklist pro začátečníky</h3><p>U každého souboru vidíte jeho přesný účel, zda už je nahraný a co udělat dál.</p></article>
        </div>
    </section>

    <section id="workflow" class="shell cta">
        <div><p class="kicker">PŘIPRAVENO K POUŽITÍ</p><h2>Začněte výběrem svého serveru.</h2><p>Průvodce naváže na existující soubory a nebude vás nutit znovu importovat to, co už máte.</p></div>
        <a class="button" href="/admin/configuration-wizard">Spustit průvodce →</a>
    </section>
</main>
<footer class="shell">DayZ Manager · bezpečná správa konfigurací pro administrátory komunitních serverů.</footer>
<script>if('serviceWorker' in navigator){addEventListener('load',()=>navigator.serviceWorker.register('/sw.js'));}</script>
</body>
</html>
