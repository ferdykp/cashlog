<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b0f17">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/pwa-192x192.png">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <title>{{ config('app.name', 'Cashlog') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite('resources/js/main.js')
  </head>
  <body class="bg-base-50 text-ink-900 font-body antialiased selection:bg-violet-100 selection:text-violet-600">
    <noscript>Aplikasi ini memerlukan JavaScript untuk berjalan.</noscript>
    <div id="app"></div>
  </body>
</html>
