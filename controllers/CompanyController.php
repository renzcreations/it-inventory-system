<?php
namespace Controllers;

use Exception;
use Models\CompanyModel;
use System\Core\Controller;

class CompanyController extends Controller
{
    private CompanyModel $companies;

    public function __construct()
    {
        $this->companies = new CompanyModel();
    }

    public function add()
    {
        $date = date('Y-m-d H:i:s');
        $company_address = $this->sanitize_input($_POST['address'] ?? '', 'ucwords');
        $company_email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $company_contact = trim($_POST['contact']);

        if (empty($company_address) || empty($company_contact) || empty($company_email)) {
            $_SESSION['warning'] = 'All fields are required.';
            header("Location: /profile");
            exit();
        }

        if (!preg_match('/^\d{11}$/', $company_contact)) {
            $_SESSION['warning'] = 'Company phone number must be exactly 11 digits.';
            $_SESSION['company_old_input'] = $_POST;
            header("Location: /profile");
            exit();
        }

        try {
            $this->companies->create($company_address, $company_email, $company_contact, $date);
            unset($_SESSION['company_old_input']);

            $_SESSION['success'] = "Information saved successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error, kindly contact the system administrator for more information." . $e->getMessage();
        }

        header("Location: /profile");
        exit();
    }
    
    public function update()
    {
        $date = date('Y-m-d H:i:s');
        $address = $this->sanitize_input($_POST['address'] ?? '', 'ucwords');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $contact = $this->sanitize_input($_POST['contact'] ?? '');

        try {
            $this->companies->update(1, compact('address', 'email', 'contact'), $date);
            $_SESSION['success'] = "Company information updated successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error, kindly contact the system administrator for more information: " . $e->getMessage();
        }

        header("Location: /profile");
        exit();
    }
}
