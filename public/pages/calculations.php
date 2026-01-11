<?php
$pdo = Database::connection();
$user = Auth::user();
$canEdit = in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $periodId = (int) ($_POST['period_id'] ?? 0);
    if ($companyId && $periodId && Rbac::canAccessCompany($companyId)) {
        $stmt = $pdo->prepare('SELECT * FROM cbam_inputs WHERE company_id = :company AND period_id = :period');
        $stmt->execute(['company' => $companyId, 'period' => $periodId]);
        $inputs = $stmt->fetchAll();

        $grouped = [];
        foreach ($inputs as $input) {
            $key = $input['product_id'] . ':' . $input['facility_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'production' => 0,
                    'electricity' => 0,
                    'fuel' => 0,
                    'process' => 0,
                ];
            }
            $data = json_decode($input['data_json'], true);
            $amount = (float) ($data['amount'] ?? 0);
            switch ($input['input_type']) {
                case 'production':
                    $grouped[$key]['production'] += $amount;
                    break;
                case 'electricity':
                    $grouped[$key]['electricity'] += $amount;
                    break;
                case 'fuel':
                    $grouped[$key]['fuel'] += $amount;
                    break;
                case 'process':
                    $grouped[$key]['process'] += $amount;
                    break;
            }
        }

        foreach ($grouped as $key => $values) {
            [$productId, $facilityId] = array_map('intval', explode(':', $key));
            $direct = $values['fuel'] + $values['process'];
            $indirect = $values['electricity'];
            $total = $direct + $indirect;
            $embedded = $values['production'] > 0 ? $values['electricity'] / $values['production'] : 0;

            $stmt = $pdo->prepare('INSERT INTO calculations (company_id, facility_id, product_id, period_id, direct_emissions, indirect_emissions, total_emissions, embedded_electricity) VALUES (:company, :facility, :product, :period, :direct, :indirect, :total, :embedded)');
            $stmt->execute([
                'company' => $companyId,
                'facility' => $facilityId,
                'product' => $productId,
                'period' => $periodId,
                'direct' => $direct,
                'indirect' => $indirect,
                'total' => $total,
                'embedded' => $embedded,
            ]);
        }
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $periods = $pdo->query('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id ORDER BY p.year DESC, p.quarter DESC')->fetchAll();
    $calculations = $pdo->query('SELECT calc.*, c.name AS company_name, f.name AS facility_name, p.name AS product_name FROM calculations calc JOIN companies c ON c.id = calc.company_id JOIN facilities f ON f.id = calc.facility_id JOIN products p ON p.id = calc.product_id ORDER BY calc.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY p.year DESC, p.quarter DESC');
    $stmt->execute(['user' => $user['id']]);
    $periods = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT calc.*, c.name AS company_name, f.name AS facility_name, p.name AS product_name FROM calculations calc JOIN companies c ON c.id = calc.company_id JOIN facilities f ON f.id = calc.facility_id JOIN products p ON p.id = calc.product_id JOIN user_facility_assignments ufa ON ufa.facility_id = f.id WHERE ufa.user_id = :user ORDER BY calc.created_at DESC');
    $stmt->execute(['user' => $user['id']]);
    $calculations = $stmt->fetchAll();
}
?>
<h2>Hesaplama</h2>
<?php if ($canEdit): ?>
    <form class="row g-2 mb-4" method="post">
        <div class="col-md-4">
            <select class="form-select" name="company_id" required>
                <option value="">Firma</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="period_id" required>
                <option value="">Dönem</option>
                <?php foreach ($periods as $period): ?>
                    <option value="<?= $period['id'] ?>"><?= htmlspecialchars($period['company_name'] . ' - ' . $period['year'] . ' Ç' . $period['quarter']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100" type="submit">Hesapla</button>
        </div>
    </form>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Tesis</th>
            <th>Ürün</th>
            <th>Direct</th>
            <th>Indirect</th>
            <th>Total</th>
            <th>Embedded Electricity</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($calculations as $calc): ?>
            <tr>
                <td><?= htmlspecialchars($calc['company_name']) ?></td>
                <td><?= htmlspecialchars($calc['facility_name']) ?></td>
                <td><?= htmlspecialchars($calc['product_name']) ?></td>
                <td><?= htmlspecialchars($calc['direct_emissions']) ?></td>
                <td><?= htmlspecialchars($calc['indirect_emissions']) ?></td>
                <td><?= htmlspecialchars($calc['total_emissions']) ?></td>
                <td><?= htmlspecialchars($calc['embedded_electricity']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
