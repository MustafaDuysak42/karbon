<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('consultant');
$user = $auth->user();

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $unlocode = trim($_POST['unlocode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $periodStart = $_POST['period_start'] ?? '';
    $periodEnd = $_POST['period_end'] ?? '';

    if ($name && $unlocode && $address && $periodStart && $periodEnd) {
        $stmt = $pdo->prepare('INSERT INTO installations (consultant_id, name, unlocode, address, period_start, period_end) VALUES (:consultant_id, :name, :unlocode, :address, :period_start, :period_end)');
        $stmt->execute([
            'consultant_id' => $user['id'],
            'name' => $name,
            'unlocode' => $unlocode,
            'address' => $address,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        $message = 'Tesis eklendi.';
    }
}

$stmt = $pdo->prepare('SELECT * FROM installations WHERE consultant_id = :consultant_id ORDER BY id DESC');
$stmt->execute(['consultant_id' => $user['id']]);
$installations = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Tesis Yönetimi</h1>
</div>
<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Yeni Tesis</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Tesis Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">UNLOCODE</label>
                        <input type="text" name="unlocode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dönem Başlangıç</label>
                            <input type="date" name="period_start" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dönem Bitiş</label>
                            <input type="date" name="period_end" class="form-control" required>
                        </div>
                    </div>
                    <button class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Tesislerim</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Dönem</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installations as $inst): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inst['name']); ?></td>
                                <td><?php echo htmlspecialchars($inst['period_start'] . ' / ' . $inst['period_end']); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="/consultant/data.php?installation_id=<?php echo (int)$inst['id']; ?>">Veri Girişi</a>
                                </td>
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
