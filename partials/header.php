<?php
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CBAM SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/index.php">CBAM SaaS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/consultants.php">Danışmanlar</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/packages.php">Paketler</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'consultant'): ?>
                        <li class="nav-item"><a class="nav-link" href="/consultant/installations.php">Tesisler</a></li>
                        <li class="nav-item"><a class="nav-link" href="/consultant/report.php">Rapor Oluştur</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?logout=1">Çıkış</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
