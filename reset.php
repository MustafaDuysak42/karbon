<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);

$token = $_GET['token'] ?? '';
$message = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$token) {
        $message = 'Geçersiz sıfırlama bağlantısı.';
    } elseif ($password !== $confirm) {
        $message = 'Şifreler eşleşmiyor.';
    } elseif (strlen($password) < 6) {
        $message = 'Şifre en az 6 karakter olmalıdır.';
    } else {
        $success = $auth->resetPassword($token, $password);
        $message = $success ? 'Şifre başarıyla güncellendi.' : 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.';
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5 mb-3">Yeni Şifre Belirle</h1>
                <?php if ($message): ?>
                    <div class="alert <?php echo $success ? 'alert-success' : 'alert-warning'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <?php if (!$success): ?>
                    <form method="post">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary">Şifreyi Güncelle</button>
                    </form>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="/index.php">Giriş sayfasına dön</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/partials/footer.php';
