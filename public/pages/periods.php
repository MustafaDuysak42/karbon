<?php
$pdo = Database::connection();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT]);
    $companyId = (int) ($_POST['company_id'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);
    $quarter = (int) ($_POST['quarter'] ?? 0);
    if ($companyId && $year && $quarter && Rbac::canAccessCompany($companyId)) {
        $stmt = $pdo->prepare('INSERT INTO periods (company_id, year, quarter) VALUES (:company, :year, :quarter)');
        $stmt->execute([
            'company' => $companyId,
            'year' => $year,
            'quarter' => $quarter,
        ]);
    }
}

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
    $periods = $pdo->query('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id ORDER BY p.year DESC, p.quarter DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT c.* FROM companies c JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY c.name');
    $stmt->execute(['user' => $user['id']]);
    $companies = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT p.*, c.name AS company_name FROM periods p JOIN companies c ON c.id = p.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY p.year DESC, p.quarter DESC');
    $stmt->execute(['user' => $user['id']]);
    $periods = $stmt->fetchAll();
}
?>
<h2>Dönemler</h2>
<?php if (in_array($user['role'], [Rbac::ROLE_SUPER_ADMIN, Rbac::ROLE_CONSULTANT], true)): ?>
    <form class="row g-2 mb-4" method="post">
        <div class="col-md-4">
            <select class="form-select" name="company_id" required>
                <option value="">Firma seçin</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" type="number" name="year" placeholder="Yıl" required>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="quarter" required>
                <option value="1">Ç1</option>
                <option value="2">Ç2</option>
                <option value="3">Ç3</option>
                <option value="4">Ç4</option>
            </select>
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
            <th>Yıl</th>
            <th>Çeyrek</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($periods as $period): ?>
            <tr>
                <td><?= htmlspecialchars($period['company_name']) ?></td>
                <td><?= htmlspecialchars($period['year']) ?></td>
                <td><?= htmlspecialchars('Ç' . $period['quarter']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
