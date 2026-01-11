<?php
$pdo = Database::connection();
$user = Auth::user();

$companyFilter = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN]);
    $name = trim($_POST['name'] ?? '');
    $companyId = (int) ($_POST['company_id'] ?? 0);
    if ($name !== '' && $companyId > 0) {
        $stmt = $pdo->prepare('INSERT INTO facilities (company_id, name, location) VALUES (:company, :name, :location)');
        $stmt->execute([
            'company' => $companyId,
            'name' => $name,
            'location' => trim($_POST['location'] ?? ''),
        ]);
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $facilities = $pdo->query('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id ORDER BY c.name, f.name')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c INNER JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id JOIN user_facility_assignments ufa ON ufa.facility_id = f.id WHERE ufa.user_id = :user ORDER BY c.name, f.name');
    $stmt->execute(['user' => $user['id']]);
    $facilities = $stmt->fetchAll();
}
?>
<h2>Tesisler</h2>
<?php if ($user['role'] === Rbac::ROLE_SUPER_ADMIN): ?>
    <form class="row g-2 mb-4" method="post">
        <div class="col-md-3">
            <select class="form-select" name="company_id" required>
                <option value="">Firma seçin</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="name" placeholder="Tesis adı" required>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="location" placeholder="Konum">
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
            <th>Tesis</th>
            <th>Konum</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($facilities as $facility): ?>
            <tr>
                <td><?= htmlspecialchars($facility['company_name']) ?></td>
                <td><?= htmlspecialchars($facility['name']) ?></td>
                <td><?= htmlspecialchars($facility['location']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
