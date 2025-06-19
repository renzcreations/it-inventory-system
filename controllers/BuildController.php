<?php
namespace Controllers;

use Exception;
use PDO;
use System\Core\Database;
use System\Core\Controller;

class BuildController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    public function index()
    {
        $tempParts = $this->db->query("SELECT * FROM temp_pc_parts");
        $tempPart = $tempParts->fetchAll(PDO::FETCH_ASSOC);

        $excludedPartTypesList = ["Processor", "Motherboard", "GPU", "Keyboard", "Mouse", "Webcam", "Pen Display", "Pen Tablet", "Headset", "Power Supply"];
        $placeholders = implode(',', array_fill(0, count($excludedPartTypesList), '?'));

        // Get excluded types from temp_pc_parts
        $stmt = $this->db->query("SELECT DISTINCT PartType FROM temp_pc_parts WHERE PartType IN ($placeholders)", $excludedPartTypesList);
        $excludedTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $excludedTypes = array_column($excludedTypes, 'PartType');

        $partsQuery = "SELECT * FROM parts WHERE Status = 'Available'";
        $conditions = [];
        $params = [];

        $conditions[] = "PartID NOT IN (SELECT PartID FROM temp_pc_parts)";

        if (!empty($excludedTypes)) {
            $typePlaceholders = implode(',', array_fill(0, count($excludedTypes), '?'));
            $conditions[] = "PartType NOT IN ($typePlaceholders)";
            $params = array_merge($params, $excludedTypes);
        }

        if (!empty($conditions)) {
            $partsQuery .= " AND " . implode(' AND ', $conditions);
        }

        $partsData = $this->db->query($partsQuery . ' ORDER BY created_at DESC', $params);
        $parts = $partsData->fetchAll(PDO::FETCH_ASSOC);

        // Grouped query for latest part per type
        $groupedQuery = "SELECT PartType, MAX(created_at) AS latest_date FROM parts WHERE Status = 'Available'";
        $groupedParams = [];
        if (!empty($excludedTypes)) {
            $typePlaceholders = implode(',', array_fill(0, count($excludedTypes), '?'));
            $groupedQuery .= " AND PartType NOT IN ($typePlaceholders)";
            $groupedParams = $excludedTypes;
        }
        $groupedQuery .= " GROUP BY PartType";
        $types = $this->db->query($groupedQuery, $groupedParams);
        $type = $types->fetchAll(PDO::FETCH_ASSOC);

        // Query for available part types
        $partTypesQuery = "SELECT DISTINCT PartType FROM parts WHERE Status = 'Available'";
        $partTypesParams = [];
        if (!empty($excludedTypes)) {
            $typePlaceholders = implode(',', array_fill(0, count($excludedTypes), '?'));
            $partTypesQuery .= " AND PartType NOT IN ($typePlaceholders)";
            $partTypesParams = $excludedTypes;
        }
        $partTypesResult = $this->db->query($partTypesQuery, $partTypesParams);
        $partTypes = $partTypesResult->fetchAll(PDO::FETCH_ASSOC);

        $this->view('pages/build', [
            'title' => 'Build a Computer',
            'tempPart' => $tempPart,
            'parts' => $parts,
            'partTypes' => $partTypes,
            'type' => $type
        ]);
    }

    public function create()
    {
        $PartID = filter_var($_POST['PartID'] ?? null, FILTER_VALIDATE_INT);
        $PartType = $this->sanitize_input($_POST['PartType'] ?? '', 'upper');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'ucwords');
        $Model = $this->sanitize_input($_POST['Model'] ?? '', 'ucwords');
        $SerialNumber = $this->sanitize_input($_POST['SerialNumber'] ?? '', 'upper');

        $partName = $Brand . ' ' . $Model;
        $created_at = date('Y-m-d H:i:s.u');

        try {
            $this->db->query(
                "INSERT INTO temp_pc_parts (PartID, PartType, Brand, Model, SerialNumber, created_At) VALUES (?, ?, ?, ?, ?, ?)",
                [$PartID, $PartType, $Brand, $Model, $SerialNumber, $created_at]
            );
            $_SESSION['success'] = $partName . ' added';
        } catch (Exception $e) {
            $_SESSION['error'] = 'There was a problem adding ' . $Brand . '! Please try again.';
        }

        header("Location: /build");
        exit();
    }

    public function check()
    {
        try {
            if (!isset($_GET['name'])) {
                throw new Exception('Missing name parameter');
            }

            $name = $_GET['name'];

            // Use your Database abstraction (PDO)
            $stmt = $this->db->query("SELECT PCName FROM pcs WHERE PCName = ?", [$name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'available' => $row === false, // true if not found
                'name' => $name
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store()
    {
        $PCName = $this->sanitize_input($_POST['PCName'] ?? '', 'upper');
        $created_at = $updated_at = date('Y-m-d H:i:s.u');

        if (empty($PCName)) {
            $_SESSION['warning'] = 'Please set the computer name.';
            header("Location: /build");
            exit();
        }

        if (!isset($_POST['PartID']) || !is_array($_POST['PartID']) || empty($_POST['PartID'])) {
            $_SESSION['warning'] = 'Please select parts to install.';
            header("Location: /build");
            exit();
        }

        foreach ($_POST['PartID'] as $PartID) {
            $cleanPartID = intval($PartID);
            if (empty($cleanPartID)) {
                $_SESSION['warning'] = 'Invalid parts selected.';
                header("Location: /build");
                exit();
            }
        }

        try {
            $this->db->query("BEGIN");

            // Insert new PC
            $this->db->query(
                "INSERT INTO pcs (PCName, created_at) VALUES (?, ?)",
                [$PCName, $created_at]
            );

            // Get the last inserted PCID
            $PCID = $this->db->query("SELECT LAST_INSERT_ID() AS id")->fetch(PDO::FETCH_ASSOC)['id'];

            // Insert parts and update their status
            foreach ($_POST['PartID'] as $PartID) {
                $cleanPartID = intval($PartID);

                $this->db->query(
                    "INSERT INTO pc_parts (PCID, PartID, created_at) VALUES (?, ?, ?)",
                    [$PCID, $cleanPartID, $created_at]
                );

                $this->db->query(
                    "UPDATE parts SET status = 'In Use', updated_at = ? WHERE PartID = ?",
                    [$updated_at, $cleanPartID]
                );
            }

            $this->db->query("TRUNCATE TABLE temp_pc_parts");
            $this->db->query("COMMIT");

            $_SESSION['success'] = htmlspecialchars($PCName, ENT_QUOTES, 'UTF-8') . ' created successfully!';
            header("Location: /build");
            exit();

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information.";
            header("Location: /build");
            exit();
        }
    }
    public function destroy()
    {
        $PartID = $this->sanitize_input($_POST['PartID'] ?? '');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '');

        try {
            $this->db->query("BEGIN");

            $stmt = $this->db->query(
                "DELETE FROM temp_pc_parts WHERE PartID = ?",
                [$PartID]
            );

            if ($stmt) {
                $this->db->query("COMMIT");
                $_SESSION['success'] = $Brand . ' has been deleted!';
            } else {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
        }

        header("Location: /build");
        exit();
    }
}