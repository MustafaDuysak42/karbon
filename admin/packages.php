<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('admin');

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $totalReports = (int)($_POST['total_reports'] ?? 0);
    $expiryDate = $_POST['expiry_date'] ?? '';

    if ($userId && $totalReports && $expiryDate) {
        $stmt = $pdo->prepare('SELECT id FROM subscriptions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $update = $pdo->prepare('UPDATE subscriptions SET total_reports = :total_reports, expiry_date = :expiry_date WHERE user_id = :user_id');
            $update->execute([
                'total_reports' => $totalReports,
                'expiry_date' => $expiryDate,
                'user_id' => $userId,
            ]);
        } else {
            $insert = $pdo->prepare('INSERT INTO subscriptions (user_id, total_reports, used_reports, expiry_date) VALUES (:user_id, :total_reports, 0, :expiry_date)');
            $insert->execute([
                'user_id' => $userId,
                'total_reports' => $totalReports,
                'expiry_date' => $expiryDate,
            ]);
        }
        $message = 'Paket ataması güncellendi.';
    }
}

$consultants = $pdo->query("SELECT id, email FROM users WHERE role = 'consultant' ORDER BY email")->fetchAll();
$subscriptions = $pdo->query('SELECT user_id, total_reports, used_reports, expiry_date FROM subscriptions')->fetchAll(PDO::FETCH_UNIQUE);

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Paket Tanımlama</h1>
</div>
<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Paket Ata</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Danışman</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Seçin</option>
                            <?php foreach ($consultants as $consultant): ?>
                                <option value="<?php echo (int)$consultant['id']; ?>">
                                    <?php echo htmlspecialchars($consultant['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Toplam Rapor</label>
                        <input type="number" name="total_reports" class="form-control" min="1" value="5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Aktif Paketler</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Danışman</th>
                            <th>Toplam</th>
                            <th>Kullanılan</th>
                            <th>Bitiş</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultants as $consultant): ?>
                            <?php $sub = $subscriptions[$consultant['id']] ?? null; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($consultant['email']); ?></td>
                                <td><?php echo $sub ? (int)$sub['total_reports'] : '-'; ?></td>
                                <td><?php echo $sub ? (int)$sub['used_reports'] : '-'; ?></td>
                                <td><?php echo $sub ? htmlspecialchars($sub['expiry_date']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/../partials/footer.php';
