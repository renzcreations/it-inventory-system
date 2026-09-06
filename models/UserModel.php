<?php

namespace Models;

use PDO;
use System\Core\Model;

class UserModel extends Model
{
    public function findByUsername(string $username): ?array
    {
        return $this->first('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function find(int $id): ?array
    {
        return $this->first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function markLoggedIn(string $username, string $timestamp): void
    {
        $this->execute('UPDATE users SET logged_date = ? WHERE username = ?', [$timestamp, $username]);
    }

    public function invitationByCode(string $code): ?array
    {
        return $this->first('SELECT * FROM register WHERE email_code = ?', [$code]);
    }

    public function invitationByEmail(string $email): ?array
    {
        return $this->first('SELECT * FROM register WHERE email = ?', [$email]);
    }

    public function userExists(string $email, string $username): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM users WHERE email = ? OR username = ?)',
            [$email, $username]
        );
    }

    public function emailExists(string $email): bool
    {
        return (bool) $this->scalar('SELECT EXISTS(SELECT 1 FROM users WHERE email = ?)', [$email]);
    }

    public function createUser(array $user): int
    {
        return $this->transaction(function () use ($user): int {
            $this->execute(
                'INSERT INTO users (name, email, email_code, username, password) VALUES (?, ?, ?, ?, ?)',
                [$user['name'], $user['email'], $user['email_code'], $user['username'], $user['password']]
            );
            $id = $this->lastInsertId();
            $this->execute('DELETE FROM register WHERE email_code = ?', [$user['email_code']]);
            return $id;
        });
    }

    public function recentEmployees(int $limit = 5): array
    {
        $limit = max(1, min($limit, 100));
        return $this->all("SELECT * FROM employees ORDER BY signature_upload_date DESC LIMIT {$limit}");
    }

    public function accessoryTotals(): array
    {
        return $this->all(
            'SELECT AccessoriesName, SUM(Qty) AS totalQty, SUM(AssignedCount) AS totalAssigned, '
            . 'SUM(DefectiveCount) AS totalDefective FROM accessories GROUP BY AccessoriesName'
        );
    }

    public function partStatusCounts(): array
    {
        return $this->all('SELECT Status, COUNT(*) AS count FROM parts GROUP BY Status');
    }

    public function signatureCounts(): array
    {
        return $this->all(
            "SELECT Signature, COUNT(*) AS count FROM employees WHERE Status = 'Active' GROUP BY Signature"
        );
    }

    public function companyDetails(): array
    {
        return $this->all('SELECT * FROM company_details');
    }

    public function updateProfile(string $currentUsername, array $attributes, string $updatedAt): void
    {
        $allowed = ['name', 'email', 'username'];
        $sets = [];
        $parameters = [];
        foreach ($allowed as $column) {
            if (isset($attributes[$column]) && $attributes[$column] !== '') {
                $sets[] = "{$column} = ?";
                $parameters[] = $attributes[$column];
            }
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = ?';
        $parameters[] = $updatedAt;
        $parameters[] = $currentUsername;
        $this->execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE username = ?', $parameters);
    }

    public function passwordHash(string $username): ?string
    {
        $value = $this->scalar('SELECT password FROM users WHERE username = ?', [$username]);
        return $value === false ? null : (string) $value;
    }

    public function updatePassword(string $username, string $passwordHash, string $updatedAt): void
    {
        $this->execute(
            'UPDATE users SET password = ?, updated_at = ? WHERE username = ?',
            [$passwordHash, $updatedAt, $username]
        );
    }

    public function signature(string $username): ?string
    {
        $value = $this->scalar('SELECT signature FROM users WHERE username = ?', [$username]);
        return $value === false || $value === null ? null : (string) $value;
    }

    public function updateSignature(string $username, string $path, string $updatedAt): void
    {
        $this->execute(
            'UPDATE users SET signature = ?, updated_at = ? WHERE username = ?',
            [$path, $updatedAt, $username]
        );
    }

    public function backupDate(int $userId): ?string
    {
        $value = $this->scalar('SELECT backup_date FROM users WHERE id = ?', [$userId]);
        return $value === false || $value === null ? null : (string) $value;
    }

    public function tableNames(): array
    {
        return $this->statement('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function createTableSql(string $table): string
    {
        $identifier = $this->safeIdentifier($table);
        $row = $this->statement("SHOW CREATE TABLE `{$identifier}`")->fetch(PDO::FETCH_NUM);
        return (string) ($row[1] ?? '');
    }

    public function tableRows(string $table, int $offset, int $limit): array
    {
        $identifier = $this->safeIdentifier($table);
        $offset = max(0, $offset);
        $limit = max(1, min($limit, 5000));
        return $this->all("SELECT * FROM `{$identifier}` LIMIT {$offset}, {$limit}");
    }

    public function administrator(): ?array
    {
        return $this->first("SELECT * FROM users WHERE type = 'Administrator' LIMIT 1");
    }

    public function allUsers(): array
    {
        return $this->all('SELECT * FROM users ORDER BY type ASC');
    }

    public function allInvitations(): array
    {
        return $this->all('SELECT * FROM register ORDER BY created_at ASC');
    }

    public function refreshInvitation(string $email, string $code, string $timestamp): int
    {
        return $this->execute(
            'UPDATE register SET email_code = ?, created_at = ? WHERE email = ?',
            [$code, $timestamp, $email]
        );
    }

    public function updateRole(string $username, string $role, string $updatedAt): int
    {
        return $this->execute(
            'UPDATE users SET type = ?, updated_at = ? WHERE username = ?',
            [$role, $updatedAt, $username]
        );
    }

    public function deleteInvitation(string $email): int
    {
        return $this->execute('DELETE FROM register WHERE email = ?', [$email]);
    }

    public function createInvitation(string $email, string $code, string $createdAt): int
    {
        $this->execute(
            'INSERT INTO register (email_code, email, created_at) VALUES (?, ?, ?)',
            [$code, $email, $createdAt]
        );
        return $this->lastInsertId();
    }

    private function safeIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException('Invalid SQL identifier.');
        }
        return $identifier;
    }
}
