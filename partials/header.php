<?php
$user = $_SESSION['user'] ?? null;
$subscriptionNotice = null;
if ($user && $user['role'] === 'consultant') {
    require_once __DIR__ . '/../config/db.php';
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT total_reports, used_reports, expiry_date FROM subscriptions WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user['id']]);
    $subscription = $stmt->fetch();
    if ($subscription) {
        $remaining = (int)$subscription['total_reports'] - (int)$subscription['used_reports'];
        $expiryDate = $subscription['expiry_date'];
        $daysLeft = (int)floor((strtotime($expiryDate) - strtotime(date('Y-m-d'))) / 86400);
        if ($daysLeft < 0 || $remaining <= 0) {
            $subscriptionNotice = [
                'type' => 'danger',
                'message' => 'Paketiniz aktif değil veya rapor hakkınız kalmadı. Lütfen admin ile iletişime geçin.',
            ];
        } elseif ($daysLeft <= 7) {
            $subscriptionNotice = [
                'type' => 'warning',
                'message' => 'Paket süresi ' . $daysLeft . ' gün içinde dolacak. Kalan rapor: ' . $remaining,
            ];
        }
    }
}
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
                        <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/consultants.php">Danışmanlar</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/packages.php">Paketler</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'consultant'): ?>
                        <li class="nav-item"><a class="nav-link" href="/consultant/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/consultant/installations.php">Tesisler</a></li>
                        <li class="nav-item"><a class="nav-link" href="/consultant/report.php">Rapor Oluştur</a></li>
                        <li class="nav-item"><a class="nav-link" href="/consultant/reports.php">Rapor Geçmişi</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/index.php?logout=1">Çıkış</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php if ($subscriptionNotice): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo htmlspecialchars($subscriptionNotice['type']); ?> mb-0">
            <?php echo htmlspecialchars($subscriptionNotice['message']); ?>
        </div>
    </div>
<?php endif; ?>
<main class="container py-4">
