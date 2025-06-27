<?php
namespace Controllers;

use Exception;
use PDO;
use System\Core\Database;
use System\Core\Controller;

class AccessoriesController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    public function index()
    {
        $historyStmt = $this->db->query(
            "SELECT 
            ac.AccessoriesID,
            ac.Brand,
            ac.PRNumber,
            ac.AccessoriesName,
            ac.Qty,
            ac.DefectiveCount as Defective,
            a.id AS assignmentID,
            a.Status AS assignmentStatus,
            e.FirstName,
            e.LastName,
            e.EmployeeID
        FROM accessories ac
        LEFT JOIN accessories_assignments a ON ac.AccessoriesID = a.AccessoriesID
        LEFT JOIN employees e ON e.EmployeeID = a.EmployeeID
        ORDER BY ac.AccessoriesName ASC, assignmentStatus ASC"
        );
        $history = $historyStmt ? $historyStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $groupedHistory = [];

        foreach ($history as $item) {
            $id = $item['AccessoriesID'];
            if (!isset($groupedHistory[$id])) {
                $groupedHistory[$id] = [
                    'AccessoriesID' => $item['AccessoriesID'],
                    'AccessoriesName' => $item['AccessoriesName'],
                    'Brand' => $item['Brand'],
                    'PRNumber' => $item['PRNumber'],
                    'Qty' => $item['Qty'],
                    'Defective' => $item['Defective'],
                    'assignments' => []
                ];
            }

            // Only add if EmployeeID is not empty and status is 'Assigned'
            if (!empty($item['EmployeeID']) && $item['assignmentStatus'] === 'Assigned') {
                $groupedHistory[$id]['assignments'][] = [
                    'EmployeeID' => $item['EmployeeID'],
                    'FirstName' => $item['FirstName'],
                    'LastName' => $item['LastName'],
                    'PRNumber' => $item['PRNumber'],
                    'Status' => $item['assignmentStatus']
                ];
            }
        }

        // Fetch all accessories for filter or other use
        $accessoriesStmt = $this->db->query("SELECT * FROM accessories");
        $accessories = $accessoriesStmt ? $accessoriesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $accessoriesTemp = $this->db->query("SELECT * FROM accessories_temp");
        $accessories_temp = $accessoriesTemp ? $accessoriesTemp->fetchAll(PDO::FETCH_ASSOC) : [];


        // $accessoriesStmt = $this->db->query("SELECT * FROM accessories_temp");
        // $accessories = $accessoriesStmt ? $accessoriesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $accessoriesName = $this->db->query("SELECT AccessoriesName FROM accessories GROUP BY AccessoriesName");
        $accessoriesNameFilter = $accessoriesName && $accessoriesName->rowCount() > 0 ? $accessoriesName->fetchAll(PDO::FETCH_ASSOC) : [];

        $brandsByAccessory = [];
        foreach ($accessories as $item) {
            if (!isset($brandsByAccessory[$item['AccessoriesName']])) {
                $brandsByAccessory[$item['AccessoriesName']] = [];
            }
            if (!in_array($item['Brand'], $brandsByAccessory[$item['AccessoriesName']])) {
                $brandsByAccessory[$item['AccessoriesName']][] = $item['Brand'];
            }
        }

        $returnHistory = $this->db->query(
            "SELECT 
            ac.AccessoriesID,
            ac.Brand,
            ac.PRNumber,
            ac.AccessoriesName,
            a.id AS assignmentID,
            a.Status,
            e.FirstName,
            e.LastName,
            e.EmployeeID
        FROM accessories_assignments a
        LEFT JOIN accessories ac ON a.AccessoriesID = ac.AccessoriesID
        LEFT JOIN employees e ON e.EmployeeID = a.EmployeeID
        WHERE a.Status = 'Returned'
        ORDER BY ac.AccessoriesName ASC, a.Status ASC"
        );
        $return = $returnHistory ? $returnHistory->fetchAll(PDO::FETCH_ASSOC) : [];

        $returnGroupHistory = [];

        foreach ($return as $item) {
            $id = $item['AccessoriesID'];
            if (!isset($returnGroupHistory[$id])) {
                $returnGroupHistory[$id] = [
                    'AccessoriesID' => $item['AccessoriesID'],
                    'AccessoriesName' => $item['AccessoriesName'],
                    'Brand' => $item['Brand'],
                    'PRNumber' => $item['PRNumber'],
                    'return' => []
                ];
            }

            /// Only add if EmployeeID is not empty and status is 'Returned'
            if (!empty($item['EmployeeID']) && $item['Status'] === 'Returned') {
                $returnGroupHistory[$id]['return'][] = [
                    'EmployeeID' => $item['EmployeeID'],
                    'FirstName' => $item['FirstName'],
                    'LastName' => $item['LastName'],
                    'PRNumber' => $item['PRNumber'],
                    'Status' => $item['Status']
                ];
            }
        }

        $returnAccessories = $this->db->query("SELECT ac.AccessoriesName, a.Status FROM accessories ac LEFT JOIN accessories_assignments a ON a.AccessoriesID = ac.AccessoriesID WHERE a.Status = 'Returned' GROUP BY AccessoriesName");
        $returnAccessoriesNameFilter = $returnAccessories && $returnAccessories->rowCount() > 0 ? $returnAccessories->fetchAll(PDO::FETCH_ASSOC) : [];

        $returnStmt = $this->db->query("SELECT ac.*, a.Status FROM accessories ac LEFT JOIN accessories_assignments a ON a.AccessoriesID = ac.AccessoriesID WHERE a.Status = 'Returned'");
        $returnAccessoriesStmt = $returnStmt ? $returnStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $returnBrandAccessory = [];
        foreach ($returnAccessoriesStmt as $item) {
            if (!isset($returnBrandAccessory[$item['AccessoriesName']])) {
                $returnBrandAccessory[$item['AccessoriesName']] = [];
            }
            if (!in_array($item['Brand'], $returnBrandAccessory[$item['AccessoriesName']])) {
                $returnBrandAccessory[$item['AccessoriesName']][] = $item['Brand'];
            }
        }

        $this->view('pages/accessories', [
            'title' => 'Accessories',
            'accessories' => $accessories,
            'history' => array_values($groupedHistory),
            'returnGroupHistory' => array_values($returnGroupHistory),
            'accessoriesNameFilter' => $accessoriesNameFilter,
            'brandsByAccessory' => $brandsByAccessory,
            'accessories_temp' => $accessories_temp,
            'returnAccessoriesNameFilter' => $returnAccessoriesNameFilter,
            'returnBrandAccessory' => $returnBrandAccessory,
            'returnAccessoriesStmt' => $returnAccessoriesStmt
        ]);

    }

    public function create()
    {
        $PRNumber = $this->sanitize_input($_POST['PRNumber'] ?? null, 'upper');
        $AccessoriesName = $this->sanitize_input($_POST['AccessoriesName'] ?? '', 'ucwords');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'ucwords');
        $Quantity = filter_var($_POST['Quantity'] ?? '', FILTER_VALIDATE_INT);
        $created_at = date('Y-m-d H:i:s');

        if (empty($AccessoriesName) || empty($Quantity)) {
            $_SESSION['warning'] = 'Invalid input data.';
            $_SESSION['accessories_old_input'] = $_POST;
            header("Location: /accessories");
            exit();
        }

        if ($Brand === '' || $Brand === null) {
            $Brand = 'No Brand';
        }

        if ($PRNumber === '' || $PRNumber === null) {
            $PRNumber = null;
        }

        try {
            $this->db->query("BEGIN");

            $checkStmt = $this->db->query(
                "SELECT Qty FROM accessories_temp WHERE AccessoriesName = ? AND Brand = ? AND PRNumber = ?",
                [$AccessoriesName, $Brand, $PRNumber]
            );
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $newQty = $row['Qty'] + $Quantity;
                // Use query() for UPDATE
                $this->db->query(
                    "UPDATE accessories_temp SET Qty = ?, CreatedAt = ? WHERE AccessoriesName = ? AND Brand = ? AND PRNumber = ?",
                    [$newQty, $created_at, $AccessoriesName, $Brand, $PRNumber]
                );

                unset($_SESSION['accessories_old_input']);
                $_SESSION['success'] = "The quantity of $AccessoriesName ($Brand) has been successfully updated by $newQty";
            } else {
                // Use query() for INSERT
                $this->db->query(
                    "INSERT INTO accessories_temp (AccessoriesName, Brand, Qty, PRNumber, CreatedAt) VALUES (?, ?, ?, ?, ?)",
                    [$AccessoriesName, $Brand, $Quantity, $PRNumber, $created_at]
                );
                unset($_SESSION['accessories_old_input']);
                $_SESSION['success'] = "$AccessoriesName ($Brand) with a quantity of $Quantity has been successfully added!";
            }


            $this->db->query("COMMIT");

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            error_log("Transaction failed: " . $e->getMessage());
        }
        header("Location: /accessories");
        exit();
    }

    public function store()
    {
        try {
            $this->db->query("BEGIN");

            // Fetch all rows from accessories_temp
            $tempRows = $this->db->query("SELECT * FROM accessories_temp")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tempRows)) {
                $_SESSION['warning'] = 'No accessories found in the temporary list.';
                header("Location: /accessories");
                exit();
            }

            $created_at = date('Y-m-d H:i:s');

            foreach ($tempRows as $row) {
                $prNumber = $this->sanitize_input($row['PRNumber'] ?? '', 'upper');
                $prNumber = $prNumber === '' ? null : $prNumber;

                $accessoriesID = $row['AccessoriesID'] ?? null;

                $accessoriesName = $this->sanitize_input($row['AccessoriesName'] ?? '', 'ucwords');

                $brand = $this->sanitize_input($row['Brand'] ?? '', 'ucwords');
                $brand = $brand === '' ? null : $brand;

                $quantity = (int) ($row['Qty'] ?? 0);

                // Check if record already exists with NULL-safe comparison
                $existingStmt = $this->db->query(
                    "SELECT Qty FROM accessories 
                    WHERE AccessoriesName = ?
                    AND ((Brand = ?) OR (Brand IS NULL AND ? IS NULL))
                    AND ((PRNumber = ?) OR (PRNumber IS NULL AND ? IS NULL))",
                    [$accessoriesName, $brand, $brand, $prNumber, $prNumber]
                );
                $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $newQty = $existing['Qty'] + $quantity;
                    $this->db->query(
                        "UPDATE accessories 
                        SET Qty = ?, CreatedAt = ?
                        WHERE AccessoriesName = ?
                        AND ((Brand = ?) OR (Brand IS NULL AND ? IS NULL))
                        AND ((PRNumber = ?) OR (PRNumber IS NULL AND ? IS NULL))",
                        [$newQty, $created_at, $accessoriesName, $brand, $brand, $prNumber, $prNumber]
                    );
                } else {
                    $this->db->query(
                        "INSERT INTO accessories (AccessoriesName, Brand, Qty, PRNumber, CreatedAt) 
                        VALUES (?, ?, ?, ?, ?)",
                        [$accessoriesName, $brand, $quantity, $prNumber, $created_at]
                    );
                }
            }

            // Truncate accessories_temp
            $this->db->query("TRUNCATE TABLE accessories_temp");

            $this->db->query("COMMIT");
            unset($_SESSION['accessories_old_input']);
            $_SESSION['success'] = "Accessories have been successfully stored!";
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            error_log("Transaction failed: " . $e->getMessage());
        }

        header("Location: /accessories");
        exit();
    }

    public function assign()
    {
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $Accessories = $_POST['Accessories'] ?? [];

        $created_at = $updated_at = date('Y-m-d H:i:s');

        // Check if any accessories were selected
        if (empty($Accessories)) {
            $_SESSION['warning'] = 'No accessories selected.';
            error_log("Error: No accessories selected.");
            echo "<script>location.replace(document.referrer);</script>";
            exit();
        }

        try {
            $this->db->query('BEGIN');
            $warnings = [];

            // Loop through selected accessories
            foreach ($Accessories as $accessoriesName => $identifier) {
                // Skip if no identifier is provided
                if (empty($identifier)) {
                    continue;
                }

                // Try to fetch by PRNumber first
                $stmt = $this->db->query(
                    "SELECT AccessoriesID, Qty FROM accessories WHERE AccessoriesName = ? AND PRNumber = ?",
                    [$accessoriesName, $identifier]
                );
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                // If not found, try to fetch by AccessoriesID
                if (!$result) {
                    $stmt = $this->db->query(
                        "SELECT AccessoriesID, Qty, PRNumber FROM accessories WHERE AccessoriesID = ?",
                        [$identifier]
                    );
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if (!$result) {
                    error_log("Accessory not found: $accessoriesName with identifier $identifier");
                    continue;
                }

                $AccessoriesID = $result['AccessoriesID'];
                $qty = $result['Qty'];
                $resolvedPRNumber = $result['PRNumber'] ?? null;

                // Check stock availability
                if ($qty <= 0) {
                    error_log("No stock left for: $accessoriesName with ID $AccessoriesID");
                    continue;
                }

                // Check if employee already has this accessory assigned
                $checkStmt = $this->db->query(
                    "SELECT COUNT(*) AS count FROM accessories_assignments WHERE EmployeeID = ? AND AccessoriesID = ? AND Status != 'Returned'",
                    [$EmployeeID, $AccessoriesID]
                );
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($checkResult['count'] > 0) {
                    $warnings[] = "Employee $EmployeeID already has accessory '$accessoriesName' assigned.";
                    error_log("Warning: Employee $EmployeeID already has accessory '$accessoriesName' assigned.");
                    continue;
                }

                // Update stock: deduct quantity and increment assigned count
                $this->db->query(
                    "UPDATE accessories SET Qty = Qty - 1, AssignedCount = IFNULL(AssignedCount, 0) + 1, UpdatedAt = ? WHERE AccessoriesID = ?",
                    [$updated_at, $AccessoriesID]
                );

                // Record the assignment
                $this->db->query(
                    "INSERT INTO accessories_assignments (EmployeeID, AccessoriesID, PRNumber, created_at) VALUES (?, ?, ?, ?)",
                    [$EmployeeID, $AccessoriesID, $resolvedPRNumber, $created_at]
                );
            }

            $this->db->query('COMMIT');

            // Set session messages
            if (!empty($warnings)) {
                $_SESSION['warning'] = implode("<br>", $warnings);
            } else {
                $_SESSION['success'] = 'Accessories assigned successfully.';
            }

            error_log("Transaction committed successfully.");
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            error_log("Transaction failed: " . $e->getMessage());
        }

        error_log("Final Session Messages: " . print_r($_SESSION, true));
        echo "<script>location.replace(document.referrer);</script>";
        exit();
    }

    public function destroy()
    {
        $AccessoriesID = $this->sanitize_input($_POST['AccessoriesID'] ?? '');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '');
        $AccessoriesName = $this->sanitize_input($_POST['AccessoriesName'] ?? '');

        try {
            $this->db->query("BEGIN");

            $stmt = $this->db->query(
                "DELETE FROM accessories_temp WHERE AccessoriesID = ?",
                [$AccessoriesID]
            );

            if ($stmt) {
                $this->db->query("COMMIT");
                $_SESSION['success'] = $AccessoriesName . ' - ' . $Brand . ' has been deleted!';
            } else {
                $this->db->query("ROLLBACK");
                $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
            }
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
        }

        header("Location: /accessories");
        exit();
    }
    // Accessories Return function
    public function delete()
    {
        $AccessoriesID = $this->sanitize_input($_POST['AccessoriesID'] ?? '');
        $PRNumber = $this->sanitize_input($_POST['PRNumber'] ?? '', 'upper');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $AccessoriesName = $this->sanitize_input($_POST['AccessoriesName'] ?? '', 'ucwords');
        $Status = $this->sanitize_input($_POST['Status'] ?? '', 'ucwords');
        $EmployeeID = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $date = date('Y-m-d H:i:s');

        if (empty($AccessoriesID) || empty($AccessoriesName) || empty($Status)) {
            $_SESSION['error'] = 'Missing required fields: PRNumber, Brand, or AccessoriesName.';
            header("Location: /employee/custody/" . urlencode($EmployeeID));
            return;
        }

        try {
            $update = $this->db->query("UPDATE accessories SET Qty = Qty + 1 WHERE AccessoriesID = ? AND AccessoriesName = ?", [$AccessoriesID, $AccessoriesName]);

            if ($update->rowCount() === 0) {
                $_SESSION['warning'] = 'No matching accessory found to update stock.';
                return;
            }

            $updateAssignments = $this->db->query(
                "UPDATE accessories_assignments SET Status = ?, updated_at = ? WHERE AccessoriesID = ?",
                [$Status, $date, $AccessoriesID]
            );

            if ($updateAssignments->rowCount() > 0) {
                $_SESSION['success'] = 'Accessory assignment returned and stock updated successfully.';
            } else {
                $_SESSION['warning'] = 'Stock updated, but no assignment was found to update.';
            }
        } catch (Exception $e) {
            error_log("Error in update function: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while processing the request.' . $e->getMessage();
        }

        header("Location: /employee/custody/" . urldecode($EmployeeID));
        exit();
    }

    public function defective()
    {
        $AccessoriesID = $this->sanitize_input($_POST['AccessoriesID'] ?? '');
        $PRNumber = $this->sanitize_input($_POST['PRNumber'] ?? '', 'upper');
        $Brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $AccessoriesName = $this->sanitize_input($_POST['AccessoriesName'] ?? '', 'ucwords');
        $Defective = $this->sanitize_input($_POST['Defective'] ?? '');
        $date = date('Y-m-d H:i:s');

        if (empty($PRNumber) || empty($Brand) || empty($AccessoriesName)) {
            $_SESSION['error'] = 'Missing required fields: PRNumber, Brand, or AccessoriesName.';
            return;
        }

        if (!ctype_digit($Defective) || (int) $Defective <= 0) {
            $_SESSION['error'] = 'Invalid defective quantity.';
            return;
        }
        
        $currentStock = $this->db->query("SELECT Qty FROM accessories WHERE AccessoriesID = ?", [$AccessoriesID])->fetchColumn();
        if ($currentStock === false) {
            $_SESSION['error'] = 'Accessory not found for stock validation.';
            return;
        }

        if ((int) $Defective > (int) $currentStock) {
            $_SESSION['error'] = 'Defective quantity exceeds available stock.';
            return;
        }

        try {
            $update = $this->db->query("UPDATE accessories SET Qty = Qty - ?, DefectiveCount = DefectiveCount + ?, UpdatedAt = ? WHERE AccessoriesID = ? AND PRNumber = ? AND AccessoriesName = ? AND Brand = ?", [$Defective, $Defective, $date, $AccessoriesID, $PRNumber, $AccessoriesName, $Brand]);

            if ($update->rowCount() === 0) {
                $_SESSION['warning'] = 'No matching accessory found to update stock.';
                return;
            } else {
                $_SESSION['success'] = "The selected accessory '$AccessoriesName' (Brand: $Brand) under PR number '$PRNumber' has been marked as defective.";
            }

        } catch (Exception $e) {
            error_log("Error in update function: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while processing the request.' . $e->getMessage();
        }

        header("Location: /accessories");
        exit();
    }
}