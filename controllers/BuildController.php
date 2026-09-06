<?php
namespace Controllers;

use Exception;
use Models\BuildModel;
use System\Core\Controller;

class BuildController extends Controller
{
    private BuildModel $builds;

    public function __construct()
    {
        $this->builds = new BuildModel();
    }
    public function index()
    {
        $tempPart = $this->builds->stagedParts();

        $excludedPartTypesList = ["Processor", "Motherboard", "GPU", "Keyboard", "Mouse", "Webcam", "Pen Display", "Pen Tablet", "Headset", "Power Supply"];
        $excludedTypes = $this->builds->excludedPartTypes($excludedPartTypesList);
        $parts = $this->builds->availableParts($excludedTypes);
        $type = $this->builds->availablePartTypeDates($excludedTypes);
        $partTypes = $this->builds->availablePartTypes($excludedTypes);

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
            $this->builds->stagePart([
                'PartID' => $PartID,
                'PartType' => $PartType,
                'Brand' => $Brand,
                'Model' => $Model,
                'SerialNumber' => $SerialNumber,
                'created_at' => $created_at,
            ]);
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

            echo json_encode([
                'available' => !$this->builds->computerNameExists($name),
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
            $partIds = array_map('intval', $_POST['PartID']);
            $this->builds->buildComputer($PCName, $partIds, $created_at);

            $_SESSION['success'] = htmlspecialchars($PCName, ENT_QUOTES, 'UTF-8') . ' created successfully!';
            header("Location: /build");
            exit();

        } catch (Exception $e) {
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
            if ($this->builds->removeStagedPart((int) $PartID) >= 0) {
                $_SESSION['success'] = $Brand . ' has been deleted!';
            } else {
                $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'There was a problem updating the status of ' . $Brand . '! Please try again!';
        }

        header("Location: /build");
        exit();
    }
}
