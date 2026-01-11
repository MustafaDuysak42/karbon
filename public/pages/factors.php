<?php
$pdo = Database::connection();
$user = Auth::user();
$canEdit = in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (($_POST['action'] ?? '') === 'factor') {
        $companyId = $_POST['company_id'] !== '' ? (int) $_POST['company_id'] : null;
        if ($companyId === null && $user['role'] !== Rbac::ROLE_SUPER_ADMIN) {
            $companyId = 0;
        }
        if ($companyId === null || $companyId === 0 || Rbac::canAccessCompany($companyId)) {
            $stmt = $pdo->prepare('INSERT INTO factors (company_id, name, source, year, value, unit) VALUES (:company, :name, :source, :year, :value, :unit)');
            $stmt->execute([
                'company' => $companyId,
                'name' => trim($_POST['name'] ?? ''),
                'source' => trim($_POST['source'] ?? ''),
                'year' => (int) ($_POST['year'] ?? 0),
                'value' => (float) ($_POST['value'] ?? 0),
                'unit' => trim($_POST['unit'] ?? ''),
            ]);
        }
    }

    if (($_POST['action'] ?? '') === 'tenant') {
        $companyId = (int) ($_POST['company_id'] ?? 0);
        if ($companyId && Rbac::canAccessCompany($companyId)) {
            $stmt = $pdo->prepare('INSERT INTO tenant_settings (company_id, electricity_ef_override) VALUES (:company, :value) ON DUPLICATE KEY UPDATE electricity_ef_override = VALUES(electricity_ef_override)');
            $stmt->execute([
                'company' => $companyId,
                'value' => (float) ($_POST['electricity_ef_override'] ?? 0),
            ]);
        }
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $factors = $pdo->query('SELECT f.*, c.name AS company_name FROM factors f LEFT JOIN companies c ON c.id = f.company_id ORDER BY f.year DESC')->fetchAll();
    $settings = $pdo->query('SELECT ts.*, c.name AS company_name FROM tenant_settings ts JOIN companies c ON c.id = ts.company_id ORDER BY c.name')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT f.*, c.name AS company_name FROM factors f LEFT JOIN companies c ON c.id = f.company_id WHERE f.company_id IS NULL OR f.company_id IN (SELECT company_id FROM user_company_assignments WHERE user_id = :user) ORDER BY f.year DESC');
    $stmt->execute(['user' => $user['id']]);
    $factors = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT ts.*, c.name AS company_name FROM tenant_settings ts JOIN companies c ON c.id = ts.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $settings = $stmt->fetchAll();
}
?>
<h2>Faktör Yönetimi</h2>
<?php if ($canEdit): ?>
    <form class="row g-2 mb-4" method="post">
        <input type="hidden" name="action" value="factor">
        <div class="col-md-3">
            <input class="form-control" name="name" placeholder="Faktör adı" required>
        </div>
        <div class="col-md-2">
            <input class="form-control" name="source" placeholder="Kaynak" required>
        </div>
        <div class="col-md-2">
            <input class="form-control" type="number" name="year" placeholder="Yıl" required>
        </div>
        <div class="col-md-2">
            <input class="form-control" name="value" placeholder="Değer" required>
        </div>
        <div class="col-md-1">
            <input class="form-control" name="unit" placeholder="Birim" required>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="company_id">
                <option value="">Global</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Kaydet</button>
        </div>
    </form>

    <h5>Firma Bazlı Elektrik EF Override</h5>
    <form class="row g-2 mb-4" method="post">
        <input type="hidden" name="action" value="tenant">
        <div class="col-md-4">
            <select class="form-select" name="company_id" required>
                <option value="">Firma</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input class="form-control" name="electricity_ef_override" placeholder="Elektrik EF" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100" type="submit">Güncelle</button>
        </div>
    </form>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Faktör</th>
            <th>Kaynak</th>
            <th>Yıl</th>
            <th>Değer</th>
            <th>Birim</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($factors as $factor): ?>
            <tr>
                <td><?= htmlspecialchars($factor['company_name'] ?? 'Global') ?></td>
                <td><?= htmlspecialchars($factor['name']) ?></td>
                <td><?= htmlspecialchars($factor['source']) ?></td>
                <td><?= htmlspecialchars($factor['year']) ?></td>
                <td><?= htmlspecialchars($factor['value']) ?></td>
                <td><?= htmlspecialchars($factor['unit']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h4>Elektrik EF Override</h4>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Firma</th>
            <th>EF Değeri</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($settings as $setting): ?>
            <tr>
                <td><?= htmlspecialchars($setting['company_name']) ?></td>
                <td><?= htmlspecialchars($setting['electricity_ef_override']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
