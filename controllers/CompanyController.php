<?php
namespace Controllers;

use DateTime;
use Exception;
use System\Core\Database;
use System\Core\Controller;
use PDO;

class CompanyController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
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
            $this->db->query('BEGIN');

            $info = $this->db->query("INSERT INTO company_details (address, email, contact, created_at) VALUES (?, ?, ?, ?)", [$company_address, $company_email, $company_contact, $date]);
            $company_old_input = $_SESSION['company_old_input'] ?? [];
            unset($_SESSION['company_old_input']);

            $_SESSION['success'] = "Information saved successfully!";
            $this->db->query('COMMIT');
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
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

        $updates = [];
        $params = [];

        if (!empty($address)) {
            $updates[] = "address = ?";
            $params[] = $address;
        }
        if (!empty($email)) {
            $updates[] = "email = ?";
            $params[] = $email;
        }
        if (!empty($contact)) {
            $updates[] = "contact = ?";
            $params[] = $contact;
        }

        $updates[] = "updated_at = ?";
        $params[] = $date;

        try {
            $this->db->query("BEGIN");

            if (!empty($updates)) {
                $sql = "UPDATE company_details SET " . implode(", ", $updates) . " WHERE id = 1";
                $stmt = $this->db->query($sql, $params); // Pass only $params

                $_SESSION['success'] = "Company information updated successfully!";
            } else {
                $_SESSION['warning'] = "No changes detected.";
            }

            $this->db->query("COMMIT");
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "Database error, kindly contact the system administrator for more information: " . $e->getMessage();
        }

        header("Location: /profile");
        exit();
    }
}