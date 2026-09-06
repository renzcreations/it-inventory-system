<?php

namespace Models;

use System\Core\Model;

class AuditModel extends Model
{
    public function record(string $action, ?string $entityType = null, ?string $entityId = null, array $newValues = []): void
    {
        $this->execute(
            'INSERT INTO audit_logs (organization_id, user_id, action, entity_type, entity_id, new_values, ip_address, user_agent) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->organizationId(), $_SESSION['user_id'] ?? null, $action, $entityType, $entityId,
                $newValues === [] ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES),
                substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            ]
        );
    }

    public function recent(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->all(
            "SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id "
            . "WHERE a.organization_id = ? ORDER BY a.created_at DESC LIMIT {$limit}",
            [$this->organizationId()]
        );
    }
}
