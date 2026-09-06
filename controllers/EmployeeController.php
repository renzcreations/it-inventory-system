<?php

namespace Controllers;

use Exception;
use Models\CatalogModel;
use Models\EmployeeModel;
use System\Core\Controller;

class EmployeeController extends Controller
{
    public function __construct(private ?EmployeeModel $employees = null)
    {
        $this->employees ??= new EmployeeModel();
    }

    public function index(): void
    {
        $catalogs = new CatalogModel();
        $this->view('pages/employee', [
            'title' => 'Employee',
            'deptFilter' => $this->employees->departments(),
            'employees' => $this->employees->allEmployees(),
            'employeeStatuses' => $catalogs->valuesByGroup('employee_statuses'),
            'workArrangements' => $catalogs->valuesByGroup('work_arrangements'),
            'departmentsCatalog' => $catalogs->valuesByGroup('departments'),
            'jobTitles' => $catalogs->valuesByGroup('job_titles'),
        ]);
    }

    public function create(): void
    {
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $firstName = $this->sanitize_input($_POST['FirstName'] ?? '', 'ucwords');
        $lastName = $this->sanitize_input($_POST['LastName'] ?? '', 'ucwords');
        $email = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $workStatus = $this->sanitize_input($_POST['WorkStatus'] ?? '', 'upper');
        $selectedDepartment = $this->sanitize_input($_POST['selectDepartment'] ?? '');
        $newDepartment = $this->sanitize_input($_POST['inputDepartment'] ?? '');
        $department = $selectedDepartment ?: $newDepartment;
        $jobTitle = $this->sanitize_input($_POST['JobTitle'] ?? '', 'ucwords');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->employeeFormError('Invalid email format.');
        }
        if ($firstName === '' || $lastName === '' || $workStatus === '' || $department === '') {
            $this->employeeFormError('All fields are required.');
        }
        if ($employeeId === '' || strtolower($employeeId) === 'n/a') {
            $employeeId = $this->nextGeneratedEmployeeId();
        }
        if ($this->employees->employeeIdExists($employeeId)) {
            $this->employeeFormError("Employee number '{$employeeId}' is already registered.");
        }

