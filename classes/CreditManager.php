<?php

declare(strict_types=1);

class CreditManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function canGenerate(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT total_reports, used_reports, expiry_date FROM subscriptions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $sub = $stmt->fetch();

        if (!$sub) {
            return false;
        }

        $remaining = (int)$sub['total_reports'] - (int)$sub['used_reports'];
        $valid = strtotime($sub['expiry_date']) >= strtotime(date('Y-m-d'));

        return $remaining > 0 && $valid;
    }

    public function consume(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE subscriptions SET used_reports = used_reports + 1 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
