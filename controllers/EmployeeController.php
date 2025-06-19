<?php
namespace Controllers;

use Exception;
use PDO;
use System\Core\Database;
use System\Core\Controller;

class EmployeeController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    public function index()
    {
        $employeeData = $this->db->query("SELECT *, 
        Status AS WorkStatus, 
        (Signature IS NOT NULL) AS HasSignature FROM employees ORDER BY Status ASC");

        // PDO version of "if ($employeeData->num_rows > 0)"
        if ($employeeData && $employeeData->rowCount() > 0) {
            $employees = $employeeData->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $employees = [];
        }

        $dept = $this->db->query("SELECT Department FROM employees GROUP BY Department");
        $deptFilter = $dept && $dept->rowCount() > 0 ? $dept->fetchAll(PDO::FETCH_ASSOC) : [];

        $this->view('pages/employee', [
            'title' => 'Employee',
            'deptFilter' => $deptFilter,
            'employees' => $employees,
        ]);

    }
    private function generateUniqueEmployeeID()
    {
        $counter = 1;
        while (true) {
            $newID = str_pad($counter, 2, 0, STR_PAD_LEFT);
            $newEmployeeID = "NEW$newID";

            $stmt = $this->db->query("SELECT EmployeeID FROM employees WHERE EmployeeID = ?", [$newEmployeeID]);

            if ($stmt->rowCount() === 0) {
                return $newID;
            }
            $counter++;
        }
    }
    // Manually Register an Employee with generated Employee ID if not available
    public function create()
    {
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $FirstName = $this->sanitize_input($_POST['FirstName'] ?? '', 'ucwords');
        $LastName = $this->sanitize_input($_POST['LastName'] ?? '', 'ucwords');
        $rawEmail = $this->sanitize_input($_POST['Email'] ?? '');
        $WorkStatus = $this->sanitize_input($_POST['WorkStatus'] ?? '');
        $selectDepartment = $this->sanitize_input($_POST['selectDepartment'] ?? '');
        $inputDepartment = $this->sanitize_input($_POST['inputDepartment'] ?? '');
        $date = date('Y-m-d H:i:s');

        if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /employee");
            exit();
        }

        $Email = filter_var($rawEmail, FILTER_SANITIZE_EMAIL);

        if (empty($FirstName) || empty($LastName) || empty($Email) || empty($WorkStatus)) {
            $_SESSION['error'] = 'All fields are required';
            $_SESSION['old_input'] = $_POST;
            header("Location: /employee");
            exit();
        }

        if (empty($selectDepartment) && empty($inputDepartment)) {
            $_SESSION['error'] = 'Please select or input a department.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /employee");
            exit();
        }

        if (empty($EmployeeID) || strtolower($EmployeeID) === "n/a") {
            $EmployeeID = "NEW" . $this->generateUniqueEmployeeID();
        }

        // Check if EmployeeID already exists
        $checkStmt = $this->db->query(
            "SELECT EmployeeID FROM employees WHERE EmployeeID = ?",
            [$EmployeeID]
        );
        if ($checkStmt->rowCount() > 0) {
            $_SESSION['error'] = "The employee number '$EmployeeID' is already registered for a " . strtoupper($FirstName) . ".";
            $_SESSION['old_input'] = $_POST;
            header("Location: /employee");
            exit();
        }

        $Department = !empty($selectDepartment) ? $selectDepartment : $inputDepartment;

        // Register new employee
        $register = $this->db->query(
            "INSERT INTO employees (EmployeeID, FirstName, LastName, Email, Department, WorkStatus, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$EmployeeID, $FirstName, $LastName, $Email, $Department, $WorkStatus, $date]
        );

        if ($register) {
            $_SESSION['success'] = "$FirstName is successfully registered to the system.";
        } else {
            $_SESSION['error'] = "There's an error registering $FirstName to the system, please try again.";
        }

        unset($_SESSION['old_input']);
        header("Location: /employee");
        exit();
    }


    public function store()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        header('Content-Type: text/html; charset=utf-8');

        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/employee_upload_errors.log');

        if (isset($_FILES["tsv"]) && $_FILES["tsv"]["error"] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES["tsv"]["tmp_name"];

            if (empty($fileTmpPath)) {
                $msg = 'The uploaded file is empty.';
                $_SESSION['error'] = $msg;
                error_log($msg);
                echo "<script>window.location = '/employee';</script>";
                return;
            }

            $fileContent = file_get_contents($fileTmpPath);
            $encoding = mb_detect_encoding($fileContent, 'UTF-8, ISO-8859-1, Windows-1252', true);

            if ($encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                file_put_contents($fileTmpPath, $fileContent);
            }

            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                if (fgets($handle, 4) !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                $header = fgetcsv($handle, 0, "\t", '"', '\\');

                if ($header === FALSE || count(array_filter($header)) == 0) {
                    $msg = 'Invalid or empty TSV file.';
                    $_SESSION['error'] = $msg;
                    error_log($msg);
                    echo "<script>window.location = '/employee';</script>";
                    return;
                }

                while (($row = fgetcsv($handle, 0, "\t", '"', '\\')) !== FALSE) {
                    $row = array_map('trim', $row);

                    if (empty($row) || count($row) !== count($header)) {
                        error_log("Skipping row - header/row count mismatch or empty row: " . json_encode($row));
                        continue;
                    }

                    $data = array_combine($header, $row);

                    foreach ($data as $key => $value) {
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                        }
                        $data[$key] = $this->sanitize_input($value);
                    }

                    $employee_id = isset($data['ID Number']) && is_numeric($data['ID Number']) ?
                        str_pad($data['ID Number'], 5, '0', STR_PAD_LEFT) : null;

                    if (!$employee_id) {
                        error_log("Skipping row - invalid/missing ID: " . json_encode($data));
                        continue;
                    }

                    $created_at = date('Y-m-d H:i:s');

                    // Check if employee already exists (PDO)
                    $checkStmt = $this->db->query("SELECT COUNT(*) as cnt FROM employees WHERE EmployeeID = ?", [$employee_id]);
                    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                    if ($exists) {
                        error_log("Skipping row - duplicate EmployeeID: $employee_id");
                        continue;
                    }

                    $Status = 'Active';
                    $Department = $this->sanitize_input($data['Current Department'] ?? ($data['Department'] ?? ''));
                    $Email = $this->sanitize_input($data['Company Email Address'] ?? ($data['Email Address'] ?? ''));
                    $FirstName = $this->sanitize_input($data['First Name'] ?? '', 'ucwords');
                    $LastName = $this->sanitize_input($data['Last Name'] ?? '', 'ucwords');

                    try {
                        $this->db->query(
                            "INSERT INTO employees (EmployeeID, FirstName, LastName, Email, Department, Status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [
                                $employee_id,
                                $FirstName,
                                $LastName,
                                $Email,
                                $Department,
                                $Status,
                                $created_at
                            ]
                        );
                    } catch (Exception $e) {
                        error_log("DB Insert Error for EmployeeID $employee_id: " . $e->getMessage());
                    }
                }

                fclose($handle);
                $_SESSION['success'] = 'TSV uploaded and data saved successfully!';
                echo "<script>window.location = '/employee';</script>";
                return;
            } else {
                $msg = 'Error opening file.';
                $_SESSION['error'] = $msg;
                error_log($msg);
                echo "<script>window.location = '/employee';</script>";
                return;
            }
        } else {
            $msg = 'No file uploaded or file upload error.';
            $_SESSION['error'] = $msg;
            error_log($msg);
            echo "<script>window.location = '/employee';</script>";
            return;
        }
    }
    public function update()
    {
        $date = date('Y-m-d H:i:s');
        $originalID = $this->sanitize_input($_POST['originalID'] ?? '');
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $FirstName = $this->sanitize_input($_POST['FirstName'] ?? '', 'ucwords');
        $LastName = $this->sanitize_input($_POST['LastName'] ?? '', 'ucwords');
        $Email = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $selectDepartment = $this->sanitize_input($_POST['selectDepartment'] ?? '');
        $inputDepartment = $this->sanitize_input($_POST['inputDepartment'] ?? '');

        try {
            // Get the old EmployeeID using PDO
            $stmt = $this->db->query("SELECT EmployeeID FROM employees WHERE id = ?", [$originalID]);
            $oldEmployee = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldEmployeeID = $oldEmployee['EmployeeID'] ?? null;

            $updates = [];
            $params = [];

            if (!empty($EmployeeID)) {
                $updates[] = "EmployeeID = ?";
                $params[] = $EmployeeID;
            }
            if (!empty($FirstName)) {
                $updates[] = "FirstName = ?";
                $params[] = $FirstName;
            }
            if (!empty($LastName)) {
                $updates[] = "LastName = ?";
                $params[] = $LastName;
            }
            if (!empty($Email)) {
                $updates[] = "Email = ?";
                $params[] = $Email;
            }
            if (!empty($selectDepartment) || !empty($inputDepartment)) {
                $department = !empty($selectDepartment) ? $selectDepartment : $inputDepartment;
                $updates[] = "Department = ?";
                $params[] = $department;
            }

            $updates[] = "updated_at = ?";
            $params[] = $date;

            if (!empty($updates)) {
                $updateEmployee = "UPDATE employees SET " . implode(", ", $updates) . " WHERE id = ?";
                $params[] = $originalID;

                $this->db->query("BEGIN");
                $this->db->query($updateEmployee, $params);

                $employeeIDChanged = !empty($EmployeeID) && ($EmployeeID !== $oldEmployeeID);

                if ($employeeIDChanged && $oldEmployeeID) {
                    $updateQueries = [
                        "UPDATE parts_history SET EmployeeID = ? WHERE EmployeeID = ?",
                        "UPDATE assignments SET EmployeeID = ? WHERE EmployeeID = ?",
                        "UPDATE accessories_assignments SET EmployeeID = ? WHERE EmployeeID = ?"
                    ];

                    foreach ($updateQueries as $query) {
                        $this->db->query($query, [$EmployeeID, $oldEmployeeID]);
                    }
                }

                $this->db->query("COMMIT");
                $_SESSION['success'] = "$FirstName's information updated successfully!";
                unset($_SESSION['old_input']);
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['warning'] = "No changes made. Please contact the system administrator.";
            $_SESSION['old_input'] = $_POST;
        }

        header("Location: /employee/" . urlencode($EmployeeID));
        exit();
    }

    public function show($EmployeeID)
    {
        $EmployeeID = $this->sanitize_input($EmployeeID);
        if (empty($EmployeeID)) {
            die('Invalid employee ID');
        }

        $stmt = $this->db->query("SELECT * FROM employees WHERE EmployeeID = ?", [$EmployeeID]);
        $employeeData = $stmt && $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $dept = $this->db->query("SELECT Department FROM employees GROUP BY Department");
        $deptFilter = $dept && $dept->rowCount() > 0 ? $dept->fetchAll(PDO::FETCH_ASSOC) : [];

        $this->view('pages/partials/employee/edit', [
            'title' => 'Update Employee',
            'viewEmployee' => $employeeData,
            'dept' => $deptFilter,
        ]);
    }
    // Resigned Status
    public function destroy()
    {
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $Status = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        $updated_at = $returnDate = date('Y-m-d H:i:s');
        $ReturnedStatus = 'Returned';

        if (empty($EmployeeID) || empty($Status)) {
            $_SESSION['warning'] = 'Incomplete data. Please provide EmployeeID and Status.';
            header("Location: /employee");
            exit();
        }

        // Get PC info assigned to the employee
        $pcData = $this->db->query("SELECT pc.PCID, pc.PCName FROM pcs pc LEFT JOIN assignments a ON a.PCID = pc.PCID WHERE a.EmployeeID = ?", [$EmployeeID]);
        $pcInfo = $pcData->fetch(PDO::FETCH_ASSOC);

        $PCID = $pcInfo['PCID'] ?? null;
        $PCName = $pcInfo['PCName'] ?? null;

        $this->db->query("SET SQL_BIG_SELECTS = 1");
        $this->db->query("BEGIN");

        try {
            if ($PCID) {
                // Fetch unique parts
                $parts = $this->db->query("
                SELECT DISTINCT p.PartID
                FROM pc_parts p
                INNER JOIN parts_history ph ON ph.PartID = p.PartID
                WHERE ph.EmployeeID = ? AND p.PCID = ?
            ", [$EmployeeID, $PCID])->fetchAll(PDO::FETCH_ASSOC);

                // Fetch unique accessories
                $accessories = $this->db->query("
                SELECT DISTINCT a.AccessoriesID, a.PRNumber, a.AccessoriesName
                FROM accessories_assignments aa
                INNER JOIN accessories a ON a.AccessoriesID = aa.AccessoriesID
                WHERE aa.EmployeeID = ?
            ", [$EmployeeID])->fetchAll(PDO::FETCH_ASSOC);

                // Insert parts to returned_custody
                foreach ($parts as $part) {
                    $this->db->query("
                    INSERT INTO returned_custody (EmployeeID, PCID, PCName, PartID, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ", [$EmployeeID, $PCID, $PCName, $part['PartID']]);
                }

                // Insert accessories to returned_custody
                foreach ($accessories as $accessory) {
                    $this->db->query("
                    INSERT INTO returned_custody (EmployeeID, PCID, PCName, AccessoriesID, AccessoriesPRNumber, AccessoriesName, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ", [
                        $EmployeeID,
                        $PCID,
                        $PCName,
                        $accessory['AccessoriesID'],
                        $accessory['PRNumber'] ?? 'No PR Number',
                        $accessory['AccessoriesName']
                    ]);
                }

                // Update PC/assignment/parts_history status
                $this->db->query("UPDATE assignments SET ReturnedDate = ?, Status = ?, updated_at = ? WHERE PCID = ?", [
                    $returnDate,
                    $ReturnedStatus,
                    $updated_at,
                    $PCID
                ]);

                $this->db->query("UPDATE pcs SET Status = ?, updated_at = ? WHERE PCID = ?", [
                    $ReturnedStatus,
                    $updated_at,
                    $PCID
                ]);

                $this->db->query("UPDATE parts_history SET Status = ?, updated_at = ? WHERE EmployeeID = ?", [
                    $ReturnedStatus,
                    $updated_at,
                    $EmployeeID
                ]);

                // Update accessories_assignments and accessories tables
                if (!empty($accessories)) {
                    $this->db->query("UPDATE accessories_assignments SET Status = ?, updated_at = ? WHERE EmployeeID = ?", [
                        $ReturnedStatus,
                        $updated_at,
                        $EmployeeID
                    ]);

                    foreach ($accessories as $accessory) {
                        $this->db->query("UPDATE accessories SET Qty = Qty + 1, AssignedCount = IFNULL(AssignedCount, 0) - 1, UpdatedAt = ? WHERE AccessoriesID = ?", [
                            $updated_at,
                            $accessory['AccessoriesID']
                        ]);
                    }
                }
            }

            // Update employee status
            $this->db->query("UPDATE employees SET Status = ?, updated_at = ? WHERE EmployeeID = ?", [
                $Status,
                $updated_at,
                $EmployeeID
            ]);

            $this->db->query("COMMIT");
            $_SESSION['success'] = "{$name} marked as {$Status} and all assets returned.";
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }

        header("Location: /employee");
        exit();
    }


    public function custody($EmployeeID)
    {
        if (empty($EmployeeID)) {
            die('Invalid employee ID');
        }

        $employee = $this->db->query("SELECT * FROM employees WHERE EmployeeID = ?", [$EmployeeID]);
        $data = $employee->fetch(PDO::FETCH_ASSOC);

        $name = isset($data['FirstName']) ? $data['FirstName'] . ' ' . $data['LastName'] : null;
        $status = $data['WorkStatus'] ?? null;
        $department = $data['Department'] ?? null;
        $EmployeeID = $data['EmployeeID'] ?? null;
        $Signature = $data['Signature'] ?? null;

        // Accessories
        $accessories = [];
        $accessories_stmt = $this->db->query("SELECT AccessoriesID FROM accessories_assignments WHERE EmployeeID = ? AND Status = 'Assigned'", [$EmployeeID]);
        $accessories_data = $accessories_stmt->fetchAll(PDO::FETCH_ASSOC);
        $accessories_ids = array_column($accessories_data, 'AccessoriesID');

        if (!empty($accessories_ids)) {
            $placeholders = implode(',', array_fill(0, count($accessories_ids), '?'));
            $query = "SELECT AccessoriesName, AccessoriesID, PRNumber, Brand FROM accessories WHERE AccessoriesID IN ($placeholders)";
            $stmt = $this->db->query($query, $accessories_ids);
            $accessories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Computer
        $assignedDate = null;
        $PCName = null;
        $parts = [];

        $computer_stmt = $this->db->query("SELECT PCID, created_at FROM assignments WHERE EmployeeID = ? AND Status = 'Assigned'", [$EmployeeID]);
        $computer_data = $computer_stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($computer_data)) {
            $assignedDate = date('F d, Y', strtotime($computer_data['created_at']));
            $PCID = $computer_data['PCID'];

            $computer_name = $this->db->query("SELECT PCName FROM pcs WHERE PCID = ?", [$PCID]);
            $pc_name_data = $computer_name->fetch(PDO::FETCH_ASSOC);
            $PCName = $pc_name_data['PCName'] ?? null;

            $parts_query = $this->db->query("SELECT parts.PartType, parts.Brand, parts.Model, parts.SerialNumber, parts.uniqueID
                                         FROM pc_parts
                                         INNER JOIN parts ON pc_parts.PartID = parts.PartID
                                         WHERE pc_parts.PCID = ?", [$PCID]);
            $parts = $parts_query->fetchAll(PDO::FETCH_ASSOC);
        }

        // Company and admin
        $company = $this->db->query("SELECT * FROM company_details");
        $result = $company->fetchAll(PDO::FETCH_OBJ);

        $admin = $this->db->query("SELECT * FROM users WHERE type = 'Administrator' LIMIT 1");
        $administrator = $admin->fetch(PDO::FETCH_ASSOC);

        $accessoriesStmt = $this->db->query("SELECT AccessoriesName, AccessoriesID, Brand, PRNumber, Qty FROM accessories WHERE Qty > 0");
        $allAccessories = $accessoriesStmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($allAccessories as $item) {
            $grouped[$item['AccessoriesName']][] = $item;
        }

        // View
        $this->view('pages/custody', [
            'title' => $name,
            'layout' => 'guest',
            'name' => $name,
            'PCName' => $PCName,
            'parts' => $parts,
            'status' => $status,
            'department' => $department,
            'assignedDate' => $assignedDate,
            'items' => $accessories,
            'EmployeeID' => $EmployeeID,
            'administrator' => $administrator,
            'result' => $result,
            'Signature' => $Signature,
            'grouped' => $grouped,
        ]);
    }

    public function signature()
    {
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID']);
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $employeeLoggedID = $_SESSION['employeeID'];
        $employeeLoggedEmail = $_SESSION['email'];

        if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['warning'] = 'Please select a valid image!';
            header("Location: /employee/custody/" . urldecode($EmployeeID));
            exit();
        }

        $checkID = $this->db->query("SELECT EmployeeID, Email FROM employees WHERE EmployeeID = ? AND Email = ?", [$employeeLoggedID, $employeeLoggedEmail]);
        $result = $checkID->fetch(PDO::FETCH_ASSOC);

        if (
                // If not an admin
            (!isset($_SESSION['login']) || $_SESSION['login'] !== true) &&
                // And not a verified employee
            (!isset($_SESSION['employeeID']) || !isset($_SESSION['email']) || $result['EmployeeID'] !== $employeeLoggedID || $result['Email'] !== $employeeLoggedEmail)
        ) {
            $_SESSION['warning'] = 'You do not have permission to upload a signature, please verify your access first.';
            header("Location: /");
            exit();
        }

        $file = $_FILES['signature'];
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxFileSize) {
            $_SESSION['warning'] = 'File too large. Maximum 2MB allowed.';
            header("Location: /employee/custody/" . urldecode($EmployeeID));
            exit();
        }

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
            $_SESSION['warning'] = 'Invalid file type. Only JPEG, JPG, PNG, and WEBP are allowed.';
            header("Location: /employee/custody/" . urldecode($EmployeeID));
            exit();
        }

        $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $name);
        $newFileName = $safeName . '.' . $fileExtension;
        $uploadDir = __DIR__ . '/../Signature/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['error'] = 'Failed to upload signature!';
            header("Location: /employee/custody/" . urldecode($EmployeeID));
            exit();
        }

        $relativePath = $_ENV['APP_URL'] . 'Signature/' . $newFileName;
        $date = date('Y-m-d H:i:s');

        try {
            $this->db->query('BEGIN');

            $upload = $this->db->query("UPDATE employees SET Signature = ?, signature_upload_date = ?, updated_at = ? WHERE EmployeeID = ?", [$relativePath, $date, $date, $EmployeeID]);

            $this->db->query('COMMIT');
            unset($_SESSION['employeeID'], $_SESSION['email']);
            $_SESSION['success'] = 'Signature uploaded successfully!';
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            $_SESSION['error'] = 'Error saving signature, kindly contact the system administrator for more information.';
        }

        header("Location: /employee/custody/" . urldecode($EmployeeID));
        exit();
    }

    public function employeeAccess()
    {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');

        if (empty($email) || empty($EmployeeID)) {
            $_SESSION['warning'] = 'All fields are required.';
            header("Location: /");
            exit();
        }

        if (!ctype_digit($EmployeeID)) {
            $_SESSION['warning'] = 'Invalid Employee ID.';
            $_SESSION['guest_old_input'] = $_POST;
            header("Location: /");
            exit();
        }

        $stmt = $this->db->query("SELECT * FROM employees WHERE email = ? AND EmployeeID = ?", [$email, $EmployeeID]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            unset($_SESSION['guest_old_input']);

            $_SESSION['employeeID'] = $EmployeeID;
            $_SESSION['email'] = $email;

            header("Location: /employee/custody/" . urlencode($EmployeeID));
            exit();
        }

        $_SESSION['error'] = "The credentials do not match our records.";
        $_SESSION['old_input'] = $_POST;
        header("Location: /");
        exit();
    }

}