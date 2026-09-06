<?php

namespace Controllers;

use DomainException;
use Exception;
use Models\ComputerModel;
use Models\CatalogModel;
use System\Core\Controller;
use System\Services\EmailService;

class ComputerController extends Controller
{
    public function __construct(
        private ?ComputerModel $computers = null,
        private ?EmailService $mailer = null
    ) {
        $this->computers ??= new ComputerModel();
        $this->mailer ??= new EmailService();
    }

    public function index(): void
    {
        $this->view('pages/computer', [
            'title' => 'Computer Management',
            'computers' => $this->computers->computers(),
            'tempAssignments' => $this->computers->stagedAssignments(),
            'returnedData' => $this->computers->returnedCustodySummary(),
            'computerStatuses' => (new CatalogModel())->valuesByGroup('computer_statuses'),
        ]);
    }

    public function create(): void
    {
        $employee = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $computer = $this->sanitize_input($_POST['computer'] ?? '', 'upper');
        if ($employee === '' || $computer === '') {
            $_SESSION['warning'] = 'Employee and computer are required.';
            $this->redirect('/computer');
        }

        try {
            $this->computers->stageAssignment($employee, $computer);
            $_SESSION['success'] = 'Assignment added to the staging list.';
        } catch (DomainException $exception) {
            $_SESSION['warning'] = $exception->getMessage();
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to stage the assignment.';
        }
        $this->redirect('/computer?name=' . rawurlencode($employee) . '&computer=' . rawurlencode($computer));
    }

    public function store(): void
    {
        $employeeIds = array_values(array_filter(array_map(
            fn($id) => $this->sanitize_input($id),
            is_array($_POST['EmployeeID'] ?? null) ? $_POST['EmployeeID'] : []
        )));
        $pcIds = array_values(array_filter(array_map(
            fn($id) => filter_var($id, FILTER_VALIDATE_INT),
            is_array($_POST['PCID'] ?? null) ? $_POST['PCID'] : []
        ), fn($id) => $id !== false));

        if ($employeeIds === [] || count($employeeIds) !== count($pcIds)) {
            $_SESSION['warning'] = 'The staged assignments are incomplete.';
            $this->redirect('/computer');
        }

        $timestamp = date('Y-m-d H:i:s');
        try {
            $summaries = $this->computers->storeAssignments($employeeIds, $pcIds, $timestamp);
            $_SESSION['success'] = 'Computers were assigned successfully.';
            foreach ($summaries as $summary) {
                try {
                    $this->mailer->send(
                        $summary['Email'],
                        $summary['Name'],
                        "Computer assigned: {$summary['PCName']}",
                        $this->assignmentEmail($summary, $timestamp)
                    );
                } catch (Exception $exception) {
                    error_log('Assignment email failed: ' . $exception->getMessage());
                    $_SESSION['warning'] = 'Assignments were saved, but one or more notification emails failed.';
                }
            }
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to save assignments: ' . $exception->getMessage();
        }
        $this->redirect('/computer');
    }

    public function return(): void
    {
        $pcId = (int) ($_POST['PCID'] ?? 0);
        $pcName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        if ($pcId <= 0 || $pcName === '' || $employeeId === '') {
            $_SESSION['warning'] = 'Computer return data is incomplete.';
            $this->redirect('/computer');
        }

        try {
            $this->computers->returnComputer($pcId, $pcName, $employeeId, date('Y-m-d H:i:s'));
            $_SESSION['success'] = "{$pcName} and its accessories were returned.";
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to return the computer: ' . $exception->getMessage();
        }
        $this->redirect('/computer');
    }

    public function checkPCID(): void
    {
        $pcName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        if ($pcName === '') {
            $_SESSION['warning'] = 'Provide a computer name.';
            $this->redirect('/parts');
        }

        try {
            $computer = $this->computers->stageComputerForUpdate($pcName, date('Y-m-d H:i:s'));
            $_SESSION['success'] = "{$computer['PCName']} is ready to update.";
        } catch (DomainException $exception) {
            $_SESSION['warning'] = $exception->getMessage();
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to prepare the computer update.';
        }
        $this->redirect('/parts');
    }

    public function reset(): void
    {
        try {
            $reset = $this->computers->resetUpdateStage();
            $_SESSION[$reset ? 'success' : 'warning'] = $reset
                ? 'The computer update staging area was reset.'
                : 'There is no temporary computer data to reset.';
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to reset the staging area.';
        }
        $this->redirect('/parts');
    }

    public function tempInstall(): void
    {
        $part = [
            'PartID' => (int) ($_POST['PartID'] ?? 0),
            'PartType' => $this->sanitize_input($_POST['PartType'] ?? '', 'ucwords'),
            'Brand' => $this->sanitize_input($_POST['Brand'] ?? '', 'upper'),
            'Model' => $this->sanitize_input($_POST['Model'] ?? '', 'upper'),
            'SerialNumber' => $this->sanitize_input($_POST['SerialNumber'] ?? '', 'upper'),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($part['PartID'] <= 0 || $part['PartType'] === '' || $part['Brand'] === '' || $part['Model'] === '') {
            $_SESSION['warning'] = 'Invalid part selection.';
            $this->redirect('/parts');
        }

        try {
            $this->computers->stageUpdatePart($part);
            $_SESSION['success'] = "{$part['Brand']} {$part['Model']} was selected.";
        } catch (DomainException $exception) {
            $_SESSION['warning'] = $exception->getMessage();
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to stage the part.';
        }
        $this->redirect('/parts');
    }

    public function delete(): void
    {
        $partId = (int) ($_POST['PartID'] ?? 0);
        $brand = $this->sanitize_input($_POST['Brand'] ?? '');
        try {
            $this->computers->removeStagedUpdatePart($partId);
            $_SESSION['success'] = "{$brand} was removed from the staging list.";
        } catch (Exception $exception) {
            $_SESSION['error'] = "Unable to remove {$brand}.";
        }
        $this->redirect('/parts');
    }

    public function destroy(): void
    {
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '');
        try {
            $this->computers->removeStagedAssignment($employeeId);
            $_SESSION['success'] = "{$name} was removed from the staging list.";
        } catch (Exception $exception) {
            $_SESSION['error'] = "Unable to remove {$name}.";
        }
        $this->redirect('/computer');
    }

