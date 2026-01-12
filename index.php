<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/classes/Auth.php';

$pdo = getPDO();
$auth = new Auth($pdo);

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: /index.php');
    exit;
}

$user = $auth->user();
if ($user) {
    if ($user['role'] === 'admin') {
        header('Location: /admin/consultants.php');
        exit;
    }
    header('Location: /consultant/installations.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$auth->login($email, $password)) {
        $error = 'Giriş başarısız. Lütfen bilgileri kontrol edin.';
    } else {
        header('Location: /index.php');
        exit;
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">CBAM SaaS Giriş</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="/forgot.php">Şifremi unuttum</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/partials/footer.php';
