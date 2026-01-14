<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);

$message = null;
$link = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $token = $auth->createResetToken($email);
        if ($token) {
            $link = '/reset.php?token=' . urlencode($token);
            $message = 'Şifre sıfırlama bağlantısı oluşturuldu. Aşağıdaki bağlantıyı kullanabilirsiniz.';
        } else {
            $message = 'Bu e-posta ile kayıtlı kullanıcı bulunamadı.';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5 mb-3">Şifre Sıfırlama</h1>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($link): ?>
                    <div class="alert alert-secondary">
                        <a href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($link); ?></a>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Bağlantı Oluştur</button>
                </form>
                <div class="mt-3">
                    <a href="/index.php">Giriş sayfasına dön</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/partials/footer.php';
