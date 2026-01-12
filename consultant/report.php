<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';
require __DIR__ . '/../classes/CreditManager.php';
require __DIR__ . '/../classes/CBAMEngine.php';
require __DIR__ . '/../classes/ExcelService.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('consultant');
$user = $auth->user();

$message = null;
$download = null;

$installationsStmt = $pdo->prepare('SELECT * FROM installations WHERE consultant_id = :consultant_id ORDER BY id DESC');
$installationsStmt->execute(['consultant_id' => $user['id']]);
$installations = $installationsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installationId = (int)($_POST['installation_id'] ?? 0);

    if (!$installationId) {
        $message = 'Lütfen bir tesis seçin.';
    } else {
        $creditManager = new CreditManager($pdo);
        if (!$creditManager->canGenerate($user['id'])) {
            $message = 'Rapor hakkınız bulunmuyor veya paket süreniz doldu.';
        } else {
            $instStmt = $pdo->prepare('SELECT * FROM installations WHERE id = :id AND consultant_id = :consultant_id');
            $instStmt->execute(['id' => $installationId, 'consultant_id' => $user['id']]);
            $installation = $instStmt->fetch();

            if (!$installation) {
                $message = 'Tesis bulunamadı.';
            } else {
                $procStmt = $pdo->prepare('SELECT * FROM processes WHERE installation_id = :installation_id');
                $procStmt->execute(['installation_id' => $installationId]);
                $processes = $procStmt->fetchAll();

                $emStmt = $pdo->prepare('SELECT * FROM emissions WHERE installation_id = :installation_id');
                $emStmt->execute(['installation_id' => $installationId]);
                $emissions = $emStmt->fetchAll();

                if (!$processes || !$emissions) {
                    $message = 'Rapor için proses ve emisyon verisi girilmelidir.';
                } else {
                    $engine = new CBAMEngine();
                    $summary = $engine->calculate($processes, $emissions);

                    try {
                        $excel = new ExcelService(__DIR__ . '/../template/cbam_clean.xlsx', __DIR__ . '/../reports');
                        $path = $excel->generateReport($installation, $processes, $summary);
                        $creditManager->consume($user['id']);
                        $download = '/reports/' . basename($path);
                        $message = 'Rapor oluşturuldu.';
                    } catch (RuntimeException $exception) {
                        $message = $exception->getMessage();
                    }
                }
            }
        }
    }
}

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Rapor Oluştur</h1>
</div>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="card shadow-sm">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Tesis Seçin</label>
                <select name="installation_id" class="form-select" required>
                    <option value="">Seçin</option>
                    <?php foreach ($installations as $inst): ?>
                        <option value="<?php echo (int)$inst['id']; ?>">
                            <?php echo htmlspecialchars($inst['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary">Rapor Oluştur</button>
        </form>
        <?php if ($download): ?>
            <hr>
            <a class="btn btn-success" href="<?php echo htmlspecialchars($download); ?>">Raporu İndir</a>
        <?php endif; ?>
    </div>
</div>
<?php
require __DIR__ . '/../partials/footer.php';
