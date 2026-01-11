<?php
$pdo = Database::connection();
$user = Auth::user();
$canEdit = in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true);
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $periodId = (int) ($_POST['period_id'] ?? 0);
    if ($companyId && $periodId && Rbac::canAccessCompany($companyId)) {
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(version), 0) + 1 FROM exports WHERE company_id = :company AND period_id = :period');
        $stmt->execute(['company' => $companyId, 'period' => $periodId]);
        $version = (int) $stmt->fetchColumn();

        $filename = sprintf('cbam_export_%d_%d_v%d.xlsx', $companyId, $periodId, $version);
        $filePath = __DIR__ . '/../../storage/exports/' . $filename;

        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $summarySheet = $spreadsheet->getActiveSheet();
            $summarySheet->setTitle('Summary_Products');

            $headers = [
                'Product ID', 'Product Name', 'Company', 'Facility', 'Period Year', 'Period Quarter',
                'Product Type', 'Production Volume', 'Production Unit', 'Direct Emissions', 'Indirect Emissions',
                'Total Emissions', 'Embedded Electricity', 'Electricity Consumption', 'Electricity Unit',
                'Fuel Consumption', 'Fuel Unit', 'Process Emissions', 'Process Unit', 'Optional Parameter',
                'Optional Value', 'Optional Unit', 'Emission Factor Source', 'Emission Factor Year',
                'Electricity EF Override', 'SEE Direct', 'SEE Indirect', 'SEE Total', 'Lineage ID',
                'Reporting Method', 'CBAM Goods', 'CN Code', 'NACE Code', 'Data Quality',
                'Verification Status', 'Verifier', 'Created By', 'Created At', 'Updated At',
                'Company Country', 'Facility Country', 'Facility City', 'Facility Region',
                'Comments',
            ];

            $summarySheet->fromArray($headers, null, 'A1');

            $stmt = $pdo->prepare('SELECT calc.*, c.name AS company_name, f.name AS facility_name, p.name AS product_name, p.product_type, per.year, per.quarter, ts.electricity_ef_override FROM calculations calc JOIN companies c ON c.id = calc.company_id JOIN facilities f ON f.id = calc.facility_id JOIN products p ON p.id = calc.product_id JOIN periods per ON per.id = calc.period_id LEFT JOIN tenant_settings ts ON ts.company_id = c.id WHERE calc.company_id = :company AND calc.period_id = :period');
            $stmt->execute(['company' => $companyId, 'period' => $periodId]);
            $rows = $stmt->fetchAll();

            $rowIndex = 2;
            foreach ($rows as $row) {
                $summarySheet->fromArray([
                    $row['product_id'],
                    $row['product_name'],
                    $row['company_name'],
                    $row['facility_name'],
                    $row['year'],
                    $row['quarter'],
                    $row['product_type'],
                    '',
                    '',
                    $row['direct_emissions'],
                    $row['indirect_emissions'],
                    $row['total_emissions'],
                    $row['embedded_electricity'],
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $row['electricity_ef_override'],
                    $row['direct_emissions'],
                    $row['indirect_emissions'],
                    $row['total_emissions'],
                    '',
                    'Activity Based',
                    '',
                    '',
                    '',
                    'Medium',
                    'Unverified',
                    '',
                    $user['name'],
                    $row['created_at'],
                    $row['updated_at'],
                    '',
                    '',
                    '',
                    '',
                    'Auto-generated',
                ], null, 'A' . $rowIndex);
                $rowIndex++;
            }

            $factorsSheet = $spreadsheet->createSheet();
            $factorsSheet->setTitle('Factors');
            $factorsSheet->fromArray(['Factor Name', 'Year', 'Source', 'Value', 'Unit'], null, 'A1');

            $lineageSheet = $spreadsheet->createSheet();
            $lineageSheet->setTitle('Lineage');
            $lineageSheet->fromArray(['Lineage ID', 'Entity', 'Reference', 'Created At'], null, 'A1');

            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);

            $hash = hash_file('sha256', $filePath);
            $stmt = $pdo->prepare('INSERT INTO exports (company_id, period_id, version, file_name, file_hash) VALUES (:company, :period, :version, :file, :hash)');
            $stmt->execute([
                'company' => $companyId,
                'period' => $periodId,
                'version' => $version,
                'file' => $filename,
                'hash' => $hash,
            ]);

            $message = 'Excel export oluşturuldu: ' . $filename;
        } else {
            $message = 'PhpSpreadsheet yüklü değil. Lütfen composer ile kurun.';
        }
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $periods = $pdo->query('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id ORDER BY p.year DESC, p.quarter DESC')->fetchAll();
    $exports = $pdo->query('SELECT e.*, c.name AS company_name FROM exports e JOIN companies c ON c.id = e.company_id ORDER BY e.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY p.year DESC, p.quarter DESC');
    $stmt->execute(['user' => $user['id']]);
    $periods = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT e.*, c.name AS company_name FROM exports e JOIN companies c ON c.id = e.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY e.created_at DESC');
    $stmt->execute(['user' => $user['id']]);
    $exports = $stmt->fetchAll();
}
?>
<h2>Excel Export</h2>
<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
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
            <button class="btn btn-primary w-100" type="submit">Export Al</button>
        </div>
    </form>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Sürüm</th>
            <th>Dosya</th>
            <th>Hash</th>
            <th>Tarih</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($exports as $export): ?>
            <tr>
                <td><?= htmlspecialchars($export['company_name']) ?></td>
                <td><?= htmlspecialchars($export['version']) ?></td>
                <td><?= htmlspecialchars($export['file_name']) ?></td>
                <td><small><?= htmlspecialchars($export['file_hash']) ?></small></td>
                <td><?= htmlspecialchars($export['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
