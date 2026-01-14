<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('consultant');
$user = $auth->user();

$subscription = null;
$subStmt = $pdo->prepare('SELECT total_reports, used_reports, expiry_date FROM subscriptions WHERE user_id = :user_id');
$subStmt->execute(['user_id' => $user['id']]);
$subscription = $subStmt->fetch();

$remaining = $subscription ? (int)$subscription['total_reports'] - (int)$subscription['used_reports'] : 0;
$expiryDate = $subscription['expiry_date'] ?? null;
$daysLeft = $expiryDate ? (int)floor((strtotime($expiryDate) - strtotime(date('Y-m-d'))) / 86400) : null;

$reportCountStmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id = :user_id');
$reportCountStmt->execute(['user_id' => $user['id']]);
$reportCount = (int)$reportCountStmt->fetchColumn();

$installCountStmt = $pdo->prepare('SELECT COUNT(*) FROM installations WHERE consultant_id = :consultant_id');
$installCountStmt->execute(['consultant_id' => $user['id']]);
$installCount = (int)$installCountStmt->fetchColumn();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Danışman Paneli</h1>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Kalan Rapor</div>
                <div class="display-6"><?php echo $remaining; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Toplam Rapor</div>
                <div class="display-6"><?php echo $reportCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Tesis Sayısı</div>
                <div class="display-6"><?php echo $installCount; ?></div>
            </div>
        </div>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h6 mb-3">Paket Bilgisi</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2 text-muted">Bitiş Tarihi</div>
                <div><?php echo $expiryDate ? htmlspecialchars($expiryDate) : 'Tanımlı değil'; ?></div>
            </div>
            <div class="col-md-6">
                <div class="mb-2 text-muted">Kalan Gün</div>
                <div><?php echo $daysLeft !== null ? $daysLeft : '-'; ?></div>
            </div>
        </div>
        <hr>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="/consultant/installations.php">Tesis Yönetimi</a>
            <a class="btn btn-outline-primary" href="/consultant/report.php">Rapor Oluştur</a>
            <a class="btn btn-outline-secondary" href="/consultant/reports.php">Rapor Geçmişi</a>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/../partials/footer.php';
