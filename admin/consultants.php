<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);
$auth->requireRole('admin');

$message = null;
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            $message = 'Bu e-posta ile kayıtlı bir kullanıcı zaten var.';
            $messageType = 'warning';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (:email, :password, :role)');
            $stmt->execute([
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'consultant',
            ]);
            $message = 'Danışman hesabı oluşturuldu.';
            $messageType = 'success';
        }
    }
}

$consultants = $pdo->query("SELECT id, email FROM users WHERE role = 'consultant' ORDER BY id DESC")->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Danışman Yönetimi</h1>
</div>
<?php if ($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Yeni Danışman</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Geçici Şifre</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Oluştur</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Mevcut Danışmanlar</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>E-posta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultants as $consultant): ?>
                            <tr>
                                <td><?php echo (int)$consultant['id']; ?></td>
                                <td><?php echo htmlspecialchars($consultant['email']); ?></td>
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
