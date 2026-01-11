<?php
$pdo = Database::connection();
$user = Auth::user();
$canEdit = in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $facilityId = (int) ($_POST['facility_id'] ?? 0);
    $productId = (int) ($_POST['product_id'] ?? 0);
    $periodId = (int) ($_POST['period_id'] ?? 0);
    $inputType = $_POST['input_type'] ?? 'production';
    if ($companyId && $facilityId && $productId && $periodId && Rbac::canAccessCompany($companyId) && Rbac::canAccessFacility($facilityId)) {
        $payload = [
            'amount' => (float) ($_POST['amount'] ?? 0),
            'unit' => $_POST['unit'] ?? '',
            'notes' => trim($_POST['notes'] ?? ''),
        ];
        if ($inputType === 'electricity') {
            $payload['grid_source'] = trim($_POST['grid_source'] ?? '');
        }
        if ($inputType === 'fuel') {
            $payload['fuel_type'] = trim($_POST['fuel_type'] ?? '');
        }
        if ($inputType === 'process') {
            $payload['process_type'] = trim($_POST['process_type'] ?? '');
        }
        if ($inputType === 'optional') {
            $payload['parameter'] = trim($_POST['parameter'] ?? '');
        }
        $stmt = $pdo->prepare('INSERT INTO cbam_inputs (company_id, facility_id, product_id, period_id, input_type, data_json, created_by) VALUES (:company, :facility, :product, :period, :type, :data, :user)');
        $stmt->execute([
            'company' => $companyId,
            'facility' => $facilityId,
            'product' => $productId,
            'period' => $periodId,
            'type' => $inputType,
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'user' => $user['id'],
        ]);
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $facilities = $pdo->query('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id ORDER BY c.name, f.name')->fetchAll();
    $products = $pdo->query('SELECT p.*, c.name AS company_name, f.name AS facility_name FROM products p JOIN companies c ON c.id = p.company_id JOIN facilities f ON f.id = p.facility_id ORDER BY p.name')->fetchAll();
    $periods = $pdo->query('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id ORDER BY p.year DESC, p.quarter DESC')->fetchAll();
    $inputs = $pdo->query('SELECT i.*, c.name AS company_name, f.name AS facility_name, p.name AS product_name FROM cbam_inputs i JOIN companies c ON c.id = i.company_id JOIN facilities f ON f.id = i.facility_id JOIN products p ON p.id = i.product_id ORDER BY i.created_at DESC')->fetchAll();
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
    $stmt = $pdo->prepare('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY p.year DESC, p.quarter DESC');
    $stmt->execute(['user' => $user['id']]);
    $periods = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT i.*, c.name AS company_name, f.name AS facility_name, p.name AS product_name FROM cbam_inputs i JOIN companies c ON c.id = i.company_id JOIN facilities f ON f.id = i.facility_id JOIN products p ON p.id = i.product_id JOIN user_facility_assignments ufa ON ufa.facility_id = f.id WHERE ufa.user_id = :user ORDER BY i.created_at DESC');
    $stmt->execute(['user' => $user['id']]);
    $inputs = $stmt->fetchAll();
}
?>
<h2>Veri Girişi</h2>
<?php if ($canEdit): ?>
    <ul class="nav nav-tabs" id="inputTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#production" type="button">Üretim</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#electricity" type="button">Elektrik</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fuel" type="button">Yakıt</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#process" type="button">Proses Emisyon</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#optional" type="button">Opsiyonel</button></li>
    </ul>
    <div class="tab-content border border-top-0 p-3 mb-4">
        <?php
        $formFields = function (string $type, array $extraFields = []) use ($companies, $facilities, $products, $periods) {
            ?>
            <form method="post" class="row g-2">
                <input type="hidden" name="input_type" value="<?= htmlspecialchars($type) ?>">
                <div class="col-md-3">
                    <select class="form-select" name="company_id" required>
                        <option value="">Firma</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="facility_id" required>
                        <option value="">Tesis</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['company_name'] . ' - ' . $facility['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="product_id" required>
                        <option value="">Ürün</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_name'] ?? $product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="period_id" required>
                        <option value="">Dönem</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>"><?= htmlspecialchars($period['company_name'] . ' - ' . $period['year'] . ' Ç' . $period['quarter']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input class="form-control" name="amount" placeholder="Miktar" required>
                </div>
                <div class="col-md-3">
                    <input class="form-control" name="unit" placeholder="Birim" required>
                </div>
                <?php foreach ($extraFields as $field): ?>
                    <div class="col-md-3">
                        <input class="form-control" name="<?= htmlspecialchars($field['name']) ?>" placeholder="<?= htmlspecialchars($field['label']) ?>">
                    </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                    <input class="form-control" name="notes" placeholder="Notlar">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Kaydet</button>
                </div>
            </form>
            <?php
        };
        ?>
        <div class="tab-pane fade show active" id="production" role="tabpanel">
            <?php $formFields('production'); ?>
        </div>
        <div class="tab-pane fade" id="electricity" role="tabpanel">
            <?php $formFields('electricity', [['name' => 'grid_source', 'label' => 'Şebeke Kaynağı']]); ?>
        </div>
        <div class="tab-pane fade" id="fuel" role="tabpanel">
            <?php $formFields('fuel', [['name' => 'fuel_type', 'label' => 'Yakıt Türü']]); ?>
        </div>
        <div class="tab-pane fade" id="process" role="tabpanel">
            <?php $formFields('process', [['name' => 'process_type', 'label' => 'Proses Tipi']]); ?>
        </div>
        <div class="tab-pane fade" id="optional" role="tabpanel">
            <?php $formFields('optional', [['name' => 'parameter', 'label' => 'Parametre']]); ?>
        </div>
    </div>
<?php endif; ?>
<h4>Son Girişler</h4>
<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Tesis</th>
            <th>Ürün</th>
            <th>Tip</th>
            <th>Veri</th>
            <th>Tarih</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inputs as $input): ?>
            <tr>
                <td><?= htmlspecialchars($input['company_name']) ?></td>
                <td><?= htmlspecialchars($input['facility_name']) ?></td>
                <td><?= htmlspecialchars($input['product_name']) ?></td>
                <td><?= htmlspecialchars($input['input_type']) ?></td>
                <td><small><?= htmlspecialchars($input['data_json']) ?></small></td>
                <td><?= htmlspecialchars($input['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
