<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('consultant');
$user = $auth->user();

$stmt = $pdo->prepare('SELECT r.id, r.file_path, r.created_at, i.name AS installation_name FROM reports r JOIN installations i ON i.id = r.installation_id WHERE r.user_id = :user_id ORDER BY r.created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$reports = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Rapor Geçmişi</h1>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tesis</th>
                    <th>Tarih</th>
                    <th>Dosya</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$reports): ?>
                    <tr>
                        <td colspan="4" class="text-muted">Henüz rapor oluşturulmadı.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($reports as $report): ?>
                    <?php $fileExists = file_exists(__DIR__ . '/..' . $report['file_path']); ?>
                    <tr>
                        <td><?php echo (int)$report['id']; ?></td>
                        <td><?php echo htmlspecialchars($report['installation_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                        <td>
                            <?php if ($fileExists): ?>
                                <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($report['file_path']); ?>">İndir</a>
                            <?php else: ?>
                                <span class="text-muted">Dosya yok</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require __DIR__ . '/../partials/footer.php';
