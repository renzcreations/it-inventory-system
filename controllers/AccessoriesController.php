<?php

namespace Controllers;

use Exception;
use Models\AccessoriesModel;
use System\Core\Controller;

class AccessoriesController extends Controller
{
    public function __construct(private ?AccessoriesModel $accessories = null)
    {
        $this->accessories ??= new AccessoriesModel();
    }

    public function index(): void
    {
        $history = $this->accessories->assignmentHistory();
        $groupedHistory = [];
        foreach ($history as $item) {
            $id = $item['AccessoriesID'];
            $groupedHistory[$id] ??= [
                'AccessoriesID' => $id,
                'AccessoriesName' => $item['AccessoriesName'],
                'Brand' => $item['Brand'],
                'PRNumber' => $item['PRNumber'],
                'Qty' => $item['Qty'],
                'Defective' => $item['Defective'],
                'assignments' => [],
            ];

            if (!empty($item['EmployeeID']) && $item['assignmentStatus'] === 'Assigned') {
                $groupedHistory[$id]['assignments'][] = [
                    'EmployeeID' => $item['EmployeeID'],
                    'FirstName' => $item['FirstName'],
                    'LastName' => $item['LastName'],
                    'PRNumber' => $item['PRNumber'],
                    'Status' => $item['assignmentStatus'],
                ];
            }
        }

        $allAccessories = $this->accessories->allAccessories();
        $brandsByAccessory = $this->groupBrands($allAccessories);

        $returnGroupHistory = [];
        foreach ($this->accessories->returnHistory() as $item) {
            $id = $item['AccessoriesID'];
            $returnGroupHistory[$id] ??= [
                'AccessoriesID' => $id,
                'AccessoriesName' => $item['AccessoriesName'],
                'Brand' => $item['Brand'],
                'PRNumber' => $item['PRNumber'],
                'return' => [],
            ];

            $returnGroupHistory[$id]['return'][] = [
                'EmployeeID' => $item['EmployeeID'],
                'FirstName' => $item['FirstName'],
                'LastName' => $item['LastName'],
                'PRNumber' => $item['PRNumber'],
                'Status' => $item['Status'],
            ];
        }

        $returnedAccessories = $this->accessories->returnedAccessories();
        $this->view('pages/accessories', [
            'title' => 'Accessories',
            'accessories' => $allAccessories,
            'history' => array_values($groupedHistory),
            'returnGroupHistory' => array_values($returnGroupHistory),
            'accessoriesNameFilter' => $this->accessories->accessoryNames(),
            'brandsByAccessory' => $brandsByAccessory,
            'accessories_temp' => $this->accessories->stagedAccessories(),
            'returnAccessoriesNameFilter' => $this->accessories->returnedAccessoryNames(),
            'returnBrandAccessory' => $this->groupBrands($returnedAccessories),
            'returnAccessoriesStmt' => $returnedAccessories,
        ]);
    }

    public function create(): void
    {
        $prNumber = $this->sanitize_input($_POST['PRNumber'] ?? '', 'upper') ?: null;
        $name = $this->sanitize_input($_POST['AccessoriesName'] ?? '', 'ucwords');
        $brand = $this->sanitize_input($_POST['Brand'] ?? '', 'ucwords') ?: 'No Brand';
        $quantity = filter_var($_POST['Quantity'] ?? '', FILTER_VALIDATE_INT);

        if ($name === '' || $quantity === false || $quantity <= 0) {
            $_SESSION['warning'] = 'Enter an accessory and a positive quantity.';
            $_SESSION['accessories_old_input'] = $_POST;
            $this->redirect('/accessories');
        }

        try {
            $this->accessories->stage([
                'AccessoriesName' => $name,
                'Brand' => $brand,
                'PRNumber' => $prNumber,
                'Qty' => $quantity,
                'CreatedAt' => date('Y-m-d H:i:s'),
            ]);
            unset($_SESSION['accessories_old_input']);
            $_SESSION['success'] = "{$name} ({$brand}) was added to the staging list.";
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to stage the accessory: ' . $exception->getMessage();
        }

        $this->redirect('/accessories');
    }

