<?php

namespace Models;

use PDO;
use System\Core\Model;

class UserModel extends Model
{
    public function recentFailedLoginCount(string $identity, string $ipAddress, int $minutes): int
    {
        $minutes = max(1, min(1440, $minutes));
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM login_attempts WHERE was_successful = 0 "
            . "AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE) "
            . 'AND (identity = ? OR ip_address = ?)',
            [$identity, $ipAddress]
        );
    }

    public function recordLoginAttempt(string $identity, string $ipAddress, bool $successful, ?int $organizationId = null): void
    {
        $this->execute(
            'INSERT INTO login_attempts (organization_id, identity, ip_address, was_successful) VALUES (?, ?, ?, ?)',
            [$organizationId, $identity, $ipAddress, (int) $successful]
        );
        if ($successful) {
            $this->execute(
                'DELETE FROM login_attempts WHERE was_successful = 0 AND identity = ? AND ip_address = ?',
                [$identity, $ipAddress]
            );
        }
    }

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
        $this->execute(
            'UPDATE users SET logged_date = ?, last_login_ip = ? WHERE username = ?',
            [$timestamp, substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), $username]
        );
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
                'INSERT INTO users (organization_id, name, email, email_code, username, password) VALUES (?, ?, ?, ?, ?, ?)',
                [$user['organization_id'], $user['name'], $user['email'], $user['email_code'], $user['username'], $user['password']]
            );
            $id = $this->lastInsertId();
            $this->execute(
                'INSERT INTO user_roles (organization_id, user_id, role_id) '
                . 'SELECT ?, ?, id FROM roles WHERE organization_id = ? AND slug = ? AND is_active = 1 LIMIT 1',
                [$user['organization_id'], $id, $user['organization_id'], 'support']
            );
            $this->execute('DELETE FROM register WHERE email_code = ?', [$user['email_code']]);
            return $id;
        });
    }

    public function recentEmployees(int $limit = 5): array
    {
        $limit = max(1, min($limit, 100));
        return $this->all("SELECT * FROM employees WHERE organization_id = ? ORDER BY signature_upload_date DESC LIMIT {$limit}", [$this->organizationId()]);
    }

    public function accessoryTotals(): array
    {
        return $this->all(
            'SELECT AccessoriesName, SUM(Qty) AS totalQty, SUM(AssignedCount) AS totalAssigned, '
            . 'SUM(DefectiveCount) AS totalDefective FROM accessories WHERE organization_id = ? GROUP BY AccessoriesName',
            [$this->organizationId()]
        );
    }

    public function partStatusCounts(): array
    {
        return $this->all('SELECT Status, COUNT(*) AS count FROM parts WHERE organization_id = ? GROUP BY Status', [$this->organizationId()]);
    }

    public function signatureCounts(): array
    {
        return $this->all(
            "SELECT Signature, COUNT(*) AS count FROM employees WHERE organization_id = ? AND LOWER(Status) = 'active' GROUP BY Signature",
            [$this->organizationId()]
        );
    }

    public function companyDetails(): array
    {
        return $this->all('SELECT * FROM company_details WHERE organization_id = ?', [$this->organizationId()]);
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
        if ($this->tableHasOrganizationColumn($identifier)) {
            return $this->all(
                "SELECT * FROM `{$identifier}` WHERE organization_id = ? LIMIT {$offset}, {$limit}",
                [$this->organizationId()]
            );
        }
        return $this->all("SELECT * FROM `{$identifier}` LIMIT {$offset}, {$limit}");
    }

    public function databaseDump(string $generatedBy, string $generatedAt): string
    {
        $dump = "-- IT Inventory System Database Backup\n";
        $dump .= "-- Generated: {$generatedAt}\n";
        $dump .= "-- Generated by: {$generatedBy}\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($this->tableNames() as $table) {
            $identifier = $this->safeIdentifier($table);
            $dump .= "--\n-- Structure for table `{$identifier}`\n--\n";
            $dump .= "DROP TABLE IF EXISTS `{$identifier}`;\n";
            $dump .= $this->createTableSql($identifier) . ";\n\n";

            $offset = 0;
            $limit = 1000;
            do {
                $rows = $this->tableRows($identifier, $offset, $limit);
                if ($rows !== [] && $offset === 0) {
                    $dump .= "--\n-- Data for table `{$identifier}`\n--\n";
                }
                foreach ($rows as $row) {
                    $columns = array_map(
                        fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
                        array_keys($row)
                    );
                    $values = array_map(
                        fn(mixed $value): string => $value === null
                            ? 'NULL'
                            : "X'" . bin2hex((string) $value) . "'",
                        array_values($row)
                    );
                    $dump .= "INSERT INTO `{$identifier}` (" . implode(', ', $columns) . ') VALUES ('
                        . implode(', ', $values) . ");\n";
                }
                $offset += $limit;
            } while (count($rows) === $limit);
            $dump .= "\n";
        }

        return $dump . "SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    public function administrator(): ?array
    {
        return $this->first("SELECT * FROM users WHERE organization_id = ? AND type = 'Administrator' LIMIT 1", [$this->organizationId()]);
    }

    public function allUsers(): array
    {
        return $this->all(
            'SELECT u.*, r.id AS role_id, r.name AS role_name FROM users u '
            . 'LEFT JOIN user_roles ur ON ur.user_id = u.id AND ur.organization_id = u.organization_id '
            . 'LEFT JOIN roles r ON r.id = ur.role_id WHERE u.organization_id = ? ORDER BY u.name',
            [$this->organizationId()]
        );
    }

    public function allInvitations(): array
    {
        return $this->all('SELECT * FROM register WHERE organization_id = ? ORDER BY created_at ASC', [$this->organizationId()]);
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
            'INSERT INTO register (organization_id, email_code, email, created_at) VALUES (?, ?, ?, ?)',
            [$this->organizationId(), $code, $email, $createdAt]
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

    private function tableHasOrganizationColumn(string $table): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?)',
            [$table, 'organization_id']
        );
    }
}
