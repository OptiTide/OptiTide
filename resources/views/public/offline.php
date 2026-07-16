<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline — <?= e(config('company.brand_name')) ?></title>
<meta name="theme-color" content="#0D1530">
<link rel="icon" href="/assets/img/favicon.png">
<style>
    :root { --navy:#0D1530; --brand:#FF6A00; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; display:grid; place-items:center; padding:1.5rem;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        background: linear-gradient(160deg, #1A223F, #0D1530); color:#e7eaf3; text-align:center; }
    .card { max-width:420px; }
    .chip { background:#fff; border-radius:14px; padding:10px 16px; display:inline-block; margin-bottom:1.5rem; }
    .chip img { height:44px; display:block; }
    h1 { font-size:1.5rem; margin:0 0 .5rem; }
    p { color:#b9c0d4; line-height:1.6; margin:0 0 1.5rem; }
    button { background:var(--brand); color:#fff; border:0; border-radius:10px; padding:.7rem 1.4rem; font-size:1rem; font-weight:600; cursor:pointer; }
    button:hover { filter:brightness(1.05); }
</style>
</head>
<body>
    <div class="card">
        <div class="chip"><img src="/assets/img/logo.png" alt="<?= e(config('company.brand_name')) ?>"></div>
        <h1>You're offline</h1>
        <p>It looks like you've lost your connection. Check your internet and try again — your <?= e(config('company.brand_name')) ?> dashboard will be right here.</p>
        <button onclick="location.reload()">Try again</button>
    </div>
</body>
</html>
