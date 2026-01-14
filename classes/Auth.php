<?php

declare(strict_types=1);

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function requireRole(string $role): void
    {
        $user = $this->user();
        if (!$user || $user['role'] !== $role) {
            header('Location: /index.php');
            exit;
        }
    }

    public function createResetToken(string $email): ?string
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $insert = $this->pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $insert->execute([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id, expires_at FROM password_resets WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return false;
        }

        if (strtotime($reset['expires_at']) < time()) {
            $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE token = :token');
            $delete->execute(['token' => $token]);
            return false;
        }

        $update = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $update->execute([
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id' => $reset['user_id'],
        ]);

        $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE token = :token');
        $delete->execute(['token' => $token]);

        return true;
    }
}
