<?php
Rbac::requireRole([Rbac::ROLE_SUPER_ADMIN]);
$pdo = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'company') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $companyId = (int) ($_POST['company_id'] ?? 0);
        if ($userId && $companyId) {
            $stmt = $pdo->prepare('INSERT INTO user_company_assignments (user_id, company_id) VALUES (:user, :company)');
            $stmt->execute(['user' => $userId, 'company' => $companyId]);
        }
    }
    if (($_POST['action'] ?? '') === 'facility') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $facilityId = (int) ($_POST['facility_id'] ?? 0);
        if ($userId && $facilityId) {
            $stmt = $pdo->prepare('INSERT INTO user_facility_assignments (user_id, facility_id) VALUES (:user, :facility)');
            $stmt->execute(['user' => $userId, 'facility' => $facilityId]);
        }
    }
}

$users = $pdo->query("SELECT * FROM users WHERE role != 'SUPER_ADMIN' ORDER BY name")->fetchAll();
$companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
$facilities = $pdo->query('SELECT f.*, c.name AS company_name FROM facilities f JOIN companies c ON c.id = f.company_id ORDER BY c.name, f.name')->fetchAll();
$companyAssignments = $pdo->query('SELECT u.name AS user_name, c.name AS company_name FROM user_company_assignments uca JOIN users u ON u.id = uca.user_id JOIN companies c ON c.id = uca.company_id ORDER BY u.name')->fetchAll();
$facilityAssignments = $pdo->query('SELECT u.name AS user_name, f.name AS facility_name, c.name AS company_name FROM user_facility_assignments ufa JOIN users u ON u.id = ufa.user_id JOIN facilities f ON f.id = ufa.facility_id JOIN companies c ON c.id = f.company_id ORDER BY u.name')->fetchAll();
?>
<h2>Yetki Atamaları</h2>
<div class="row">
    <div class="col-md-6">
        <h5>Firma Atama</h5>
        <form class="row g-2 mb-3" method="post">
            <input type="hidden" name="action" value="company">
            <div class="col-md-6">
                <select class="form-select" name="user_id" required>
                    <option value="">Kullanıcı</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <select class="form-select" name="company_id" required>
                    <option value="">Firma</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Ata</button>
            </div>
        </form>
        <ul class="list-group">
            <?php foreach ($companyAssignments as $assignment): ?>
                <li class="list-group-item"><?= htmlspecialchars($assignment['user_name'] . ' → ' . $assignment['company_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-md-6">
        <h5>Tesis Atama</h5>
        <form class="row g-2 mb-3" method="post">
            <input type="hidden" name="action" value="facility">
            <div class="col-md-6">
                <select class="form-select" name="user_id" required>
                    <option value="">Kullanıcı</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <select class="form-select" name="facility_id" required>
                    <option value="">Tesis</option>
                    <?php foreach ($facilities as $facility): ?>
                        <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['company_name'] . ' - ' . $facility['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Ata</button>
            </div>
        </form>
        <ul class="list-group">
            <?php foreach ($facilityAssignments as $assignment): ?>
                <li class="list-group-item"><?= htmlspecialchars($assignment['user_name'] . ' → ' . $assignment['company_name'] . ' / ' . $assignment['facility_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
