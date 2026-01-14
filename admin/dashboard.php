<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('admin');

$stats = [
    'consultants' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'consultant'")->fetchColumn(),
    'installations' => (int)$pdo->query('SELECT COUNT(*) FROM installations')->fetchColumn(),
    'reports' => (int)$pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn(),
];

$expiring = $pdo->query("SELECT u.email, s.expiry_date FROM subscriptions s JOIN users u ON u.id = s.user_id WHERE s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY s.expiry_date ASC")->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Admin Dashboard</h1>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Danışmanlar</div>
                <div class="display-6"><?php echo $stats['consultants']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Tesisler</div>
                <div class="display-6"><?php echo $stats['installations']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Oluşturulan Rapor</div>
                <div class="display-6"><?php echo $stats['reports']; ?></div>
            </div>
        </div>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h6 mb-3">Bitişi Yaklaşan Paketler (7 gün)</h2>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Danışman</th>
                    <th>Bitiş Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$expiring): ?>
                    <tr>
                        <td colspan="2" class="text-muted">Yaklaşan paket bulunamadı.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($expiring as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require __DIR__ . '/../partials/footer.php';
