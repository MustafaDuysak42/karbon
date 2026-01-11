<?php
Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN]);
$pdo = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? Rbac::ROLE_CUSTOMER_VIEWER;
    $password = $_POST['password'] ?? '';
    if ($name && $email && $password) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, role, password_hash, is_active) VALUES (:name, :email, :role, :hash, 1)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY name')->fetchAll();
?>
<h2>Kullanıcılar</h2>
<form class="row g-2 mb-4" method="post">
    <div class="col-md-3">
        <input class="form-control" name="name" placeholder="Ad Soyad" required>
    </div>
    <div class="col-md-3">
        <input class="form-control" type="email" name="email" placeholder="E-posta" required>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="role">
            <option value="SUPER_ADMIN">SÜPER_ADMIN</option>
            <option value="CONSULTANT">DANIŞMAN</option>
            <option value="CUSTOMER_VIEWER">MÜŞTERİ_VIEWER</option>
        </select>
    </div>
    <div class="col-md-2">
        <input class="form-control" type="password" name="password" placeholder="Şifre" required>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">Ekle</button>
    </div>
</form>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Ad</th>
            <th>E-posta</th>
            <th>Rol</th>
            <th>Durum</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td><?= $user['is_active'] ? 'Aktif' : 'Pasif' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
