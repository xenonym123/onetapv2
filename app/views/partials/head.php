<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf" content="<?= e(Auth::csrfToken()) ?>">
<meta name="base" content="<?= e(url('/')) ?>">
<title><?= e(($title ?? '') !== '' ? $title . ' · 1TAP' : '1TAP') ?></title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
