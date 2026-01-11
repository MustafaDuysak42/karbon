<?php
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/Rbac.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

session_start();
Rbac::requireLogin();

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = [
    'dashboard',
    'companies',
    'facilities',
    'products',
    'periods',
    'inputs',
    'calculations',
    'export',
    'lineage',
    'factors',
    'users',
    'assignments',
];

if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    echo 'Sayfa bulunamadı.';
    exit;
}

ob_start();
require __DIR__ . '/pages/' . $page . '.php';
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
