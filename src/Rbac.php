<?php
class Rbac
{
    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_CONSULTANT = 'CONSULTANT';
    public const ROLE_CUSTOMER_VIEWER = 'CUSTOMER_VIEWER';

    public static function requireLogin(): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        $user = Auth::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Yetkisiz erişim.';
            exit;
        }
    }

    public static function canAccessCompany(int $companyId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return true;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT 1 FROM user_company_assignments WHERE user_id = :user AND company_id = :company');
        $stmt->execute(['user' => $user['id'], 'company' => $companyId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function canAccessFacility(int $facilityId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return true;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT 1 FROM user_facility_assignments WHERE user_id = :user AND facility_id = :facility');
        $stmt->execute(['user' => $user['id'], 'facility' => $facilityId]);
        return (bool) $stmt->fetchColumn();
    }
}