    public function store(): void
    {
        try {
            $count = $this->accessories->storeStaged(date('Y-m-d H:i:s'));
            if ($count === 0) {
                $_SESSION['warning'] = 'No accessories found in the temporary list.';
            } else {
                unset($_SESSION['accessories_old_input']);
                $_SESSION['success'] = 'Accessories have been successfully stored!';
            }
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to store accessories: ' . $exception->getMessage();
        }

        $this->redirect('/accessories');
    }

    public function assign(): void
    {
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');
        $selections = is_array($_POST['Accessories'] ?? null) ? $_POST['Accessories'] : [];

        if ($employeeId === '' || $selections === []) {
            $_SESSION['warning'] = 'Select at least one accessory.';
            $this->redirectBack('/employee/custody/' . rawurlencode($employeeId));
        }

        try {
            $warnings = $this->accessories->assign($employeeId, $selections, date('Y-m-d H:i:s'));
            $_SESSION[$warnings === [] ? 'success' : 'warning'] = $warnings === []
                ? 'Accessories assigned successfully.'
                : implode('<br>', $warnings);
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to assign accessories: ' . $exception->getMessage();
        }

        $this->redirectBack('/employee/custody/' . rawurlencode($employeeId));
    }

    public function destroy(): void
    {
        $accessoryId = (int) ($_POST['AccessoriesID'] ?? 0);
        $label = trim(($_POST['AccessoriesName'] ?? '') . ' - ' . ($_POST['Brand'] ?? ''), ' -');

        try {
            $this->accessories->removeStaged($accessoryId);
            $_SESSION['success'] = "{$label} has been removed.";
        } catch (Exception $exception) {
            $_SESSION['error'] = "Unable to remove {$label}.";
        }
        $this->redirect('/accessories');
    }

    public function delete(): void
    {
        $accessoryId = (int) ($_POST['AccessoriesID'] ?? 0);
        $employeeId = $this->sanitize_input($_POST['EmployeeID'] ?? '');

        if ($accessoryId <= 0 || $employeeId === '') {
            $_SESSION['error'] = 'Missing accessory or employee information.';
            $this->redirect('/employee/custody/' . rawurlencode($employeeId));
        }

        try {
            $returned = $this->accessories->returnAssignment($accessoryId, $employeeId, date('Y-m-d H:i:s'));
            $_SESSION[$returned ? 'success' : 'warning'] = $returned
                ? 'Accessory returned and stock updated successfully.'
                : 'No active accessory assignment was found.';
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to return the accessory: ' . $exception->getMessage();
        }

        $this->redirect('/employee/custody/' . rawurlencode($employeeId));
    }

    public function defective(): void
    {
        $accessoryId = (int) ($_POST['AccessoriesID'] ?? 0);
        $prNumber = $this->sanitize_input($_POST['PRNumber'] ?? '', 'upper');
        $brand = $this->sanitize_input($_POST['Brand'] ?? '', 'upper');
        $name = $this->sanitize_input($_POST['AccessoriesName'] ?? '', 'ucwords');
        $quantity = filter_var($_POST['Defective'] ?? null, FILTER_VALIDATE_INT);

        if ($accessoryId <= 0 || $prNumber === '' || $brand === '' || $name === '' || $quantity === false || $quantity <= 0) {
            $_SESSION['error'] = 'Provide a valid accessory and defective quantity.';
            $this->redirect('/accessories');
        }

        try {
            $updated = $this->accessories->markDefective(
                $accessoryId,
                $quantity,
                $prNumber,
                $name,
                $brand,
                date('Y-m-d H:i:s')
            );
            $_SESSION[$updated ? 'success' : 'warning'] = $updated
                ? "{$name} ({$brand}) was marked defective."
                : 'Accessory not found or defective quantity exceeds stock.';
        } catch (Exception $exception) {
            $_SESSION['error'] = 'Unable to update accessory stock: ' . $exception->getMessage();
        }

        $this->redirect('/accessories');
    }

    private function groupBrands(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $name = $item['AccessoriesName'];
            $groups[$name] ??= [];
            if (!in_array($item['Brand'], $groups[$name], true)) {
                $groups[$name][] = $item['Brand'];
            }
        }
        return $groups;
    }
}
