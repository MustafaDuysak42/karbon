<?php
$pdo = Database::connection();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT]);
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $facilityId = (int) ($_POST['facility_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'STEEL';
    if ($companyId && $facilityId && $name !== '' && Rbac::canAccessCompany($companyId) && Rbac::canAccessFacility($facilityId)) {
        $stmt = $pdo->prepare('INSERT INTO products (company_id, facility_id, name, product_type) VALUES (:company, :facility, :name, :type)');
        $stmt->execute([
            'company' => $companyId,
            'facility' => $facilityId,
            'name' => $name,
            'type' => $type,
        ]);
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $facilities = $pdo->query('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id ORDER BY c.name, f.name')->fetchAll();
    $products = $pdo->query('SELECT p.*, c.name AS company_name, f.name AS facility_name FROM products p JOIN companies c ON c.id = p.company_id JOIN facilities f ON f.id = p.facility_id ORDER BY p.name')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id JOIN user_facility_assignments ufa ON ufa.facility_id = f.id WHERE ufa.user_id = :user ORDER BY c.name, f.name');
    $stmt->execute(['user' => $user['id']]);
    $facilities = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT p.*, c.name AS company_name, f.name AS facility_name FROM products p JOIN companies c ON c.id = p.company_id JOIN facilities f ON f.id = p.facility_id JOIN user_facility_assignments ufa ON ufa.facility_id = f.id WHERE ufa.user_id = :user ORDER BY p.name');
    $stmt->execute(['user' => $user['id']]);
    $products = $stmt->fetchAll();
}
?>
<h2>Ürünler</h2>
<?php if (in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true)): ?>
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
            <select class="form-select" name="facility_id" required>
                <option value="">Tesis seçin</option>
                <?php foreach ($facilities as $facility): ?>
                    <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['company_name'] . ' - ' . $facility['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" name="name" placeholder="Ürün adı" required>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="STEEL">Çelik</option>
                <option value="ALUMINUM">Alüminyum</option>
                <option value="CEMENT">Çimento</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100" type="submit">Ekle</button>
        </div>
    </form>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Tesis</th>
            <th>Ürün</th>
            <th>Tip</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['company_name']) ?></td>
                <td><?= htmlspecialchars($product['facility_name']) ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['product_type']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
