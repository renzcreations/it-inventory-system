<?php
namespace Controllers;

use Exception;
use PDO;
use System\Core\Database;
use System\Core\Controller;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;


class ComputerController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // display computer history data
    public function index()
    {
        try {
            $assignmentResult = $this->db->query("SELECT 
                                                        p.PCID, 
                                                        p.PCName,
                                                        a.EmployeeID,
                                                        e.FirstName,
                                                        e.LastName,
                                                        p.Status,
                                                        a.AssignmentID,
                                                        a.AssignedDate,
                                                        a.ReturnedDate
                                                    FROM pcs p
                                                    LEFT JOIN (
                                                        SELECT a1.*
                                                        FROM assignments a1
                                                        LEFT JOIN assignments a2 ON a1.PCID = a2.PCID 
                                                            AND a1.AssignedDate < a2.AssignedDate
                                                        WHERE a2.PCID IS NULL
                                                    ) a ON p.PCID = a.PCID
                                                    LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
                                                    ORDER BY p.PCID ASC
                                                ");
            $computers = $assignmentResult->fetchAll(PDO::FETCH_ASSOC);

            $tempResult = $this->db->query("SELECT t.*, e.FirstName, e.LastName, p.PCName
                                                FROM temp_assignments t
                                                LEFT JOIN employees e ON t.EmployeeID = e.EmployeeID
                                                LEFT JOIN pcs p ON t.PCID = p.PCID
                                            ");
            $tempAssignments = $tempResult->fetchAll(PDO::FETCH_ASSOC);

            $returned_custody = $this->db->query("SELECT 
                                                        r.id AS resignedID, r.PCID, r.PCName, r.PartID, r.EmployeeID, e.FirstName, e.LastName, p.PartID, r.created_at
                                                    FROM 
                                                        returned_custody r
                                                    LEFT JOIN employees e ON e.EmployeeID = r.EmployeeID
                                                    LEFT JOIN parts p ON p.PartID = r.PartID
                                                    INNER JOIN (
                                                        SELECT EmployeeID, MAX(id) as max_id
                                                        FROM returned_custody
                                                        GROUP BY EmployeeID
                                                    ) r2 ON r.EmployeeID = r2.EmployeeID AND r.id = r2.max_id
                                                    ORDER BY r.PCID DESC
                                                ");
            $returnedData = $returned_custody->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die("Query failed: " . $e->getMessage());
        }

        $this->view('pages/computer', [
            'title' => 'Computer Management',
            'computers' => $computers,
            'tempAssignments' => $tempAssignments,
            'returnedData' => $returnedData
        ]);
    }

    // get the pcid and employee to store to temporary table
    public function create()
    {
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $computer = $this->sanitize_input($_POST['computer'] ?? '', 'upper');

        if (empty($name) || empty($computer)) {
            $_SESSION['warning'] = 'Please fill in all fields.';
            header("Location: /computer");
            exit();
        }

        try {
            $this->db->query("BEGIN");

            // Find employee by name or ID
            $employeeResult = $this->db->query(
                "SELECT EmployeeID FROM employees WHERE (FirstName = ? OR LastName = ? OR EmployeeID = ?) AND Status != 'Resigned'",
                [$name, $name, $name]
            );
            $employeeRow = $employeeResult->fetch(PDO::FETCH_ASSOC);

            // Find computer by name or ID and status
            $computerResult = $this->db->query(
                "SELECT PCID, PCName FROM pcs WHERE (PCName = ? OR PCID = ?) AND Status IN ('Unassigned', 'Returned')",
                [$computer, $computer]
            );
            $computerRow = $computerResult->fetch(PDO::FETCH_ASSOC);

            if ($employeeRow && $computerRow) {
                $EmployeeID = $employeeRow['EmployeeID'];
                $PCID = $computerRow['PCID'];

                // Check temp assignments
                $tempCount = $this->db->query(
                    "SELECT COUNT(*) as cnt FROM temp_assignments WHERE EmployeeID = ? OR PCID = ?",
                    [$EmployeeID, $PCID]
                )->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

                // Check main assignments
                $mainCount = $this->db->query(
                    "SELECT COUNT(*) as cnt FROM assignments WHERE (EmployeeID = ? OR PCID = ?) AND Status != 'Returned'",
                    [$EmployeeID, $PCID]
                )->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

                if ($tempCount > 0 || $mainCount > 0) {
                    $_SESSION['warning'] = 'This employee or computer is already assigned to someone else.';
                } else {
                    $this->db->query(
                        "INSERT INTO temp_assignments (EmployeeID, PCID) VALUES (?, ?)",
                        [$EmployeeID, $PCID]
                    );
                    $_SESSION['success'] = 'Assignment saved successfully.';
                }
            } elseif (!$employeeRow) {
                $_SESSION['error'] = 'No employee data found. Please check the name or ID and try again.';
            } elseif (!$computerRow) {
                // Check if computer exists but is not available
                $computerAllResult = $this->db->query(
                    "SELECT PCID, PCName, Status FROM pcs WHERE (PCName = ? OR PCID = ?)",
                    [$computer, $computer]
                );
                $computerRowAll = $computerAllResult->fetch(PDO::FETCH_ASSOC);

                if ($computerRowAll && !in_array($computerRowAll['Status'], ['Unassigned', 'Returned'])) {
                    $assignmentResult = $this->db->query(
                        "SELECT e.FirstName, e.LastName 
                     FROM assignments a 
                     JOIN employees e ON a.EmployeeID = e.EmployeeID 
                     WHERE a.PCID = ? AND a.Status != 'Returned'
                     LIMIT 1",
                        [$computerRowAll['PCID']]
                    );
                    $assignmentRow = $assignmentResult->fetch(PDO::FETCH_ASSOC);
                    if ($assignmentRow) {
                        $_SESSION['warning'] = "The computer {$computerRowAll['PCName']} is already assigned to {$assignmentRow['FirstName']} {$assignmentRow['LastName']}.";
                    } else {
                        $_SESSION['error'] = 'No computer data found. Please check the computer name or ID and try again.';
                    }
                } else {
                    $_SESSION['error'] = 'No computer data found. Please check the computer name or ID and try again.';
                }
            } else {
                $_SESSION['error'] = 'No matching data found. Please check your input and try again.';
            }

            $this->db->query("COMMIT");
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "A server error occurred. Please contact the system administrator for assistance.";
        }

        header("Location: /computer?name=" . urlencode($name) . "&computer=" . urlencode($computer));
        exit();
    }

    // store the multiple assignments info to assignments table
    public function store()
    {
        $EmployeeIDs = array_map(
            fn($id) => $this->sanitize_input($id),
            $_POST['EmployeeID'] ?? []
        );
        $PCIDs = array_map(
            fn($id) => $this->sanitize_input($id),
            $_POST['PCID'] ?? []
        );
        $created_at = date('Y-m-d H:i:s');

        $EmployeeIDs = array_filter($EmployeeIDs, fn($id) => strlen($id) === 5);
        $PCIDs = array_filter($PCIDs, fn($id) => filter_var($id, FILTER_VALIDATE_INT) !== false);

        if (empty($EmployeeIDs) || empty($PCIDs) || count($EmployeeIDs) !== count($PCIDs)) {
            $_SESSION['warning'] = 'Invalid Employee ID format or mismatched assignments. Please review your entries and try again.';
            header("Location: /computer");
            exit();
        }

        try {
            $this->db->query("BEGIN");

            foreach ($EmployeeIDs as $index => $EmployeeID) {
                $PCID = $PCIDs[$index];

                $this->db->query(
                    "INSERT INTO assignments (EmployeeID, PCID, created_at) VALUES (?, ?, ?)",
                    [$EmployeeID, $PCID, $created_at]
                );

                $this->db->query(
                    "UPDATE pcs SET status = 'Assigned' WHERE PCID = ?",
                    [$PCID]
                );

                $partsResult = $this->db->query(
                    "SELECT p.PartID FROM parts p LEFT JOIN pc_parts cp ON p.PartID = cp.PartID WHERE cp.PCID = ?",
                    [$PCID]
                );
                $parts = $partsResult->fetchAll(PDO::FETCH_ASSOC);

                foreach ($parts as $row) {
                    $PartID = $row['PartID'];
                    $this->db->query(
                        "INSERT INTO parts_history (PartID, EmployeeID, created_at) VALUES (?, ?, ?)",
                        [$PartID, $EmployeeID, $created_at]
                    );
                }

                $empData = $this->db->query("SELECT FirstName, LastName, Email FROM employees WHERE EmployeeID = ?", [$EmployeeID]);
                $employee = $empData->fetch(PDO::FETCH_ASSOC);

                $pcData = $this->db->query("SELECT PCName FROM pcs WHERE PCID = ?", [$PCID]);
                $pc = $pcData->fetch(PDO::FETCH_ASSOC);

                if ($employee) {
                    $employeeName = $employee['FirstName'] . ' ' . $employee['LastName'];
                    $employeeEmail = $employee['Email'];
                    $emailSummaries[] = [
                        'PCName' => $pc['PCName'],
                        'EmployeeID' => $EmployeeID,
                        'Name' => $employeeName,
                        'Email' => $employeeEmail
                    ];
                }
            }
            // Prepare email via Brevo
            $registeredEmail = $_ENV['BREVO_EMAIL'];
            $apiKey = $_ENV['BREVO_API'];
            $urlEmployeeAccess = $_ENV['APP_URL'];
            $nameParts = explode(',', $_SESSION['name']);
            $senderName = isset($nameParts[1]) ? trim($nameParts[1]) : '';

            $config = Configuration::getDefaultConfiguration();
            $config->setApiKey('api-key', $apiKey);

            $apiInstance = new TransactionalEmailsApi(new Client(), $config);

            foreach ($emailSummaries as $data) {
                $subject = "Welcome to HPL Gamedesign Your PC is Ready for Deployment";
                $htmlContent = "<html>
                                    <body style='background-color: #000000; color: #f0f0f0; font-family: Arial, sans-serif; padding: 30px;'>
                                        <div style='max-width: 600px; margin: 0 auto; background-color: #111111; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(255, 0, 0, 0.2);'>

                                        <p style='color: #f44336; font-size: 16px;'><strong>Computer name:</strong> {$data['PCName']}</p>
                                        <p style='color: #f44336; font-size: 16px; margin-bottom: 20px;'><strong>Assignment Date:</strong> $created_at</p>

                                        <p style='font-size: 16px;'>Hi " . htmlspecialchars($data['Name']) . ",</p>

                                        <p>
                                            Welcome to the <strong style='color: #f44336;'>HPL Game Design</strong> team! We're excited to have you with us. 😊<br>
                                            I'm <strong>" . htmlspecialchars($senderName) . "</strong> from the IT Department, and your PC is now fully prepared and ready for use.
                                        </p>

                                        <p>
                                            To get started, please review and sign your <strong style='color: #f44336;'>Property Custody Form</strong>. It includes the details of your assigned PC and accessories like the monitor, keyboard, and mouse.
                                        </p>

                                        <p style='margin-top: 25px; margin-bottom: 5px;'><strong style='color: #f44336;'>To verify your access, use the following:</strong></p>
                                        <ul style='list-style-type: none; padding-left: 0;'>
                                            <li><strong>Email Address:</strong> {$data['Email']}</li>
                                            <li><strong>Employee ID:</strong> {$data['EmployeeID']}</li>
                                        </ul>

                                        <p style='margin-top: 30px;'>
                                            <a href='$urlEmployeeAccess'
                                            style='display: inline-block; padding: 12px 24px; background-color: #f44336; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>
                                            View and Sign Custody Form
                                            </a>
                                        </p>

                                        <p style='margin-top: 30px;'>
                                            If you have any questions or need help, feel free to reply to this message. I'm here to assist you anytime.
                                        </p>

                                        <p>
                                            Thank you again, and we look forward to working with you!
                                        </p>

                                        <br/>
                                        <p style='font-weight: bold;'>Warm regards,</p>
                                        <p>" . htmlspecialchars($senderName) . "</p>
                                        <p>IT Department</p>

                                        </div>
                                    </body>
                                </html>";

                $sendSmtpEmail = new SendSmtpEmail([
                    'subject' => $subject,
                    'sender' => [
                        'name' => 'HPL Notification',
                        'email' => $registeredEmail
                    ],
                    'to' => [
                        [
                            'name' => $data['Name'],
                            'email' => $data['Email']
                        ]
                    ],
                    'htmlContent' => $htmlContent
                ]);

                try {
                    $apiInstance->sendTransacEmail($sendSmtpEmail);
                } catch (Exception $e) {
                    error_log("Email failed for {$data['Email']}: " . $e->getMessage());
                }
            }

            $this->db->query("TRUNCATE TABLE temp_assignments");
            $this->db->query("COMMIT");
            $_SESSION['success'] = 'Computers have been assigned successfully.';


        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "A server error occurred. Please contact the system administrator for assistance.";
        }

        header("Location: /computer");
        exit();
    }

    // update return status of computer - multiple table updates
    public function return()
    {
        $PCID = $this->sanitize_input($_POST['PCID'] ?? '');
        $PCName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $Status = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $updated_at = $returnDate = date('Y-m-d H:i:s');

        if (empty($PCID) || empty($PCName) || empty($Status) || empty($EmployeeID)) {
            $_SESSION['warning'] = 'No data to save.';
            header("Location: /computer");
            exit();
        }

        $this->db->query("SET SQL_BIG_SELECTS = 1");
        $this->db->query("BEGIN");

        try {
            // Fetch Parts only
            $parts = $this->db->query("
            SELECT DISTINCT p.PartID
            FROM pc_parts p
            INNER JOIN parts_history ph ON ph.PartID = p.PartID
            WHERE ph.EmployeeID = ? AND p.PCID = ?
        ", [$EmployeeID, $PCID])->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Accessories only
            $accessories = $this->db->query("
            SELECT DISTINCT a.AccessoriesID, a.PRNumber, a.AccessoriesName
            FROM accessories_assignments aa
            INNER JOIN accessories a ON a.AccessoriesID = aa.AccessoriesID
            WHERE aa.EmployeeID = ?
        ", [$EmployeeID])->fetchAll(PDO::FETCH_ASSOC);

            // Insert unique per part
            foreach ($parts as $part) {
                $this->db->query("
                INSERT INTO returned_custody (EmployeeID, PCID, PCName, PartID, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ", [$EmployeeID, $PCID, $PCName, $part['PartID']]);
            }

            // Insert unique per accessory
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

            // Update status everywhere
            $this->db->query("UPDATE assignments SET ReturnedDate = ?, Status = ?, updated_at = ? WHERE PCID = ?", [
                $returnDate,
                $Status,
                $updated_at,
                $PCID
            ]);

            $this->db->query("UPDATE pcs SET Status = ?, updated_at = ? WHERE PCID = ?", [
                $Status,
                $updated_at,
                $PCID
            ]);

            $this->db->query("UPDATE parts_history SET Status = ?, updated_at = ? WHERE EmployeeID = ?", [
                $Status,
                $updated_at,
                $EmployeeID
            ]);

            // Update accessories stock counts
            if (!empty($accessories)) {
                $this->db->query("UPDATE accessories_assignments SET Status = ?, updated_at = ? WHERE EmployeeID = ?", [
                    $Status,
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

            $this->db->query("COMMIT");
            $_SESSION['success'] = $PCName . ' returned the PC!';
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }

        header("Location: /computer");
        exit();
    }

    // check and get the PCID - update computer page
    public function checkPCID()
    {
        $PCName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');

        if (empty($PCName)) {
            $_SESSION['warning'] = 'Please provide a valid PC name.';
            error_log("Warning: No PC name provided.");
            header("Location: /parts");
            exit();
        }

        try {
            $this->db->query('BEGIN');

            $getPC = $this->db->query("SELECT * FROM pcs WHERE PCName = ?", [$PCName]);
            $pcRow = $getPC->fetch(PDO::FETCH_ASSOC);

            if (!$pcRow) {
                throw new Exception("The specified PC was not found in the system.");
            }

            $PCID = $pcRow['PCID'];
            $PCName_data = $pcRow['PCName'];
            $created_at = date('Y-m-d H:i:s');

            $checkTemp = $this->db->query("SELECT COUNT(*) AS count FROM temp_pc");
            $tempCount = $checkTemp->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            if ($tempCount > 0) {
                throw new Exception("Only one computer can be processed at a time. Please complete or remove the existing entry before adding another.");
            }

            $this->db->query("INSERT INTO temp_pc (PCID, PCName, created_at) VALUES (?, ?, ?)", [
                $PCID,
                $PCName_data,
                $created_at
            ]);

            $this->db->query('COMMIT');

            $_SESSION['success'] = "$PCName_data has been successfully added for processing.";
            header("Location: /parts");
            exit();
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['error'] = "An error occurred while processing the PC: " . $e->getMessage();
            header("Location: /parts");
            exit();
        }
    }

    // reset the update page
    public function reset()
    {
        try {
            $this->db->query('BEGIN');

            // Get count from temp_pc
            $checkTempPC = $this->db->query("SELECT COUNT(*) AS count FROM temp_pc");
            $tempPCCount = $checkTempPC->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Get count from temp_update_pc_parts
            $checkTempParts = $this->db->query("SELECT COUNT(*) AS count FROM temp_update_pc_parts");
            $tempPartsCount = $checkTempParts->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            if ($tempPCCount == 0 && $tempPartsCount == 0) {
                $_SESSION['warning'] = "There is no temporary data available. You may now proceed to update another computer.";
            } else {
                $this->db->query("DELETE FROM temp_pc");
                $this->db->query("DELETE FROM temp_update_pc_parts");

                $this->db->query('COMMIT');
                $_SESSION['success'] = "Everything has been reset. You're all set to begin again!";
            }

            header("Location: /parts");
            exit();
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['error'] = "An error occurred on the server. Please contact the system administrator for assistance.";
            header("Location: /parts");
            exit();
        }
    }

    // store parts info to temporaray storage
    public function tempInstall()
    {
        $PartID = $this->sanitize_input($_POST['PartID'] ?? '');
        $PartType = $this->sanitize_input($_POST['PartType'] ?? '', 'ucwords');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $Model = $this->sanitize_input($_POST['Model'] ?? '', 'upper');
        $SerialNumber = $this->sanitize_input($_POST['SerialNumber'] ?? '', 'upper');

        if (empty($PartID) || empty($PartType) || empty($Brand) || empty($Model)) {
            $_SESSION['warning'] = 'Invalid input data.';
            header("Location: /parts");
            exit();
        }

        $excludedParts = ["processor", "motherboard", "gpu", "keyboard", "mouse", "webcam", "pen display", "pen tablet", "headset", "power supply"];
        $PartTypeLower = strtolower($PartType);

        try {
            $this->db->query('BEGIN');

            $stmt = $this->db->query("SELECT PCID FROM temp_pc LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Please search a computer first to update.");
            }

            $PCID = $result['PCID'];

            $stmt = $this->db->query("SELECT PartID FROM pc_parts WHERE PCID = ?", [$PCID]);
            $partIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $installedPartTypes = [];
            if (!empty($partIDs)) {
                foreach ($partIDs as $existingPartID) {
                    $stmt = $this->db->query("SELECT PartType FROM parts WHERE PartID = ?", [$existingPartID]);
                    $partTypeResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($partTypeResult && isset($partTypeResult['PartType'])) {
                        $installedPartTypes[] = strtolower($partTypeResult['PartType']);
                    }
                }
            }

            if (in_array($PartTypeLower, $excludedParts) && in_array($PartTypeLower, $installedPartTypes)) {
                $_SESSION['warning'] = "A $PartType is already installed on this computer!";
                header("Location: /parts");
                exit();
            }

            $created_at = date('Y-m-d H:i:s');
            $this->db->query(
                "INSERT INTO temp_update_pc_parts (PartID, PartType, Brand, Model, SerialNumber, created_At) VALUES (?, ?, ?, ?, ?, ?)",
                [$PartID, $PartType, $Brand, $Model, $SerialNumber, $created_at]
            );

            $this->db->query('COMMIT');
            $_SESSION['success'] = "$Brand $Model has been added successfully!";
            header("Location: /parts");
            exit();
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['error'] = "A server error occurred. Please contact the system administrator. " . $e->getMessage();
            header("Location: /parts");
            exit();
        }
    }

    // remove from the update computer page
    public function delete()
    {
        $PartID = $this->sanitize_input($_POST['PartID'] ?? '');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '');

        try {
            $this->db->query("BEGIN");

            $stmt = $this->db->query(
                "DELETE FROM temp_update_pc_parts WHERE PartID = ?",
                [$PartID]
            );

            if ($stmt) {
                $this->db->query("COMMIT");
                $_SESSION['success'] = $Brand . ' has been removed from the table.';
            } else {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = 'There was a problem removing the ' . $Brand . '. Please try again.';
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'There was a problem removing the ' . $Brand . '. Please try again.';
        }

        header("Location: /parts");
        exit();
    }


    // remove from the allocation table - assigning of computer page
    public function destroy()
    {
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $name = $this->sanitize_input($_POST['name'] ?? '');

        try {
            $this->db->query("BEGIN");

            $stmt = $this->db->query(
                "DELETE FROM temp_assignments WHERE EmployeeID = ?",
                [$EmployeeID]
            );

            if ($stmt) {
                $this->db->query("COMMIT");
                $_SESSION['success'] = $name . ' has been removed from the assignments.';
            } else {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = 'There was a problem updating the status for ' . $name . '. Please try again.';
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'There was a problem updating the status for ' . $name . '. Please try again.';
        }

        header("Location: /computer");
        exit();
    }

    // update the parts of a computer
    public function update()
    {
        $created_at = date('Y-m-d H:i:s');

        $PCName = $this->sanitize_input($_POST['PCName'] ?? '');
        if (empty($PCName)) {
            $_SESSION['warning'] = 'PC Name is required!';
            header("Location: /parts");
            exit();
        }

        try {
            // Get PCID from temp_pc
            $getPC = $this->db->query("SELECT PCID FROM temp_pc WHERE PCName = ?", [$PCName]);
            $pcData = $getPC->fetch(PDO::FETCH_ASSOC);
            if (!$pcData) {
                throw new Exception("PC not found in temporary storage");
            }
            $PCID = $pcData['PCID'];

            // Get EmployeeID (optional)
            $getEmployee = $this->db->query(" SELECT e.EmployeeID, e.FirstName, e.LastName, e.Email FROM assignments a JOIN employees e ON a.EmployeeID = e.EmployeeID WHERE a.PCID = ? AND a.Status = 'Assigned' ORDER BY a.Updated_at DESC LIMIT 1 ", [$PCID]);
            $employeeData = $getEmployee->fetch(PDO::FETCH_ASSOC);
            $assignedEmployeeID = $employeeData['EmployeeID'] ?? null;
            $employeeEmail = $employeeData['Email'] ?? null;
            $employeeName = $employeeData['FirstName'] . ' ' . $employeeData['LastName'];


        } catch (Exception $e) {
            $_SESSION['warning'] = 'Invalid PC configuration: ' . $e->getMessage();
            header("Location: /parts");
            exit();
        }

        $PartIDs = filter_input(INPUT_POST, 'PartID', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
        $PartIDs = array_filter($PartIDs, fn($id) => filter_var($id, FILTER_VALIDATE_INT) !== false);

        if (empty($PartIDs)) {
            $_SESSION['warning'] = 'No parts selected!';
            header("Location: /parts");
            exit();
        }

        $this->db->query("BEGIN");

        try {
            foreach ($PartIDs as $PartID) {
                $this->db->query("INSERT INTO pc_parts (PCID, PartID, created_at) VALUES (?, ?, ?)", [$PCID, $PartID, $created_at]);
                if ($assignedEmployeeID) {
                    $this->db->query("INSERT INTO parts_history (PartID, EmployeeID, created_at) VALUES (?, ?, ?)", [$PartID, $assignedEmployeeID, $created_at]);
                }
                $this->db->query("UPDATE parts SET status = 'In Use', updated_at = ? WHERE PartID = ?", [$created_at, $PartID]);
            }

            $this->db->query("DELETE FROM temp_pc");
            $this->db->query("DELETE FROM temp_update_pc_parts");

            $this->db->query("COMMIT");
            $_SESSION['success'] = "Successfully updated $PCName with " . count($PartIDs) . " parts!";
            $partDetailsHtml = "";
            $counter = 1;

            foreach ($PartIDs as $PartID) {
                $part = $this->db->query("SELECT PRNumber, PartID, PartType, Brand, Model, SerialNumber FROM parts WHERE PartID = ?", [$PartID]);
                $parts = $part->fetch(PDO::FETCH_ASSOC);

                $partDetailsHtml .= "
                                    <div style='font-family: Arial, sans-serif; font-size: 14px; color: #f0f0f0; background-color: #1a1a1a; padding: 15px; border-radius: 6px; border-left: 4px solid #f44336; margin-top: 20px;'>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>No.:</strong> $counter</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>PR Number:</strong> " . ($parts['PRNumber'] ?? 'N/A') . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Part ID:</strong> " . htmlspecialchars($parts['PartID']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Part Type:</strong> " . htmlspecialchars($parts['PartType']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Brand:</strong> " . htmlspecialchars($parts['Brand']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Model:</strong> " . htmlspecialchars($parts['Model']) . "</p>
                                        <p style='margin: 0;'><strong style='color: #f44336;'>Serial Number:</strong> " . htmlspecialchars($parts['SerialNumber']) . "</p>
                                    </div>";
                $counter++;
            }



            $registeredEmail = $_ENV['BREVO_EMAIL'];

            // Send email notification to employee when there are changes to their computer
            if ($assignedEmployeeID && $employeeEmail) {
                $partList = implode(", ", $PartIDs);
                $subject = "PC Parts Updated - $PCName";
                $htmlContent = "
                                <html>
                                    <body style='background-color: #000000; color: #f0f0f0; font-family: Arial, sans-serif; padding: 30px;'>
                                        <div style='max-width: 600px; margin: 0 auto; background-color: #111111; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(255, 0, 0, 0.2);'>

                                        <p style='font-size: 16px;'>Hello " . htmlspecialchars($employeeName) . ",</p>

                                        <p style='font-size: 16px;'>
                                            This is to notify you that your assigned PC <strong style='color: #f44336;'>$PCName</strong> has been updated with the following part(s):
                                        </p>

                                        $partDetailsHtml

                                        <p style='margin-top: 20px; font-size: 16px;'><strong>Update Date:</strong> $created_at</p>

                                        <br>
                                        <p style='font-weight: bold;'>Regards,</p>
                                        <p>HPL IT Team</p>
                                        </div>
                                    </body>
                                </html>";


                $config = Configuration::getDefaultConfiguration();
                $config->setApiKey('api-key', $_ENV['BREVO_API']);

                $apiInstance = new TransactionalEmailsApi(
                    new Client(),
                    $config
                );

                $sendSmtpEmail = new SendSmtpEmail([
                    'subject' => $subject,
                    'sender' => [
                        'name' => 'HPL Notification',
                        'email' => $registeredEmail
                    ],
                    'to' => [
                        [
                            'name' => $employeeName,
                            'email' => $employeeEmail
                        ]
                    ],
                    'htmlContent' => $htmlContent
                ]);

                try {
                    error_log("Sending email to $employeeEmail for PC $PCName");
                    $apiInstance->sendTransacEmail($sendSmtpEmail);
                } catch (Exception $e) {
                    $_SESSION['warning'] = "Update succeeded, but failed to send email: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }

        header("Location: /parts");
        exit();
    }
    public function specifications($PCName)
    {
        if (empty($PCName)) {
            die('Invalid PC Name');
        }

        // Fetch the PC record using PCName
        $stmt = $this->db->query("SELECT * FROM pcs WHERE PCName = ?", [$PCName]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            die('PC not found');
        }

        // Get the PCID from the first matched row
        $PCID = $data[0]['PCID'];

        // Fetch parts installed in the PC
        $stmt = $this->db->query(" SELECT p2.PartID, p2.Brand, p2.uniqueID, p2.PartType, p2.Model, p2.SerialNumber, p3.PCName
                                    FROM pc_parts p1
                                    JOIN parts p2 ON p1.PartID = p2.PartID
                                    LEFT JOIN pcs p3 ON p1.PCID = p3.PCID
                                    WHERE p1.PCID = ?
                                ", [$PCID]);

        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('pages/specifications', [
            'title' => 'Specification of ' . $PCName,
            'data' => $data,
            'parts' => $parts,
        ]);
    }

    public function uninstall()
    {
        $PartID = $this->sanitize_input($_POST['PartID'] ?? '');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $PCName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $Status = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        $HistoryStatus = $this->sanitize_input($_POST['HistoryStatus'] ?? '', 'ucwords');
        $updated_at = date('Y-m-d H:i:s');

        if (empty($PartID) || empty($Brand) || empty($PCName) || empty($Status) || empty($HistoryStatus)) {
            $_SESSION['warning'] = 'Invalid input data.';
            header("Location: /computer/specifications/" . urldecode($PCName));
            exit();
        }

        try {
            $this->db->query('BEGIN');

            // Get PCID and employee info
            $getPC = $this->db->query("SELECT PCID FROM pcs WHERE PCName = ?", [$PCName]);
            $pcData = $getPC->fetch(PDO::FETCH_ASSOC);
            if (!$pcData) {
                throw new Exception("PC not found.");
            }

            $PCID = $pcData['PCID'];

            $getEmployee = $this->db->query("
            SELECT e.EmployeeID, e.FirstName, e.LastName, e.Email
            FROM assignments a
            JOIN employees e ON a.EmployeeID = e.EmployeeID
            WHERE a.PCID = ? AND a.Status = 'Assigned'
            ORDER BY a.Updated_at DESC LIMIT 1
        ", [$PCID]);

            $employeeData = $getEmployee->fetch(PDO::FETCH_ASSOC);
            $assignedEmployeeID = $employeeData['EmployeeID'] ?? null;
            $employeeEmail = $employeeData['Email'] ?? null;
            $employeeName = $employeeData['FirstName'] . ' ' . $employeeData['LastName'];

            // Update and delete part
            $this->db->query("UPDATE parts SET Status = ?, updated_at = ? WHERE PartID = ?", [$Status, $updated_at, $PartID]);
            $this->db->query("DELETE FROM pc_parts WHERE PartID = ?", [$PartID]);

            // Log uninstall in history
            if ($assignedEmployeeID) {
                $this->db->query("INSERT INTO parts_history (PartID, EmployeeID, Status, created_at) VALUES (?, ?, ?, ?)", [$PartID, $assignedEmployeeID, $HistoryStatus, $updated_at]);
            }

            // Fetch part details
            $part = $this->db->query("SELECT PRNumber, PartID, PartType, Brand, Model, SerialNumber FROM parts WHERE PartID = ?", [$PartID]);

            $parts = $part->fetch(PDO::FETCH_ASSOC);

            $this->db->query("COMMIT");

            $_SESSION['success'] = "$Brand has been uninstalled from $PCName";

            // Build email content
            if ($assignedEmployeeID && $employeeEmail) {
                $partDetailsHtml = "
                                    <div style='font-family: Arial, sans-serif; font-size: 14px; color: #f0f0f0; background-color: #1a1a1a; padding: 15px; border-radius: 6px; border-left: 4px solid #f44336; margin-top: 20px;'>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>No.:</strong> 1</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>PR Number:</strong> " . ($parts['PRNumber'] ?? 'N/A') . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Part ID:</strong> " . htmlspecialchars($parts['PartID']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Part Type:</strong> " . htmlspecialchars($parts['PartType']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Brand:</strong> " . htmlspecialchars($parts['Brand']) . "</p>
                                        <p style='margin: 0 0 8px 0;'><strong style='color: #f44336;'>Model:</strong> " . htmlspecialchars($parts['Model']) . "</p>
                                        <p style='margin: 0;'><strong style='color: #f44336;'>Serial Number:</strong> " . htmlspecialchars($parts['SerialNumber']) . "</p>
                                    </div>";

                $subject = "PC Part Uninstalled - $PCName";
                $htmlContent = "
                                <html>
                                    <body style='background-color: #000000; color: #f0f0f0; font-family: Arial, sans-serif; padding: 10px;'>
                                        <div style='max-width: 600px; margin: 0 auto; background-color: #111111; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(255, 0, 0, 0.2);'>

                                        <p style='font-size: 16px;'>Hello " . htmlspecialchars($employeeName) . ",</p>

                                        <p style='font-size: 16px;'>
                                            This is to inform you that the following part has been <strong style='color: #f44336;'>uninstalled</strong> from your assigned PC <strong style='color: #f44336;'>$PCName</strong>:
                                        </p>

                                        $partDetailsHtml

                                        <p style='margin-top: 20px; font-size: 16px;'><strong>Uninstall Date:</strong> $updated_at</p>

                                        <br>
                                        <p style='font-weight: bold;'>Regards,</p>
                                        <p>HPL IT Team</p>

                                        </div>
                                    </body>
                                </html>";


                $config = Configuration::getDefaultConfiguration();
                $config->setApiKey('api-key', $_ENV['BREVO_API']);

                $apiInstance = new TransactionalEmailsApi(
                    new Client(),
                    $config
                );

                $registeredEmail = $_ENV['BREVO_EMAIL'];
                $sendSmtpEmail = new SendSmtpEmail([
                    'subject' => $subject,
                    'sender' => [
                        'name' => 'HPL Notification',
                        'email' => $registeredEmail
                    ],
                    'to' => [
                        [
                            'name' => $employeeName,
                            'email' => $employeeEmail
                        ]
                    ],
                    'htmlContent' => $htmlContent
                ]);

                try {
                    $apiInstance->sendTransacEmail($sendSmtpEmail);
                } catch (Exception $e) {
                    $_SESSION['warning'] = "Uninstall succeeded, but failed to send email: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "There was an error: " . $e->getMessage();
        }

        header("Location: /computer/specifications/" . urldecode($PCName));
        exit();
    }
    public function returned($EmployeeID)
    {
        if (empty($EmployeeID)) {
            die('Invalid PC Name');
        }

        // Fetch the PC record using PCName
        $stmt = $this->db->query("SELECT r.PCID, r.EmployeeID, pc.PCName, r.AccessoriesPRNumber, r.AccessoriesID, r.AccessoriesName FROM returned_custody r LEFT JOIN pcs pc ON pc.PCID = r.PCID WHERE EmployeeID = ?", [$EmployeeID]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            die('PC not found');
        }

        // Get the PCID from the first matched row
        $EmployeeID = $data[0]['EmployeeID'];
        $PCID = $data[0]['PCID'];
        $PCName = $data[0]['PCName'];
        $AccessoriesPRNumber = $data[0]['AccessoriesPRNumber'];
        $AccessoriesID = $data[0]['AccessoriesID'];
        $AccessoriesName = $data[0]['AccessoriesName'];

        // Fetch parts installed in the PC
        $stmt = $this->db->query(" SELECT 
                                            r.PartID, 
                                            p.Brand, 
                                            p.uniqueID, 
                                            p.PartType, 
                                            p.Model, 
                                            p.SerialNumber, 
                                            r.PCName
                                        FROM returned_custody r
                                        JOIN parts p ON p.PartID = r.PartID
                                        WHERE r.EmployeeID = ?
                                    ", [$EmployeeID]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $employeeName = $this->db->query("SELECT * FROM employees WHERE EmployeeID = ?", [$EmployeeID]);
        $name = $employeeName->fetch(PDO::FETCH_ASSOC);

        $accessoriesData = $this->db->query("SELECT 
                                                    rc.AccessoriesID,
                                                    rc.AccessoriesPRNumber,
                                                    rc.AccessoriesName,
                                                    a.Brand
                                                FROM returned_custody rc
                                                LEFT JOIN accessories a ON rc.AccessoriesID = a.AccessoriesID
                                                WHERE rc.EmployeeID = ? AND rc.PCID = ?
                                                AND (rc.AccessoriesName IS NOT NULL AND rc.AccessoriesName != '')
                                            ", [$EmployeeID, $PCID]);

        $accessories = $accessoriesData->fetchAll(PDO::FETCH_ASSOC);


        $this->view('pages/returned', [
            'title' => 'Returned Equipment',
            'PCName' => $PCName,
            'parts' => $parts,
            'accessories' => $accessories,
        ]);
    }
}