<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bot SAP - {{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <meta http-equiv="refresh" content="3;url={{ request()->url() }}">
        <style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#18181b;color:#a1a1aa;} .msg{text-align:center;padding:2rem;}</style>
    @endif
</head>
<body class="antialiased min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <script>
      (function() {
        var t = localStorage.getItem('portal-sap-bot-theme');
        document.documentElement.classList.toggle('dark', t !== 'light');
      })();
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <div id="app"></div>
    @else
        <div class="msg">
            <p>Build do frontend não encontrado.</p>
            <p>Execute no terminal: <code>npm run build</code></p>
            <p>Ou para desenvolvimento: <code>npm run dev</code></p>
            <p>Recarregando em 3s…</p>
        </div>
    @endif
</body>
</html>
