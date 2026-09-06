<?php
namespace Controllers;

use Exception;
use Models\PartsModel;
use System\Core\Controller;

class PartsController extends Controller
{
    private PartsModel $parts;

    public function __construct()
    {
        $this->parts = new PartsModel();
    }

    private function getExcludedPartTypes()
    {
        $excludedPartTypes = ["Processor", "Motherboard", "GPU", "Keyboard", "Mouse", "Webcam", "Pen Display", "Pen Tablet", "Headset", "Power Supply"];

        return $this->parts->excludedPartTypes($excludedPartTypes);
    }
    private function getAllPartsWithHistory()
    {
        return $this->parts->allParts();
    }
    private function getAvailableParts($excludedTypes, $tempPartIDs)
    {
        return $this->parts->availableParts($excludedTypes);
    }
    public function index()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $types = $this->parts->partTypes();
        $tempPart = $this->parts->temporaryUpdateParts();
        $tempPartIDs = array_column($tempPart, 'PartID');
        $tempPC = $this->parts->temporaryComputer();
        $temp_part = $this->parts->stagedParts();
        $parts_data = $this->parts->partsWithCurrentHistory();

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
            if (!$this->parts->serialExistsInInventoryOrStage($fullSerial)) {
                return $newSerial;
            }
            $counter++;
        }
    }

    private function generateNextUniqueID($partType)
    {
        $cleanedPartType = str_replace(' ', '', $partType);
        $nextNumber = $this->parts->nextPartNumber($cleanedPartType, $partType);
        return $cleanedPartType . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
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
        if ($this->parts->serialExistsInInventoryOrStage($SerialNumber)) {
            $_SESSION['error'] = "The serial number '$SerialNumber' was already registered.";
            $_SESSION['old_input'] = $_POST;
            header("Location: /parts");
            exit();
        }

        // Insert into temporary_storage_parts
        try {
            $this->parts->stagePart(compact('PRNumber', 'PartType', 'Brand', 'Model', 'SerialNumber', 'created_at'));
            $_SESSION['success'] = "$Brand $Model was successfully added!";
            unset($_SESSION['old_input']);
        } catch (Exception $exception) {
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
                if ($this->parts->serialExists($part['PartType'], $part['SerialNumber'])) {
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
            try {
                $this->parts->transaction(function () use ($parts, $created_at): void {
                    foreach ($parts as $part) {
                        $uniqueID = $this->sanitize_input($this->generateNextUniqueID($part['PartType']), 'upper');
                        $this->parts->createPart($part + ['uniqueID' => $uniqueID, 'created_at' => $created_at]);
                    }
                    $this->parts->clearStagedParts();
                });
                $_SESSION['success'] = 'All items were successfully added!';
            } catch (Exception $e) {
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

        $part = $this->parts->find((int) $id);
        $partData = $part ? [$part] : [];

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
            $this->parts->updatePart((int) $PartID, compact('uniqueID', 'Brand', 'Model', 'SerialNumber'), $date);
            $_SESSION['success'] = "$uniqueID's information updated successfully!";
        } catch (Exception $e) {
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
            if ($this->parts->updateStatus((int) $id, $status, $updated_at) >= 0) {
                $_SESSION['success'] = $name . ' status updated!';
            } else {
                $_SESSION['error'] = 'There was a problem updating the status of ' . $name . '! Please try again!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'There was a problem updating the status of ' . $name . '! Please try again!';
        }

        header("Location: /parts");
        exit();
    }
}
