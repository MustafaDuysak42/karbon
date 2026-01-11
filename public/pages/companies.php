<?php
$pdo = Database::connection();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN]);
    $name = trim($_POST['name'] ?? '');
    $taxNumber = trim($_POST['tax_number'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare('INSERT INTO companies (name, tax_number) VALUES (:name, :tax)');
        $stmt->execute(['name' => $name, 'tax' => $taxNumber]);
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c INNER JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
}
?>
<h2>Firmalar</h2>
<?php if ($user['role'] === Rbac::ROLE_SUPER_ADMIN): ?>
    <form class="row g-2 mb-4" method="post">
        <div class="col-md-5">
            <input class="form-control" name="name" placeholder="Firma adı" required>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="tax_number" placeholder="Vergi No">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Ekle</button>
        </div>
    </form>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Vergi No</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($companies as $company): ?>
            <tr>
                <td><?= htmlspecialchars($company['name']) ?></td>
                <td><?= htmlspecialchars($company['tax_number']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