    public function update(): void
    {
        $pcName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $partIds = array_values(array_filter(array_map(
            'intval',
            is_array($_POST['PartID'] ?? null) ? $_POST['PartID'] : []
        )));
        if ($pcName === '' || $partIds === []) {
            $_SESSION['warning'] = 'Select a computer and at least one part.';
            $this->redirect('/parts');
        }

        $timestamp = date('Y-m-d H:i:s');
        try {
            $result = $this->computers->installStagedParts($pcName, $partIds, $timestamp);
            $_SESSION['success'] = "{$pcName} was updated with " . count($partIds) . ' part(s).';
            $this->notifyPartChange($result['employee'], $pcName, $result['parts'], 'installed', $timestamp);
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to update the computer: ' . $exception->getMessage();
        }
        $this->redirect('/parts');
    }

    public function specifications(string $pcName): void
    {
        $result = $this->computers->specifications($pcName);
        if (!$result) {
            http_response_code(404);
            die('Computer not found');
        }
        $this->view('pages/specifications', [
            'title' => 'Specification of ' . $pcName,
            'data' => [$result['computer']],
            'parts' => $result['parts'],
        ]);
    }

    public function uninstall(): void
    {
        $partId = (int) ($_POST['PartID'] ?? 0);
        $brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $pcName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $partStatus = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        $historyStatus = $this->sanitize_input($_POST['HistoryStatus'] ?? '', 'ucwords');
        if ($partId <= 0 || $pcName === '' || $partStatus === '' || $historyStatus === '') {
            $_SESSION['warning'] = 'Invalid uninstall request.';
            $this->redirect('/computer/specifications/' . rawurlencode($pcName));
        }

        $timestamp = date('Y-m-d H:i:s');
        try {
            $result = $this->computers->uninstallPart($pcName, $partId, $partStatus, $historyStatus, $timestamp);
            $_SESSION['success'] = "{$brand} was uninstalled from {$pcName}.";
            $this->notifyPartChange($result['employee'], $pcName, array_filter([$result['part']]), 'uninstalled', $timestamp);
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to uninstall the part: ' . $exception->getMessage();
        }
        $this->redirect('/computer/specifications/' . rawurlencode($pcName));
    }

    public function returned(string $employeeId): void
    {
        $result = $this->computers->returnedEquipment($employeeId);
        if (!$result) {
            http_response_code(404);
            die('Returned equipment not found');
        }
        $this->view('pages/returned', [
            'title' => 'Returned Equipment',
            'PCName' => $result['custody']['PCName'],
            'parts' => $result['parts'],
            'accessories' => $result['accessories'],
        ]);
    }

    private function notifyPartChange(?array $employee, string $pcName, array $parts, string $action, string $timestamp): void
    {
        if (!$employee || empty($employee['Email'])) {
            return;
        }
        $name = $employee['FirstName'] . ' ' . $employee['LastName'];
        try {
            $this->mailer->send(
                $employee['Email'],
                $name,
                "PC parts {$action}: {$pcName}",
                $this->partChangeEmail($name, $pcName, $parts, $action, $timestamp)
            );
        } catch (Exception $exception) {
            error_log('PC update email failed: ' . $exception->getMessage());
            $_SESSION['warning'] = 'Inventory was updated, but the notification email failed.';
        }
    }

    private function assignmentEmail(array $summary, string $timestamp): string
    {
        $name = htmlspecialchars($summary['Name'], ENT_QUOTES, 'UTF-8');
        $pc = htmlspecialchars($summary['PCName'], ENT_QUOTES, 'UTF-8');
        $employeeId = htmlspecialchars($summary['EmployeeID'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars(rtrim($_ENV['APP_URL'], '/'), ENT_QUOTES, 'UTF-8');
        return "<h1>Computer assignment</h1><p>Hello {$name},</p>"
            . "<p><strong>{$pc}</strong> was assigned to you on {$timestamp}.</p>"
            . "<p>Employee ID: {$employeeId}</p><p><a href=\"{$url}\">View custody form</a></p>";
    }

    private function partChangeEmail(string $name, string $pcName, array $parts, string $action, string $timestamp): string
    {
        $items = '';
        foreach ($parts as $part) {
            $label = implode(' · ', array_filter([
                $part['PartType'] ?? null,
                $part['Brand'] ?? null,
                $part['Model'] ?? null,
                $part['SerialNumber'] ?? null,
            ]));
            $items .= '<li>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        return '<h1>Computer parts update</h1><p>Hello '
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p><p>The following part(s) were '
            . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . ' on '
            . htmlspecialchars($pcName, ENT_QUOTES, 'UTF-8') . ":</p><ul>{$items}</ul><p>{$timestamp}</p>";
    }
}
