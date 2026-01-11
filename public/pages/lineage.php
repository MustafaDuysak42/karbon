<?php
$pdo = Database::connection();
$user = Auth::user();

if ($user['role'] === Rbac::ROLE_SUPER_ADMIN) {
    $lineage = $pdo->query('SELECT l.*, c.name AS company_name FROM lineage l JOIN companies c ON c.id = l.company_id ORDER BY l.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT l.*, c.name AS company_name FROM lineage l JOIN companies c ON c.id = l.company_id JOIN user_company_assignments uca ON uca.company_id = c.id WHERE uca.user_id = :user ORDER BY l.created_at DESC');
    $stmt->execute(['user' => $user['id']]);
    $lineage = $stmt->fetchAll();
}
?>
<h2>Lineage</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Firma</th>
            <th>Entity</th>
            <th>Referans</th>
            <th>Oluşturma</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($lineage as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['company_name']) ?></td>
                <td><?= htmlspecialchars($item['entity_type']) ?></td>
                <td><?= htmlspecialchars($item['reference']) ?></td>
                <td><?= htmlspecialchars($item['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
