<?php
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Auth.php';

session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::attempt($email, $password)) {
        header('Location: /');
        exit;
    }
    $error = 'Giriş başarısız. Lütfen bilgilerinizi kontrol edin.';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CBAM Yönetim Paneli - Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body">
                    <h1 class="h4 mb-3">CBAM Yönetim Paneli</h1>
                    <p class="text-muted">Lütfen giriş yapın.</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
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
                        <button class="btn btn-primary w-100" type="submit">Giriş</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
