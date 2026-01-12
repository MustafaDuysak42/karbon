<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('consultant');
$user = $auth->user();

$installationId = (int)($_GET['installation_id'] ?? 0);
if (!$installationId) {
    header('Location: /consultant/installations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM installations WHERE id = :id AND consultant_id = :consultant_id');
$stmt->execute(['id' => $installationId, 'consultant_id' => $user['id']]);
$installation = $stmt->fetch();
if (!$installation) {
    header('Location: /consultant/installations.php');
    exit;
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'process') {
        $cnCode = trim($_POST['cn_code'] ?? '');
        $productionAmount = (float)($_POST['production_amount'] ?? 0);
        if ($cnCode && $productionAmount > 0) {
            $insert = $pdo->prepare('INSERT INTO processes (installation_id, cn_code, production_amount) VALUES (:installation_id, :cn_code, :production_amount)');
            $insert->execute([
                'installation_id' => $installationId,
                'cn_code' => $cnCode,
                'production_amount' => $productionAmount,
            ]);
            $message = 'Proses kaydedildi.';
        }
    }

    if ($action === 'emission') {
        $type = $_POST['type'] ?? 'direct';
        $energySource = trim($_POST['energy_source'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $efFactor = (float)($_POST['ef_factor'] ?? 0);
        if ($energySource && $amount > 0 && $efFactor > 0) {
            $insert = $pdo->prepare('INSERT INTO emissions (installation_id, type, energy_source, amount, ef_factor) VALUES (:installation_id, :type, :energy_source, :amount, :ef_factor)');
            $insert->execute([
                'installation_id' => $installationId,
                'type' => $type,
                'energy_source' => $energySource,
                'amount' => $amount,
                'ef_factor' => $efFactor,
            ]);
            $message = 'Emisyon girdisi kaydedildi.';
        }
    }
}

$processes = $pdo->prepare('SELECT * FROM processes WHERE installation_id = :installation_id');
$processes->execute(['installation_id' => $installationId]);
$processList = $processes->fetchAll();

$emissions = $pdo->prepare('SELECT * FROM emissions WHERE installation_id = :installation_id');
$emissions->execute(['installation_id' => $installationId]);
$emissionList = $emissions->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Veri Girişi - <?php echo htmlspecialchars($installation['name']); ?></h1>
    <a class="btn btn-outline-secondary" href="/consultant/installations.php">Tesislere Dön</a>
</div>
<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Proses Ekle</h2>
                <form method="post">
                    <input type="hidden" name="action" value="process">
                    <div class="mb-3">
                        <label class="form-label">CN Kodu</label>
                        <input type="text" name="cn_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Üretim Miktarı (ton)</label>
                        <input type="number" step="0.0001" name="production_amount" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Mevcut Prosesler</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>CN</th>
                            <th>Üretim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processList as $process): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($process['cn_code']); ?></td>
                                <td><?php echo number_format((float)$process['production_amount'], 4); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Emisyon Ekle</h2>
                <form method="post">
                    <input type="hidden" name="action" value="emission">
                    <div class="mb-3">
                        <label class="form-label">Tür</label>
                        <select name="type" class="form-select">
                            <option value="direct">Direct</option>
                            <option value="indirect">Indirect</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Enerji Kaynağı</label>
                        <input type="text" name="energy_source" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tüketim</label>
                        <input type="number" step="0.0001" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Emisyon Faktörü</label>
                        <input type="number" step="0.000001" name="ef_factor" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Emisyon Kayıtları</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tür</th>
                            <th>Kaynak</th>
                            <th>Tüketim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emissionList as $emission): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emission['type']); ?></td>
                                <td><?php echo htmlspecialchars($emission['energy_source']); ?></td>
                                <td><?php echo number_format((float)$emission['amount'], 4); ?></td>
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
