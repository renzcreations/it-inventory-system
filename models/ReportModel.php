<?php

namespace Models;

use System\Core\Model;

class ReportModel extends Model
{
    public function templates(): array
    {
        return $this->all(
            'SELECT * FROM report_templates WHERE organization_id = ? AND is_active = 1 ORDER BY name',
            [$this->organizationId()]
        );
    }

    public function template(string $code): ?array
    {
        return $this->first(
            'SELECT * FROM report_templates WHERE organization_id = ? AND code = ? AND is_active = 1',
            [$this->organizationId(), $code]
        );
    }

    public function summary(): array
    {
        $organizationId = $this->organizationId();
        return [
            'employees' => (int) $this->scalar('SELECT COUNT(*) FROM employees WHERE organization_id = ? AND LOWER(Status) = ?', [$organizationId, 'active']),
            'parts' => (int) $this->scalar('SELECT COUNT(*) FROM parts WHERE organization_id = ?', [$organizationId]),
            'computers' => (int) $this->scalar('SELECT COUNT(*) FROM pcs WHERE organization_id = ?', [$organizationId]),
            'accessories' => (int) $this->scalar('SELECT COALESCE(SUM(Qty), 0) FROM accessories WHERE organization_id = ?', [$organizationId]),
        ];
    }

    public function data(string $code): array
    {
        $organizationId = $this->organizationId();
        return match ($code) {
            'inventory-summary' => $this->inventorySummary($organizationId),
            'asset-register' => $this->assetRegister($organizationId),
            'employee-custody' => $this->employeeCustody($organizationId),
            'defective-assets' => $this->defectiveAssets($organizationId),
            default => throw new \InvalidArgumentException('Unknown report template.'),
        };
    }

    public function logGeneration(string $code, string $fileName): void
    {
        $this->execute(
            'INSERT INTO generated_reports (organization_id, user_id, report_code, filters, file_name) VALUES (?, ?, ?, ?, ?)',
            [$this->organizationId(), $_SESSION['user_id'] ?? null, $code, '{}', $fileName]
        );
    }

    private function inventorySummary(int $organizationId): array
    {
        return $this->all(
            "SELECT 'Part' AS asset_type, PartType AS category, Status AS status, COUNT(*) AS quantity "
            . 'FROM parts WHERE organization_id = ? GROUP BY PartType, Status '
            . "UNION ALL SELECT 'Computer', Category, Status, COUNT(*) FROM pcs WHERE organization_id = ? GROUP BY Category, Status "
            . "UNION ALL SELECT 'Accessory', AccessoriesName, 'Stock', SUM(Qty) FROM accessories WHERE organization_id = ? GROUP BY AccessoriesName "
            . 'ORDER BY asset_type, category, status',
            [$organizationId, $organizationId, $organizationId]
        );
    }

    private function assetRegister(int $organizationId): array
    {
        return $this->all(
            'SELECT pc.PCID, pc.PCName, pc.Category, pc.Status, '
            . "GROUP_CONCAT(CONCAT(p.PartType, ': ', p.Brand, ' ', p.Model, ' [', p.SerialNumber, ']') ORDER BY p.PartType SEPARATOR '; ') AS installed_parts "
            . 'FROM pcs pc LEFT JOIN pc_parts pp ON pp.PCID = pc.PCID AND pp.organization_id = pc.organization_id '
            . 'LEFT JOIN parts p ON p.PartID = pp.PartID AND p.organization_id = pc.organization_id '
            . 'WHERE pc.organization_id = ? GROUP BY pc.PCID ORDER BY pc.PCName',
            [$organizationId]
        );
    }

    private function employeeCustody(int $organizationId): array
    {
        return $this->all(
            "SELECT e.EmployeeID, CONCAT(e.FirstName, ' ', e.LastName) AS employee, e.Department, e.JobTitle, "
            . 'pc.PCName, a.AssignedDate, '
            . "GROUP_CONCAT(DISTINCT ac.AccessoriesName ORDER BY ac.AccessoriesName SEPARATOR ', ') AS accessories "
            . 'FROM employees e LEFT JOIN assignments a ON a.EmployeeID = e.EmployeeID AND a.organization_id = e.organization_id AND LOWER(a.Status) = ? '
            . 'LEFT JOIN pcs pc ON pc.PCID = a.PCID AND pc.organization_id = e.organization_id '
            . 'LEFT JOIN accessories_assignments aa ON aa.EmployeeID = e.EmployeeID AND aa.organization_id = e.organization_id AND LOWER(aa.Status) = ? '
            . 'LEFT JOIN accessories ac ON ac.AccessoriesID = aa.AccessoriesID AND ac.organization_id = e.organization_id '
            . 'WHERE e.organization_id = ? AND LOWER(e.Status) = ? GROUP BY e.EmployeeID, pc.PCID, a.AssignmentID ORDER BY employee',
            ['assigned', 'assigned', $organizationId, 'active']
        );
    }

    private function defectiveAssets(int $organizationId): array
    {
        return $this->all(
            "SELECT 'Part' AS asset_type, PartType AS category, Brand, Model, SerialNumber AS reference, 1 AS quantity "
            . 'FROM parts WHERE organization_id = ? AND LOWER(Status) = ? '
            . "UNION ALL SELECT 'Accessory', AccessoriesName, Brand, '', PRNumber, DefectiveCount "
            . 'FROM accessories WHERE organization_id = ? AND DefectiveCount > 0 ORDER BY asset_type, category',
            [$organizationId, 'defective', $organizationId]
        );
    }
}
