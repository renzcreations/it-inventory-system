<?php
namespace Controllers;

use Exception;
use PDO;
use System\Core\Database;
use System\Core\Controller;

class PartsController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    private function getExcludedPartTypes()
    {
        $excludedPartTypes = ["Processor", "Motherboard", "GPU", "Keyboard", "Mouse", "Webcam", "Pen Display", "Pen Tablet", "Headset", "Power Supply"];

        $placeholders = implode(',', array_fill(0, count($excludedPartTypes), '?'));
        $sql = "SELECT DISTINCT PartType FROM temp_update_pc_parts WHERE PartType IN ($placeholders)";
        $stmt = $this->db->query($sql, $excludedPartTypes);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'PartType');
    }
    private function getAllPartsWithHistory()
    {
        return $this->db->query("SELECT 
                                        p.*
                                    FROM parts
                                    ORDER BY Status ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    private function getAvailableParts($excludedTypes, $tempPartIDs)
    {
        $excludeConditions = ["p.Status = 'Available'"];

        $excludeConditions[] = "p.PartID NOT IN (SELECT PartID FROM temp_update_pc_parts)";

        if (!empty($excludedTypes)) {
            $escapedTypes = array_map([$this, 'sanitize_input'], $excludedTypes); // FIXED
            $excludeConditions[] = "p.PartType NOT IN ('" . implode("','", $escapedTypes) . "')";
        }


        $query = " SELECT p.*,
                    COALESCE(e.FirstName, 'Unassigned') AS FirstName,
                    COALESCE(e.LastName, '') AS LastName 
                    FROM parts p
                    LEFT JOIN (
                        SELECT ph.PartID, ph.EmployeeID, ph.created_at, ph.updated_at 
                        FROM parts_history ph
                        INNER JOIN (
                            SELECT PartID, MAX(created_at) AS latest 
                            FROM parts_history 
                            GROUP BY PartID
                        ) latest_ph ON ph.PartID = latest_ph.PartID AND ph.created_at = latest_ph.latest
                    ) latest ON p.PartID = latest.PartID
                    LEFT JOIN employees e ON latest.EmployeeID = e.EmployeeID
                    WHERE " . implode(' AND ', $excludeConditions);

        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->db->query("SET SQL_BIG_SELECTS = 1");

        $types = $this->db->query("SELECT DISTINCT PartType FROM parts")->fetchAll(PDO::FETCH_ASSOC);
        $tempPart = $this->db->query("SELECT * FROM temp_update_pc_parts")->fetchAll(PDO::FETCH_ASSOC);
        $tempPartIDs = array_column($tempPart, 'PartID');
        $tempPC = $this->db->query("SELECT * FROM temp_pc LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $temp_part = $this->db->query("SELECT * FROM temporary_storage_parts")->fetchAll(PDO::FETCH_ASSOC);

        $parts = $this->db->query("
                                        SELECT p.*, 
                                        p.Status AS PartStatus, 
                                        e.FirstName, 
                                        e.LastName, 
                                        ph.Status AS HistoryStatus,
                                        pc.PCName,
                                        pp.PartID as PartsIdentification,
                                        pp.PCID
                                    FROM parts p
                                    LEFT JOIN parts_history ph 
                                        ON ph.PartID = p.PartID 
                                        AND ph.id = (SELECT MAX(id) FROM parts_history WHERE PartID = p.PartID)
                                    LEFT JOIN pc_parts pp
                                        ON pp.PartID = p.PartID
                                    LEFT JOIN pcs pc
                                        ON pc.PCID = pp.PCID
                                    LEFT JOIN employees e 
                                        ON e.EmployeeID = ph.EmployeeID 
                                    ORDER BY 
                                        CASE 
                                            WHEN ph.Status = 'Returned' THEN 1 ELSE 0 
                                        END ASC, 
                                        PartStatus ASC
                                    ");

        $parts_data = $parts->fetchAll(PDO::FETCH_ASSOC);

        $excludedTypes = $this->getExcludedPartTypes();
        $parts_available = $this->getAvailableParts($excludedTypes, $tempPartIDs);

        $this->view('pages/parts', [
            'title' => 'Parts',
            'parts_data' => $parts_data,
            'parts_available' => $parts_available,
            'excludedPartTypes' => $excludedTypes,
            'types' => $types,
            'tempPart' => $tempPart,
            'tempPartIDs' => $tempPartIDs,
            'temp_part' => $temp_part,
            'tempPC' => $tempPC
        ]);
    }

    private function generateUniqueSerialNumber($PartType)
    {
        $counter = 1;
        while (true) {
            $newSerial = str_pad($counter, 8, '0', STR_PAD_LEFT);
            $fullSerial = "NA$PartType$newSerial";
            $sql = "
            SELECT SerialNumber FROM parts WHERE SerialNumber = ?
            UNION
            SELECT SerialNumber FROM temporary_storage_parts WHERE SerialNumber = ?
        ";
            $stmt = $this->db->query($sql, [$fullSerial, $fullSerial]);
            if ($stmt->rowCount() === 0) {
                return $newSerial;
            }
            $counter++;
        }
    }

    private function generateNextUniqueID($partType)
    {
        $cleanedPartType = str_replace(' ', '', $partType);
        $pattern = '^' . preg_quote($cleanedPartType, '/') . '[0-9]+$';

        $sql = "SELECT MAX(CAST(SUBSTRING(uniqueID, LENGTH(?) + 1) AS UNSIGNED)) AS max_num 
            FROM parts 
            WHERE PartType = ? 
            AND uniqueID REGEXP ?";

        $stmt = $this->db->query($sql, [$cleanedPartType, $partType, $pattern]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $max_num = $row['max_num'] ?? 0;
        $next_num = $max_num + 1;

        return $cleanedPartType . str_pad($next_num, 5, '0', STR_PAD_LEFT);
    }

    public function create()
    {

        $PartType = $this->sanitize_input($_POST['PartType'] ?? '', 'ucwords');
        $PRNumber = $this->sanitize_input($_POST['PRNumber'] ?? '', 'upper');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $Model = $this->sanitize_input($_POST['Model'] ?? '', 'upper');
        $SerialNumber = $this->sanitize_input($_POST['SerialNumber'] ?? '', 'upper');
        $created_at = date('Y-m-d H:i:s.u');


        // Validate required fields
        if (empty($PartType)) {
            $_SESSION['warning'] = 'Please select part type';
            $_SESSION['old_input'] = $_POST;
            header("Location: /parts");
            exit();
        }

        // Set default values if needed
        if (empty($PRNumber) || $PRNumber === "N/A") {
            $PRNumber = "No Purchase Requisition Number";
        }
        if (empty($Brand) || $Brand === "N/A") {
            $Brand = "No Brand";
        }
        if (empty($Model) || $Model === "N/A") {
            $Model = "No Model";
        }

        // Generate serial number if not provided
        if (empty($SerialNumber) || $SerialNumber === "N/A") {
            $SerialNumber = "NA" . str_replace(' ', '', $PartType) . $this->generateUniqueSerialNumber(str_replace(' ', '', $PartType));
        }

        // Check for duplicate serial number
        $checkSql = "SELECT SerialNumber FROM parts WHERE SerialNumber = ? UNION SELECT SerialNumber FROM temporary_storage_parts WHERE SerialNumber = ?";
        $checkStmt = $this->db->query($checkSql, [$SerialNumber, $SerialNumber]);
        if ($checkStmt->rowCount() > 0) {
            $_SESSION['error'] = "The serial number '$SerialNumber' was already registered.";
            $_SESSION['old_input'] = $_POST;
            header("Location: /parts");
            exit();
        }

        // Insert into temporary_storage_parts
        $insertSql = "INSERT INTO temporary_storage_parts (PRNumber, PartType, Brand, Model, SerialNumber, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $result = $this->db->query($insertSql, [$PRNumber, $PartType, $Brand, $Model, $SerialNumber, $created_at]);

        if ($result) {
            $_SESSION['success'] = "$Brand $Model was successfully added!";
            unset($_SESSION['old_input']);
        } else {
            $_SESSION['error'] = "There was a problem adding $Brand $Model. Please try again.";
            $_SESSION['old_input'] = $_POST;
        }

        header("Location: /parts");
        exit();
    }

    public function store()
    {
        if (
            isset($_POST['PRNumber'], $_POST['PartType'], $_POST['Brand'], $_POST['Model'], $_POST['SerialNumber']) &&
            count($_POST['PRNumber']) === count($_POST['PartType']) &&
            count($_POST['PartType']) === count($_POST['Brand']) &&
            count($_POST['Brand']) === count($_POST['Model']) &&
            count($_POST['Model']) === count($_POST['SerialNumber'])
        ) {
            $parts = [];
            $serialNumbers = [];

            foreach ($_POST['PartType'] as $key => $value) {
                $PartType = $this->sanitize_input($value, 'upper');
                $PRNumber = $this->sanitize_input($_POST['PRNumber'][$key], 'upper');
                $Brand = $this->sanitize_input($_POST['Brand'][$key], 'ucwords');
                $Model = $this->sanitize_input($_POST['Model'][$key], 'ucwords');
                $SerialNumber = $this->sanitize_input($_POST['SerialNumber'][$key], 'upper');
                $SerialNumber = ($SerialNumber === 'N/A' || empty($SerialNumber)) ? null : $SerialNumber;

                if (empty($PRNumber) || empty($PartType) || empty($Brand) || empty($Model) || empty($SerialNumber)) {
                    $_SESSION['warning'] = 'Please fill in all fields for every item.';
                    header("Location: /parts");
                    exit();
                }

                $parts[] = [
                    'PRNumber' => $PRNumber,
                    'PartType' => $PartType,
                    'Brand' => $Brand,
                    'Model' => $Model,
                    'SerialNumber' => $SerialNumber
                ];
                $serialNumbers[] = $SerialNumber;
            }

            // Check for duplicate serial numbers in the submission
            if (count($serialNumbers) !== count(array_unique($serialNumbers))) {
                $_SESSION['warning'] = 'You entered duplicate serial numbers. Please ensure each item has a unique serial number.';
                header("Location: /parts");
                exit();
            }

            // Check for existing serial numbers in the database
            $existingSerials = [];
            foreach ($parts as $part) {
                $checkSql = "SELECT SerialNumber FROM parts WHERE PartType = ? AND SerialNumber = ?";
                $checkStmt = $this->db->query($checkSql, [$part['PartType'], $part['SerialNumber']]);
                if ($checkStmt->rowCount() > 0) {
                    $existingSerials[] = "{$part['SerialNumber']} for {$part['PartType']}";
                }
            }

            if (!empty($existingSerials)) {
                $_SESSION['error'] = 'The following serial numbers are already registered: ' . implode(', ', $existingSerials);
                header("Location: /parts");
                exit();
            }

            // Insert all parts in a transaction
            $created_at = date('Y-m-d H:i:s.u');
            $this->db->query("BEGIN");
            try {
                foreach ($parts as $part) {
                    $retries = 0;
                    $maxRetries = 5;
                    $success = false;

                    do {
                        $uniqueID = $this->sanitize_input($this->generateNextUniqueID($part['PartType']), 'upper');
                        $insertSql = "INSERT INTO parts (uniqueID, PartType, Brand, Model, SerialNumber, PRNumber, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $this->db->query($insertSql, [
                            $uniqueID,
                            $part['PartType'],
                            $part['Brand'],
                            $part['Model'],
                            $part['SerialNumber'],
                            $part['PRNumber'],
                            $created_at
                        ]);
                        if ($stmt) {
                            $success = true;
                            break;
                        } else {
                            // Check for duplicate uniqueID (race condition)
                            $errorInfo = $stmt ? $stmt->errorInfo() : [null, null, 'Unknown DB error'];
                            if (isset($errorInfo[1]) && $errorInfo[1] == 1062) {
                                $retries++;
                            } else {
                                throw new Exception($errorInfo[2] ?? 'Unknown DB error', $errorInfo[1] ?? 0);
                            }
                        }
                    } while ($retries < $maxRetries);

                    if (!$success) {
                        throw new Exception("Failed to generate unique ID for {$part['PartType']} after $maxRetries attempts.");
                    }
                }

                $this->db->query("TRUNCATE TABLE temporary_storage_parts");
                $this->db->query("COMMIT");
                $_SESSION['success'] = 'All items were successfully added!';
            } catch (Exception $e) {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = $e->getCode() === 1062
                    ? 'One or more serial numbers are already registered.'
                    : 'Something went wrong. Please try again.';
                header("Location: /parts");
                exit();
            }

            header("Location: /parts");
            exit();
        } else {
            $_SESSION['error'] = 'Invalid form submission. Please check your entries.';
            header("Location: /parts");
            exit();
        }
    }

    public function show($id)
    {
        $id = $this->sanitize_input($id);
        if (empty($id)) {
            die('Invalid employee ID');
        }

        $stmt = $this->db->query("SELECT * FROM parts WHERE PartID = ?", [$id]);
        $partData = $stmt && $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $this->view('pages/partials/parts/edit', [
            'title' => 'Update Parts',
            'viewPart' => $partData
        ]);
    }
    public function update()
    {
        $date = date('Y-m-d H:i:s');
        $PartID = $this->sanitize_input($_POST['PartID'] ?? '');
        $uniqueID = $this->sanitize_input($_POST['uniqueID'] ?? '');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '');
        $Model = $this->sanitize_input($_POST['Model'] ?? '');
        $SerialNumber = $this->sanitize_input($_POST['SerialNumber'] ?? '');

        try {
            $this->db->query("BEGIN");
            $updates = [];
            $params = [];

            if (!empty($uniqueID)) {
                $updates[] = "uniqueID = ?";
                $params[] = $uniqueID;
            }
            if (!empty($Brand)) {
                $updates[] = "Brand = ?";
                $params[] = $Brand;
            }
            if (!empty($Model)) {
                $updates[] = "Model = ?";
                $params[] = $Model;
            }
            if (!empty($SerialNumber)) {
                $updates[] = "SerialNumber = ?";
                $params[] = $SerialNumber;
            }

            $updates[] = "updated_at = ?";
            $params[] = $date;

            if (!empty($updates)) {
                $sql = "UPDATE parts SET " . implode(", ", $updates) . " WHERE PartID = ?";
                $params[] = $PartID;

                $this->db->query($sql, $params);

                $_SESSION['success'] = "$uniqueID's information updated successfully!";
            } else {
                $_SESSION['warning'] = "No changes detected.";
            }

            $this->db->query("COMMIT");
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information.";
        }

        header("Location: /parts/" . urlencode($PartID));
        exit();
    }
    public function destroy()
    {
        $id = $this->sanitize_input($_POST['id'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '');
        $status = $this->sanitize_input($_POST['Status'] ?? '');
        $updated_at = date('Y-m-d_H:i:s.u');

        try {
            $this->db->query("BEGIN");

            $result = $this->db->query(
                "UPDATE parts SET Status = ?, updated_at = ? WHERE PartID = ?",
                [$status, $updated_at, $id]
            );

            if ($result) {
                $this->db->query("COMMIT");
                $_SESSION['success'] = $name . ' status updated!';
            } else {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = 'There was a problem updating the status of ' . $name . '! Please try again!';
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'There was a problem updating the status of ' . $name . '! Please try again!';
        }

        header("Location: /parts");
        exit();
    }
}