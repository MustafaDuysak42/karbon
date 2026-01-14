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
$messageType = 'success';
    $editId = (int)($_GET['edit'] ?? 0);
    $editInstallation = null;
    if ($editId) {
        $editStmt = $pdo->prepare('SELECT * FROM installations WHERE id = :id AND consultant_id = :consultant_id');
        $editStmt->execute(['id' => $editId, 'consultant_id' => $user['id']]);
        $editInstallation = $editStmt->fetch();
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $unlocode = trim($_POST['unlocode'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $periodStart = $_POST['period_start'] ?? '';
        $periodEnd = $_POST['period_end'] ?? '';

        if ($name && $unlocode && $address && $periodStart && $periodEnd) {
            if ($action === 'create') {
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
            } else {
                $installId = (int)($_POST['installation_id'] ?? 0);
                $update = $pdo->prepare('UPDATE installations SET name = :name, unlocode = :unlocode, address = :address, period_start = :period_start, period_end = :period_end WHERE id = :id AND consultant_id = :consultant_id');
                $update->execute([
                    'name' => $name,
                    'unlocode' => $unlocode,
                    'address' => $address,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'id' => $installId,
                    'consultant_id' => $user['id'],
                ]);
                $message = 'Tesis güncellendi.';
                header('Location: /consultant/installations.php');
                exit;
            }
        } else {
            $message = 'Lütfen tüm alanları doldurun.';
            $messageType = 'warning';
        }
    }

    if ($action === 'delete') {
        $installId = (int)($_POST['installation_id'] ?? 0);
        if ($installId) {
            $delete = $pdo->prepare('DELETE FROM installations WHERE id = :id AND consultant_id = :consultant_id');
            $delete->execute(['id' => $installId, 'consultant_id' => $user['id']]);
            $message = 'Tesis silindi.';
        }
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
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3"><?php echo $editInstallation ? 'Tesis Güncelle' : 'Yeni Tesis'; ?></h2>
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $editInstallation ? 'update' : 'create'; ?>">
                    <?php if ($editInstallation): ?>
                        <input type="hidden" name="installation_id" value="<?php echo (int)$editInstallation['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Tesis Adı</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editInstallation['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">UNLOCODE</label>
                        <input type="text" name="unlocode" class="form-control" value="<?php echo htmlspecialchars($editInstallation['unlocode'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($editInstallation['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dönem Başlangıç</label>
                            <input type="date" name="period_start" class="form-control" value="<?php echo htmlspecialchars($editInstallation['period_start'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dönem Bitiş</label>
                            <input type="date" name="period_end" class="form-control" value="<?php echo htmlspecialchars($editInstallation['period_end'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary"><?php echo $editInstallation ? 'Güncelle' : 'Kaydet'; ?></button>
                        <?php if ($editInstallation): ?>
                            <a class="btn btn-outline-secondary" href="/consultant/installations.php">İptal</a>
                        <?php endif; ?>
                    </div>
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
                                    <a class="btn btn-sm btn-outline-secondary" href="/consultant/installations.php?edit=<?php echo (int)$inst['id']; ?>">Düzenle</a>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="installation_id" value="<?php echo (int)$inst['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu tesis silinsin mi?');">Sil</button>
                                    </form>
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
