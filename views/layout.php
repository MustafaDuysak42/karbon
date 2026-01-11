<?php
$config = require __DIR__ . '/../config.php';
$user = Auth::user();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($config['app']['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; }
        .sidebar { min-width: 260px; }
        .content { padding: 24px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">CBAM Yönetim Paneli</a>
        <div class="d-flex text-white">
            <span class="me-3"><?= htmlspecialchars($user['name'] ?? '') ?></span>
            <a class="btn btn-outline-light btn-sm" href="/logout.php">Çıkış</a>
        </div>
    </div>
</nav>
<div class="d-flex">
    <aside class="bg-light sidebar border-end">
        <div class="list-group list-group-flush">
            <a class="list-group-item list-group-item-action" href="/?page=dashboard">Genel Bakış</a>
            <a class="list-group-item list-group-item-action" href="/?page=companies">Firmalar</a>
            <a class="list-group-item list-group-item-action" href="/?page=facilities">Tesisler</a>
            <a class="list-group-item list-group-item-action" href="/?page=products">Ürünler</a>
            <a class="list-group-item list-group-item-action" href="/?page=periods">Dönemler</a>
            <a class="list-group-item list-group-item-action" href="/?page=inputs">Veri Girişi</a>
            <a class="list-group-item list-group-item-action" href="/?page=calculations">Hesaplama</a>
            <a class="list-group-item list-group-item-action" href="/?page=export">Excel Export</a>
            <a class="list-group-item list-group-item-action" href="/?page=lineage">Lineage</a>
            <a class="list-group-item list-group-item-action" href="/?page=factors">Faktör Yönetimi</a>
            <a class="list-group-item list-group-item-action" href="/?page=users">Kullanıcılar</a>
            <a class="list-group-item list-group-item-action" href="/?page=assignments">Yetki Atamaları</a>
        </div>
    </aside>
    <main class="content flex-grow-1">
        <?= $content ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