        try {
            $this->employees->createEmployee([
                'EmployeeID' => $employeeId,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Email' => $email,
                'Department' => $department,
                'JobTitle' => $jobTitle,
                'WorkStatus' => $workStatus,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            unset($_SESSION['old_input']);
            $_SESSION['success'] = "{$firstName} was registered successfully.";
        } catch (Exception $exception) {
            $_SESSION['old_input'] = $_POST;
            $_SESSION['error'] = 'Unable to register the employee: ' . $exception->getMessage();
        }
        $this->redirect('/employee');
    }

    public function store(): void
    {
        $file = $_FILES['tsv'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            $this->employeeFormError('No TSV file was uploaded.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $this->employeeFormError('The TSV file must be 5MB or smaller.');
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false || $contents === '') {
            $this->employeeFormError('The uploaded file is empty.');
        }
        $encoding = mb_detect_encoding($contents, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contents = mb_convert_encoding($contents, 'UTF-8', $encoding);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        $header = fgetcsv($stream, 0, "\t", '"', '\\');
        if (!$header || count(array_filter($header)) === 0) {
            fclose($stream);
            $this->employeeFormError('Invalid or empty TSV file.');
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $created = 0;
        $skipped = 0;
        while (($row = fgetcsv($stream, 0, "\t", '"', '\\')) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }
            $data = array_combine($header, array_map('trim', $row));
            $rawId = $data['ID Number'] ?? '';
            $employeeId = is_numeric($rawId) ? str_pad((string) $rawId, 5, '0', STR_PAD_LEFT) : '';
            if ($employeeId === '' || $this->employees->employeeIdExists($employeeId)) {
                $skipped++;
                continue;
            }

            try {
                $this->employees->createEmployee([
                    'EmployeeID' => $employeeId,
                    'FirstName' => $this->sanitize_input($data['First Name'] ?? '', 'ucwords'),
                    'LastName' => $this->sanitize_input($data['Last Name'] ?? '', 'ucwords'),
                    'Email' => $this->sanitize_input($data['Company Email Address'] ?? $data['Email Address'] ?? ''),
                    'Department' => $this->sanitize_input($data['Current Department'] ?? $data['Department'] ?? ''),
                    'JobTitle' => $this->sanitize_input($data['Job Title'] ?? $data['Position'] ?? ''),
                    'Status' => 'Active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $created++;
            } catch (Exception) {
                $skipped++;
            }
        }
        fclose($stream);

        $_SESSION['success'] = "Imported {$created} employee(s); skipped {$skipped}.";
        $this->redirect('/employee');
    }

    public function update(): void
    {
        $id = (int) ($_POST['originalID'] ?? 0);
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $firstName = $this->sanitize_input($_POST['FirstName'] ?? '', 'ucwords');
        $lastName = $this->sanitize_input($_POST['LastName'] ?? '', 'ucwords');
        $email = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $department = $this->sanitize_input($_POST['selectDepartment'] ?? '')
            ?: $this->sanitize_input($_POST['inputDepartment'] ?? '');
        $jobTitle = $this->sanitize_input($_POST['JobTitle'] ?? '', 'ucwords');
        $updatedWorkStatus = $this->sanitize_input($_POST['WorkStatus'] ?? '', 'upper');

        try {
            $this->employees->updateEmployee($id, [
                'EmployeeID' => $employeeId,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Email' => $email,
                'Department' => $department,
                'JobTitle' => $jobTitle,
                'WorkStatus' => $updatedWorkStatus,
            ], date('Y-m-d H:i:s'));
            unset($_SESSION['old_input']);
            $_SESSION['success'] = "{$firstName}'s information was updated.";
        } catch (Exception $exception) {
            $_SESSION['old_input'] = $_POST;
            $_SESSION['warning'] = 'No changes made: ' . $exception->getMessage();
        }
        $this->redirect('/employee/' . rawurlencode($employeeId));
    }

    public function show(string $employeeId): void
    {
        $employeeId = $this->sanitize_input($employeeId);
        $employee = $this->employees->findByEmployeeId($employeeId);
        $catalogs = new CatalogModel();
        $this->view('pages/partials/employee/edit', [
            'title' => 'Update Employee',
            'viewEmployee' => $employee ? [$employee] : [],
            'dept' => $this->employees->departments(),
            'departmentsCatalog' => $catalogs->valuesByGroup('departments'),
            'jobTitles' => $catalogs->valuesByGroup('job_titles'),
            'workArrangements' => $catalogs->valuesByGroup('work_arrangements'),
        ]);
    }

    public function destroy(): void
    {
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $status = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        if ($employeeId === '' || $status === '') {
            $_SESSION['warning'] = 'Employee and status are required.';
            $this->redirect('/employee');
        }

        try {
            $this->employees->resign($employeeId, $status, date('Y-m-d H:i:s'));
            $_SESSION['success'] = "{$name} was marked {$status} and all assets were returned.";
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to resign employee: ' . $exception->getMessage();
        }
        $this->redirect('/employee');
    }

    public function custody(string $employeeId): void
    {
        $employee = $this->employees->findByEmployeeId($employeeId);
        if (!$employee) {
            http_response_code(404);
            die('Employee not found');
        }

        $items = $this->employees->accessoriesByIds($this->employees->activeAccessoryIds($employeeId));
        $assignment = $this->employees->activeComputerAssignment($employeeId);
        $pcName = null;
        $parts = [];
        $assignedDate = null;
        if ($assignment) {
            $pcId = (int) $assignment['PCID'];
            $pcName = $this->employees->computerName($pcId);
            $parts = $this->employees->computerParts($pcId);
            $assignedDate = date('F d, Y', strtotime($assignment['created_at']));
        }

        $grouped = [];
        foreach ($this->employees->availableAccessories() as $accessory) {
            $grouped[$accessory['AccessoriesName']][] = $accessory;
        }
        $company = $this->employees->companyDetails();

        $this->view('pages/custody', [
            'title' => $employee['FirstName'] . ' ' . $employee['LastName'],
            'layout' => 'guest',
            'name' => $employee['FirstName'] . ' ' . $employee['LastName'],
            'PCName' => $pcName,
            'parts' => $parts,
            'status' => $employee['WorkStatus'],
            'department' => $employee['Department'],
            'assignedDate' => $assignedDate,
            'items' => $items,
            'EmployeeID' => $employeeId,
            'administrator' => $this->employees->administrator(),
            'result' => (object) ($company[0] ?? []),
            'Signature' => $employee['Signature'],
            'grouped' => $grouped,
        ]);
    }

    public function signature(): void
    {
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $authenticated = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $authenticated = $authenticated || $this->employees->matchesIdentity(
            (string) ($_SESSION['employeeID'] ?? ''),
            (string) ($_SESSION['email'] ?? '')
        );
        if (!$authenticated) {
            $_SESSION['warning'] = 'Verify your access before uploading a signature.';
            $this->redirect('/');
        }

        $file = $_FILES['signature'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
            $_SESSION['warning'] = 'Select an image no larger than 2MB.';
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) {
            $_SESSION['warning'] = 'Only JPEG, PNG, and WEBP images are allowed.';
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }

        $directory = BASE_PATH . '/Signature';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $_SESSION['error'] = 'Unable to create the signature directory.';
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }
        $filename = 'employee-' . hash('sha256', $employeeId . random_bytes(16)) . '.' . $extension;
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $_SESSION['error'] = 'Failed to upload signature.';
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }

        try {
            $this->employees->updateSignature(
                $employeeId,
                rtrim($_ENV['APP_URL'], '/') . '/Signature/' . $filename,
                date('Y-m-d H:i:s')
            );
            unset($_SESSION['employeeID'], $_SESSION['email']);
            $_SESSION['success'] = 'Signature uploaded successfully!';
        } catch (Exception $exception) {
            if (is_file($target)) {
                unlink($target);
            }
            $_SESSION['error'] = 'Unable to save the signature.';
        }
        $this->redirect('/employee/custody/' . rawurlencode($employeeId));
    }

    public function employeeAccess(): void
    {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !ctype_digit($employeeId)) {
            $_SESSION['guest_old_input'] = $_POST;
            $_SESSION['warning'] = 'Enter a valid email address and employee ID.';
            $this->redirect('/');
        }

        if ($this->employees->authenticate($email, $employeeId)) {
            unset($_SESSION['guest_old_input']);
            $_SESSION['employeeID'] = $employeeId;
            $_SESSION['email'] = $email;
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }

        $_SESSION['guest_old_input'] = $_POST;
        $_SESSION['error'] = 'The credentials do not match our records.';
        $this->redirect('/');
    }

    private function nextGeneratedEmployeeId(): string
    {
        $counter = 1;
        do {
            $id = 'NEW' . str_pad((string) $counter++, 2, '0', STR_PAD_LEFT);
        } while ($this->employees->employeeIdExists($id));
        return $id;
    }

    private function employeeFormError(string $message): never
    {
        $_SESSION['old_input'] = $_POST;
        $_SESSION['error'] = $message;
        $this->redirect('/employee');
    }
}
