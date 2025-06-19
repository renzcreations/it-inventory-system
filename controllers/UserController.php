<?php
namespace Controllers;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use System\Core\Database;
use System\Core\Controller;
use PDO;
use ZipArchive;

class UserController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function welcome()
    {
        $this->view('welcome', [
            'title' => 'Welcome',
            'layout' => 'guest'
        ]);
    }

    public function loginForm()
    {
        $this->view('auth/login', [
            'title' => 'Login',
            'layout' => 'guest'
        ]);
    }

    public function login()
    {
        $username = $this->sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Track login attempts
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt'] = 0;
        }

        $maxAttempts = 5;
        $lockoutDuration = 360;
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['last_attempt']) < $lockoutDuration) {
            $timeLeft = $lockoutDuration - (time() - $_SESSION['last_attempt']);
            $minutesLeft = ceil($timeLeft / 60);
            $_SESSION['warning'] = "Too many failed login attempts. Please try again in $minutesLeft minute(s).";
            header('Location: /login');
            exit();
        }

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: /login');
            exit();
        }

        $user = $this->db->query("SELECT * FROM users WHERE username = ?", [$username])->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_attempt'] = time();
            $attemptsLeft = $maxAttempts - ($_SESSION['login_attempts'] ?? 0);
            if ($attemptsLeft > 0) {
                $_SESSION['login_old_input'] = $_POST;
                $_SESSION['error'] = "Invalid credentials. You have $attemptsLeft attempt(s) left.";
            } else {
                $timeLeft = $lockoutDuration - (time() - ($_SESSION['last_attempt'] ?? 0));
                $minutesLeft = ceil($timeLeft / 60);
                unset($_SESSION['login_old_input']);
                $_SESSION['error'] = "Too many failed login attempts. Please try again in $minutesLeft minute(s).";
            }
            header('Location: /login');
            exit();
        }

        if (!$user['email_code']) {
            $_SESSION['warning'] = 'Please contact the IT administrator for more information.';
            header('Location: /login');
            exit();
        }

        $_SESSION['login_attempts'] = 0;
        session_regenerate_id(true);
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        // Remember Me
        if (isset($_POST['rememberMe'])) {
            $rememberToken = bin2hex(random_bytes(32));
            setcookie("remember_token", $rememberToken, time() + (86400 * 30), "/");
            echo "<script>localStorage.setItem('remember_token', '$rememberToken');</script>";
        }

        // Update logged_date
        $currentTimestamp = (new DateTime())->format('Y-m-d H:i:s');
        $this->db->query("UPDATE users SET logged_date = ? WHERE username = ?", [$currentTimestamp, $username]);

        header('Location: /dashboard');
        exit();
    }

    // public function registerForm()
    // {
    //     $this->view('auth/register', [
    //         'title' => 'Register',
    //         'layout' => 'guest'
    //     ]);
    // }

    public function register()
    {
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $email = $this->sanitize_input($_POST['email'] ?? '');
        $username = $this->sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $code = $this->sanitize_input($_POST['email_code'] ?? '');

        $errors = [];

        // Validate name
        if (empty($name) || strlen($name) < 10) {
            $errors[] = 'Name is required and must be at least 10 characters.';
        }

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        // Validate username
        if (empty($username) || strlen($username) < 6 || strlen($username) > 20) {
            $errors[] = 'Username is required and must be 6-20 characters.';
        }

        $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/";

        if (empty($password) || !preg_match($passwordPattern, $password)) {
            $errors[] = 'Password must be at least 12 characters and include uppercase, lowercase, number, and special character.';
        }


        // Password confirmation
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        $check = $this->db->query("SELECT * FROM register WHERE email_code = ?", [$code]);
        if ($check && $check->rowCount() !== 1) {
            $_SESSION['error'] = 'Invalid or expired registration code';
            header('Location: /register/' . urldecode($code));
            exit();
        }

        $user = $check->fetch(PDO::FETCH_ASSOC);

        if ($email !== $user['email']) {
            $_SESSION['error'] = 'Email does not match registration invitation';
            header('Location: /register/' . urldecode($code));
            exit();
        }

        // Check for existing email or username
        $existing = $this->db->query(
            "SELECT id FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        )->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $errors[] = 'Email or username already exists.';
        }

        if (!empty($errors)) {
            $_SESSION['register_old_input'] = $_POST;
            $_SESSION['error'] = implode('<br><br>', $errors);
            header('Location: /register/' . urldecode($code));
            exit();
        }

        $user = [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email_code' => $code,
        ];

        $this->db->query(
            "INSERT INTO users (name, email, email_code, username, password) VALUES (?, ?, ?, ?, ?)",
            array_values($user)
        );

        $this->db->query("DELETE FROM register WHERE email_code = ?", [$code]);

        $_SESSION['success'] = 'Registration successful! Please login';
        unset($_SESSION['register_old_input']);
        header('Location: /login');
        exit();
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit();
    }

    public function dashboard()
    {
        $employeeData = $this->db->query("SELECT * FROM employees ORDER BY signature_upload_date DESC LIMIT 5");
        $employees = $employeeData ? $employeeData->fetchAll(PDO::FETCH_ASSOC) : [];

        $partsCount = $this->getPartsCount();
        $signedCount = $this->getSignedCount();

        $accessoriesCount = $this->db->query("SELECT AccessoriesName, SUM(Qty) as totalQty, SUM(AssignedCount) as totalAssigned, SUM(DefectiveCount) as totalDefective FROM accessories GROUP BY AccessoriesName");
        $accessories = $accessoriesCount->fetchAll(PDO::FETCH_ASSOC);

        $this->view('dashboard', [
            'title' => 'Dashboard',
            'employees' => $employees,
            'availableCount' => $partsCount['availableCount'] ?? 0,
            'inUseCount' => $partsCount['inUseCount'] ?? 0,
            'defectiveCount' => $partsCount['defectiveCount'] ?? 0,
            'signedCount' => $signedCount['signed'] ?? 0,
            'unsignedCount' => $signedCount['unsigned'] ?? 0,
            'accessories' => $accessories,
        ]);
    }

    public function getPartsCount()
    {
        $partsData = $this->db->query("SELECT Status, COUNT(*) AS count FROM parts GROUP BY Status");
        $parts = [];
        while ($row = $partsData->fetch(PDO::FETCH_ASSOC)) {
            $parts[] = $row;
        }

        $availableCount = 0;
        $inUseCount = 0;
        $defectiveCount = 0;

        foreach ($parts as $part) {
            if ($part['Status'] === "Available") {
                $availableCount = $part['count'];
            } elseif ($part['Status'] === "In Use") {
                $inUseCount = $part['count'];
            } elseif ($part['Status'] === "Defective") {
                $defectiveCount = $part['count'];
            }
        }

        return [
            'availableCount' => $availableCount === 0 ? 'No Available' : $availableCount,
            'inUseCount' => $inUseCount === 0 ? 'No In Use' : $inUseCount,
            'defectiveCount' => $defectiveCount === 0 ? 'No Defective' : $defectiveCount,
        ];
    }

    public function getSignedCount()
    {
        $signedData = $this->db->query("SELECT Signature, COUNT(*) AS count FROM employees WHERE Status = 'Active' GROUP BY Signature");
        $signed = 0;
        $unsigned = 0;
        $rows = $signedData->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $data) {
            if ($data['Signature'] !== null && $data['Signature'] !== '') {
                $signed += $data['count'];
            } else {
                $unsigned += $data['count'];
            }
        }

        return [
            'signed' => $signed,
            'unsigned' => $unsigned
        ];
    }

    public function profile()
    {
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        )->fetch(PDO::FETCH_ASSOC);

        $company = $this->db->query("SELECT * FROM company_details");
        $data = $company->fetchAll(PDO::FETCH_ASSOC);

        $this->view('profile/profile', [
            'title' => 'Profile',
            'data' => $data,
            'user' => $user
        ]);
    }

    public function update()
    {
        $old_username = $_SESSION['username'];
        $date = date('Y-m-d H:i:s');
        $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $username = $this->sanitize_input($_POST['username'] ?? '');

        try {
            $this->db->query("BEGIN");
            $updates = [];
            $params = [];
            $types = "";

            if (!empty($name)) {
                $updates[] = "name = ?";
                $params[] = $name;
                $types .= "s";
            }
            if (!empty($email)) {
                $updates[] = "email = ?";
                $params[] = $email;
                $types .= "s";
            }
            if (!empty($username)) {
                $updates[] = "username = ?";
                $params[] = $username;
                $types .= "s";
            }

            if (empty($updates)) {
                $_SESSION['warning'] = "No changes detected.";
                $this->db->query("COMMIT");
                header("Location: /profile");
                exit();
            }

            $updates[] = "updated_at = ?";
            $params[] = $date;
            $types .= "s";

            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE username = ?";
            $params[] = $old_username;
            $types .= "s";

            $stmt = $this->db->query($sql, $params); // Removed the array wrapper around $params

            if (!empty($username) && $username !== $old_username) {
                unset($_SESSION['username']);
                $_SESSION['username'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            }

            $_SESSION['success'] = "Profile updated successfully!";
            $this->db->query("COMMIT");
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information." . $e->getMessage();
        }

        header("Location: /profile");
        exit();
    }

    public function password()
    {
        $username = $_SESSION['username'];
        $oldPassword = $this->sanitize_input($_POST['oldPassword']);
        $password = $this->sanitize_input($_POST['password']);
        $confirmPassword = $this->sanitize_input($_POST['confirmPassword']);

        if (empty($oldPassword) || empty($password) || empty($confirmPassword)) {
            $_SESSION['warning'] = 'All fields are required!';
            header("Location: /profile");
            exit();
        }

        $stmt = $this->db->query("SELECT password FROM users WHERE username = ?", [$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $storedHashedPassword = $result['password'];

        if (!password_verify($oldPassword, $storedHashedPassword)) {
            $_SESSION['warning'] = 'Old password is incorrect!';
            header("Location: /profile");
            exit();
        }

        if ($password !== $confirmPassword) {
            $_SESSION['warning'] = 'Passwords do not match.';
            header("Location: /profile");
            exit();
        }

        $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/";

        if (!preg_match($passwordPattern, $password)) {
            $_SESSION['warning'] = 'Password must be at least 12 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
            header("Location: /profile");
            exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updated_at = date('Y-m-d H:i:s');

        try {
            $updatePassword = $this->db->query("UPDATE users SET password = ?, updated_at = ? WHERE username = ?", [$hashedPassword, $updated_at, $username]);
            $_SESSION['success'] = "Password updated!";
        } catch (Exception $e) {
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information." . $e->getMessage();
        }

        header("Location: /profile");
        exit();
    }

    public function signature()
    {
        $username = $this->sanitize_input($_POST['username'] ?? '');
        if (empty($username)) {
            $_SESSION['warning'] = 'Invalid user!';
            header("Location: /profile");
            exit();
        }

        if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['warning'] = 'Please select a valid image file!';
            header("Location: /profile");
            exit();
        }

        $file = $_FILES['signature'];
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxFileSize) {
            $_SESSION['warning'] = 'File too large. Maximum 2MB allowed.';
            header("Location: /profile");
            exit();
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($fileExtension, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
            $_SESSION['warning'] = 'Invalid file type. Only JPEG, JPG, PNG, and WEBP are allowed.';
            header("Location: /profile");
            exit();
        }

        $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $username);
        $newFileName = $safeName . '.' . $fileExtension;
        $uploadDir = __DIR__ . '/../Signature/Admin/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error'] = 'Failed to create upload directory!';
                header("Location: /profile");
                exit();
            }
        }

        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['error'] = 'Failed to upload signature!';
            header("Location: /profile");
            exit();
        }

        // Maintain your $_ENV usage here
        $fullPath = $_ENV['APP_URL'] . 'Signature/Admin/' . $newFileName;
        $date = date('Y-m-d H:i:s');

        try {
            $this->db->query('BEGIN');

            // Get old signature path for cleanup
            $stmt = $this->db->query("SELECT signature FROM users WHERE username = ?", [$username]);
            $oldSignature = $stmt->fetchColumn();

            // Update with full path including APP_URL
            $this->db->query(
                "UPDATE users SET signature = ?, updated_at = ? WHERE username = ?",
                [$fullPath, $date, $username]
            );

            // Clean up old file if it exists
            if ($oldSignature) {
                $oldPath = str_replace($_ENV['APP_URL'], '', $oldSignature);
                $oldFilePath = __DIR__ . '/..' . $oldPath;
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $this->db->query('COMMIT');
            $_SESSION['success'] = 'Signature uploaded successfully!';
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            $_SESSION['error'] = 'Error saving signature: ' . $e->getMessage();
            error_log('Signature upload error: ' . $e->getMessage());
        }

        header("Location: /profile");
        exit();
    }

    public function backup()
    {
        // Authentication check
        if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit();
        }

        $userId = $_SESSION['user_id'];
        $currentTime = new DateTime();
        $isManualBackup = isset($_GET['manual']);

        try {
            // Get user's backup history
            $userStmt = $this->db->query("SELECT backup_date FROM users WHERE id = ?", [$userId]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                throw new Exception("User not found");
            }

            $lastManualBackup = $userData['backup_date'];

            // Backup frequency validation
            if ($isManualBackup) {
                if ($lastManualBackup) {
                    $lastBackupTime = new DateTime($lastManualBackup);
                    $diffHours = ($currentTime->getTimestamp() - $lastBackupTime->getTimestamp()) / 3600;
                    if ($diffHours < 5) {
                        $_SESSION['error'] = "Manual backup allowed only every 5 hours.";
                        header("Location: /");
                        exit();
                    }
                }
            }

            // Generate SQL dump
            $sqlDump = "-- HPL Database Backup\n";
            $sqlDump .= "-- Generated: " . $currentTime->format('Y-m-d H:i:s') . "\n";
            $sqlDump .= "-- Generated by: " . $_SESSION['username'] . "\n\n";
            $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            // Get all tables
            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $tableName) {
                // Table structure
                $createStmt = $this->db->query("SHOW CREATE TABLE `$tableName`");
                $createTable = $createStmt->fetch(PDO::FETCH_NUM);

                $sqlDump .= "--\n-- Structure for table `$tableName`\n--\n";
                $sqlDump .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sqlDump .= $createTable[1] . ";\n\n";

                // Table data - fetch in chunks to handle large tables
                $offset = 0;
                $limit = 1000; // Process 1000 rows at a time
                $hasMoreData = true;

                while ($hasMoreData) {
                    $dataStmt = $this->db->query("SELECT * FROM `$tableName` LIMIT $offset, $limit");
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        $hasMoreData = false;
                        break;
                    }

                    if ($offset === 0) {
                        $sqlDump .= "--\n-- Data for table `$tableName`\n--\n";
                    }

                    foreach ($rows as $data) {
                        $columns = array_map(fn($col) => "`$col`", array_keys($data));
                        $values = array_map(function ($value) {
                            if ($value === null)
                                return 'NULL';
                            if (is_bool($value))
                                return $value ? '1' : '0';
                            // Use parameterized query to safely escape the value
                            $tempStmt = $this->db->query("SELECT ? as val", [$value]);
                            $escaped = $tempStmt->fetch(PDO::FETCH_COLUMN);
                            return "'" . str_replace("'", "''", $escaped) . "'";
                        }, array_values($data));

                        $sqlDump .= "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }

                    $offset += $limit;
                }

                if ($offset > 0) {
                    $sqlDump .= "\n";
                }
            }

            $sqlDump .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

            // Create backup directory if it doesn't exist
            $backupDir = __DIR__ . "/../backups/";
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0755, true)) {
                    throw new Exception("Failed to create backup directory");
                }
            }

            // Save SQL file
            $sqlFilename = 'HPL_Backup_' . date('Y-m-d_His') . '.sql';
            $sqlFilePath = $backupDir . $sqlFilename;

            if (file_put_contents($sqlFilePath, $sqlDump) === false) {
                throw new Exception("Failed to create backup file");
            }

            // Create ZIP archive
            $zipFilename = str_replace('.sql', '.zip', $sqlFilename);
            $zipFilePath = $backupDir . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception("Failed to create ZIP file");
            }

            $zip->addFile($sqlFilePath, $sqlFilename);
            if (!$zip->close()) {
                throw new Exception("Failed to finalize ZIP file");
            }
            unlink($sqlFilePath);

            $downloadLink = $_ENV['APP_URL'] . 'backups/' . $zipFilename;

            // Get admin details for email
            $adminStmt = $this->db->query("SELECT name, email FROM users WHERE type = 'Administrator' LIMIT 1");
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                throw new Exception("No administrator found");
            }

            $adminName = $admin['name'];
            $adminEmail = $admin['email'];

            $htmlContent = "
                            <html>
                            <head>
                                <meta charset='UTF-8'>
                                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                                <title>Database Backup</title>
                            </head>
                            <body style='font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; letter-spacing: 1px; background-color: #000000; padding: 20px; text-align: center; color: #f0f0f0;'>

                                <h1 style='color: #f44336;'>Hello, {$_SESSION['username']}!</h1>
                                
                                <p style='font-size: 16px; color: #cccccc;'>
                                Your database backup is ready. Click the button below to download it.
                                </p>

                                <p>
                                <a href='{$downloadLink}' target='_blank' style='
                                    display: inline-block;
                                    background-color: #f44336;
                                    color: #ffffff;
                                    padding: 12px 20px;
                                    font-size: 16px;
                                    font-weight: bold;
                                    text-decoration: none;
                                    border-radius: 5px;
                                    letter-spacing: 1px;
                                    margin-top: 10px;
                                '>Download Backup</a>
                                </p>

                                <div style='margin-top: 30px; font-size: 14px; color: #aaaaaa;'>
                                <p>Regards,</p>
                                <p>{$adminName}</p>
                                <strong style='color: #f44336;'>HPL IT Team</strong>
                                </div>

                            </body>
                            </html>";


            // Send email using Brevo
            $config = Configuration::getDefaultConfiguration();
            $config->setApiKey('api-key', $_ENV['BREVO_API']);

            $apiInstance = new TransactionalEmailsApi(new Client(), $config);

            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => 'Database Backup - ' . $currentTime->format('F j, Y h:i A'),
                'sender' => [
                    'name' => 'HPL Backup System',
                    'email' => $_ENV['BREVO_EMAIL']
                ],
                'replyTo' => [
                    'name' => 'HPL Support',
                    'email' => $adminEmail
                ],
                'to' => [
                    [
                        'name' => $_SESSION['username'],
                        'email' => $_SESSION['email']
                    ]
                ],
                'htmlContent' => $htmlContent
            ]);

            $apiInstance->sendTransacEmail($sendSmtpEmail);

            $_SESSION['success'] = 'Backup emailed successfully!';

        } catch (Exception $e) {
            $_SESSION['error'] = "Backup process failed: " . $e->getMessage();
            error_log("Backup Error: " . $e->getMessage());
        }

        header("Location: /");
        exit();
    }

    public function users()
    {
        $usersQuery = $this->db->query("SELECT * FROM users ORDER BY type ASC");
        $users = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

        $invitedQuery = $this->db->query("SELECT * FROM register ORDER BY created_at ASC");
        $invited = $invitedQuery->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/invite-users', [
            'title' => 'Users',
            'users' => $users,
            'invited' => $invited,
        ]);
    }


    public function regenerateCode()
    {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $code = bin2hex(random_bytes(10));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['warning'] = 'Invalid email format.';
            header("Location: /users");
            exit();
        }

        $link = $_ENV['APP_URL'] . $code;


        $config = Configuration::getDefaultConfiguration();
        $config->setApiKey('api-key', $_ENV['BREVO_API']);

        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $getAdmin = $this->db->query("SELECT * FROM users WHERE type = 'Administrator'");
        if ($getAdmin && $getAdmin->rowCount() > 0) {
            $result = $getAdmin->fetch(PDO::FETCH_ASSOC);
            $name = $result['name'];
            $HPLSupport = $result['email'];
        } else {
            $_SESSION['error'] = 'No administrator found.';
            header("Location: /users");
            exit();
        }

        $registerName = strtolower($email);
        $htmlContent = "
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <title>HPL Inventory System Invitation</title>
                        </head>
                        <body style='font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; letter-spacing: 1px; background-color: #000000; padding: 20px; text-align: center; color: #f0f0f0;'>

                            <h1 style='color: #f44336;'>Hello, " . htmlspecialchars($registerName) . "!</h1>

                            <p style='font-size: 16px; color: #cccccc;'>
                            <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong> has requested to regenerate your access code.<br>
                            Please click the button below to continue:
                            </p>

                            <p style='margin: 25px 0;'>
                            <a href='" . htmlspecialchars($link, ENT_QUOTES) . "' target='_blank' style='
                                display: inline-block;
                                background-color: #f44336;
                                color: #ffffff;
                                padding: 12px 20px;
                                font-size: 16px;
                                font-weight: bold;
                                text-decoration: none;
                                border-radius: 5px;
                                letter-spacing: 1px;
                            '>Access HPL Inventory System</a>
                            </p>

                            <p style='font-size: 12px; color: #aaaaaa;'>
                            <i>If you have any questions or concerns, please contact the HPL IT Team. Thank you!</i>
                            </p>

                            <div style='margin-top: 30px; font-size: 14px; color: #bbbbbb;'>
                            <p>Best regards,</p>
                            <p>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
                            <p><strong style='color: #f44336;'>HPL IT Team</strong></p>
                            </div>

                        </body>
                        </html> ";

        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => 'Request for regenerated code for ' . $registerName,
            'sender' => [
                'name' => $name,
                'email' => $_ENV['BREVO_EMAIL']
            ],
            'replyTo' => [
                'name' => 'HPL Support',
                'email' => $HPLSupport
            ],
            'to' => [
                [
                    'name' => htmlspecialchars($registerName, ENT_QUOTES, 'UTF-8') ?: 'HPL User',
                    'email' => $email
                ]
            ],
            'htmlContent' => $htmlContent,
            'params' => [
                'link' => $link,
                'name' => $name
            ]
        ]);

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

            if ($result) {
                $currentTimestamp = (new DateTime())->format('Y-m-d H:i:s');
                $stmt = $this->db->query("UPDATE register SET email_code = ?, created_at = ? WHERE email = ?", [$code, $currentTimestamp, $email]);
                $_SESSION['success'] = 'Invitation sent!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information.";
        }

        header("Location: /users");
        exit();
    }

    public function updateType()
    {
        $username = $this->sanitize_input($_POST['username'] ?? '');
        $type = $this->sanitize_input($_POST['type'] ?? '', 'ucwords');
        $date = date('Y-m-d H:i:s');

        if (empty($username) || empty($type)) {
            $_SESSION['warning'] = 'Invalid input, please try again!';
            header("Location: /users");
            exit();
        }

        try {
            $this->db->query('BEGIN');

            $update = $this->db->query("UPDATE users SET type = ?, updated_at = ? WHERE username = ?", [$type, $date, $username]);
            $_SESSION['success'] = "$username role has been updated to $type.";
            $this->db->query('COMMIT');
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['failed'] = "There was a problem updating the role of $username, please try again or contact the web administrator for more information.";
        }
        header("Location: /users");
        exit();
    }

    public function removeCode()
    {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['warning'] = 'Invalid email format.';
            header("Location: /users");
            exit();
        }

        try {
            $this->db->query('BEGIN');

            $remove = $this->db->query("DELETE FROM register WHERE email = ?", [$email]);
            $_SESSION['success'] = "$email has been removed successfully";
            $this->db->query('COMMIT');
        } catch (Exception $e) {
            $this->db->query('ROLLBACK');
            $_SESSION['failed'] = "There was a problem removing $email, please try again or contact the web administrator for more information.";
        }

        header("Location: /users");
        exit();
    }

    public function sendCode()
    {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $code = bin2hex(random_bytes(10));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['warning'] = 'Invalid email format.';
            $_SESSION['send_code_old_input'] = $_POST;
            header("Location: /users");
            exit();
        }

        $stmt = $this->db->query("SELECT * FROM register WHERE email = ?", [$email]);

        if ($stmt && $stmt->rowCount() > 0) {
            $_SESSION['warning'] = 'Invitation code is already sent to this email address.';
            $_SESSION['send_code_old_input'] = $_POST;
            header("Location: /users");
            exit();
        }

        $existingUser = $this->db->query("SELECT email FROM users WHERE email = ?", [$email]);

        if ($existingUser && $existingUser->rowCount() > 0) {
            $_SESSION['warning'] = "$email is already registered.";
            unset($_SESSION['send_code_old_input']);
            header("Location: /users");
            exit();
        }

        $link = $_ENV['APP_URL'] . 'register/' . $code;

        $config = Configuration::getDefaultConfiguration();
        $config->setApiKey('api-key', $_ENV['BREVO_API']);

        $apiInstance = new TransactionalEmailsApi(
            new Client(),
            $config
        );

        $getAdmin = $this->db->query("SELECT * FROM users WHERE type = 'Administrator'");
        if ($getAdmin && $getAdmin->rowCount() > 0) {
            $result = $getAdmin->fetch(PDO::FETCH_ASSOC);
            $name = $result['name'];
            $HPLSupport = $result['email'];
        } else {
            $_SESSION['error'] = 'No administrator found.';
            $_SESSION['send_code_old_input'] = $_POST;
            header("Location: /users");
            exit();
        }
        $registerName = strtolower($email);
        $htmlContent = "
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <title>Email Invitation</title>
                        </head>
                        <body style='font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; letter-spacing: 1px; background-color: #000000; padding: 20px; text-align: center; color: #f0f0f0;'>

                            <h1 style='color: #f44336;'>Hello, " . htmlspecialchars($registerName) . "!</h1>

                            <p style='font-size: 16px; color: #cccccc;'>
                            You are invited by <strong><i>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</i></strong> to register to the <strong style='color: #f44336;'>HPL Inventory System</strong>.<br>
                            Click the button below to continue:
                            </p>

                            <p style='margin: 25px 0;'>
                            <a href='" . htmlspecialchars($link, ENT_QUOTES) . "' target='_blank' style='
                                display: inline-block;
                                background-color: #f44336;
                                color: #ffffff;
                                padding: 12px 20px;
                                font-size: 16px;
                                font-weight: bold;
                                text-decoration: none;
                                border-radius: 5px;
                                letter-spacing: 1px;
                            '>HPL Inventory System</a>
                            </p>

                            <p style='font-size: 12px; color: #aaaaaa;'>
                            <i>If you have any questions or concerns, please contact the HPL IT Team. Thank you!</i>
                            </p>

                            <div style='margin-top: 30px; font-size: 14px; color: #bbbbbb;'>
                            <p>Regards,</p>
                            <p>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
                            <p><strong style='color: #f44336;'>HPL IT Team</strong></p>
                            </div>

                        </body>
                        </html>
                        ";

        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => 'You are invited by ' . $name,
            'sender' => [
                'name' => $name,
                'email' => $_ENV['BREVO_EMAIL']
            ],
            'replyTo' => [
                'name' => 'HPL Support',
                'email' => $HPLSupport
            ],
            'to' => [
                [
                    'name' => $email,
                    'email' => $email
                ]
            ],
            'htmlContent' => $htmlContent,
            'params' => [
                'link' => $link,
                'name' => $name
            ]
        ]);

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

            if ($result) {
                $currentTimestamp = (new DateTime())->format('Y-m-d H:i:s');
                $stmt = $this->db->query("INSERT INTO register (email_code, email, created_at) VALUES (?, ?, ?)", [$code, $email, $currentTimestamp]);
                unset($_SESSION['send_code_old_input']);
                $_SESSION['success'] = 'Invitation sent!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "There's an error with the server, kindly contact the system administrator for more information." . $e->getMessage();
        }

        header("Location: /users");
        exit();
    }
    public function code($code)
    {
        if (!isset($code) || empty($code)) {
            $_SESSION['warning'] = 'Invalid invitational code, please contact the IT Team for more information.';
            header("Location: /users");
            exit();
        }

        $check = $this->db->query("SELECT * FROM register WHERE email_code = ?", [$code]);
        if ($check && $check->rowCount() !== 1) {
            $_SESSION['error'] = 'Please contact the IT Team for more information';
            header('Location: /users');
            exit();
        }

        $user = $check->fetch(PDO::FETCH_ASSOC);

        if ($code === $user['email_code']) {
            $_SESSION['code'] = $code;
        }

        $this->view('auth/register', [
            'title' => 'Register',
            'email_code' => $_SESSION['code'],
        ]);
    }
}