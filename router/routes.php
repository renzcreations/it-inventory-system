<?php
use System\Core\Router;

$router = new Router();

// Public routes
$router->get('', 'UserController@welcome');
$router->get('login', 'UserController@loginForm');
$router->post('login', 'UserController@login');
$router->get('register/{code}', 'UserController@code');
$router->post('user-register', 'UserController@register');
$router->get('logout', 'UserController@logout');

$router->post('employee/login', 'EmployeeController@employeeAccess');
$router->get('employee/custody/{EmployeeID}', 'EmployeeController@custody');
$router->post('employee/signature', 'EmployeeController@signature');

// Protected routes (require authentication)
// GET Method
// Dashboard
$router->get('dashboard', 'UserController@dashboard', 'dashboard.view');

// Profile
$router->get('profile', 'UserController@profile', 'profile.manage');

// Employee
$router->get('employee', 'EmployeeController@index', 'employees.view');
$router->get('employee/{EmployeeID}', 'EmployeeController@show', 'employees.view');

// Parts
$router->get('parts', 'PartsController@index', 'parts.view');
$router->get('parts/{id}', 'PartsController@show', 'parts.view');

// Accessories
$router->get('accessories', 'AccessoriesController@index', 'accessories.view');

// Build
$router->get('build', 'BuildController@index', 'build.manage');
$router->get('build/check', 'BuildController@check', 'build.manage');

// Computer
$router->get('computer', 'ComputerController@index', 'computers.view');
$router->get('computer/specifications/{id}', 'ComputerController@specifications', 'computers.view');
$router->get('computer/returned/{EmployeeID}', 'ComputerController@returned', 'computers.view');

// Users
$router->get('users', 'UserController@users', 'users.manage');

// CMS, RBAC, audit, and reports
$router->get('settings', 'SettingsController@index', 'catalogs.manage');
$router->post('settings/general', 'SettingsController@saveGeneral', 'catalogs.manage');
$router->post('settings/catalog-group', 'SettingsController@createGroup', 'catalogs.manage');
$router->post('settings/catalog-value', 'SettingsController@saveValue', 'catalogs.manage');
$router->post('settings/catalog-value/remove', 'SettingsController@removeValue', 'catalogs.manage');
$router->post('settings/role', 'SettingsController@saveRole', 'roles.manage');
$router->post('settings/role/permissions', 'SettingsController@syncPermissions', 'roles.manage');
$router->post('settings/user-role', 'SettingsController@assignUserRole', 'roles.manage');
$router->get('reports', 'ReportController@index', 'reports.view');
$router->get('reports/pdf/{code}', 'ReportController@pdf', 'reports.export');

// POST Method for EmployeeController
$router->post('employee/register', 'EmployeeController@create', 'employees.manage');
$router->post('employee/upload', 'EmployeeController@store', 'employees.manage');
$router->post('employee/update', 'EmployeeController@update', 'employees.manage');
$router->post('employee/resigned', 'EmployeeController@destroy', 'employees.manage');

// POST Method for PartsController
$router->post('parts/create', 'PartsController@create', 'parts.manage');
$router->post('parts/store', 'PartsController@store', 'parts.manage');
$router->post('parts/update', 'PartsController@update', 'parts.manage');
$router->post('parts/defective', 'PartsController@destroy', 'parts.manage');


//  POST Method for AccessoriesController
$router->post('accessories/create', 'AccessoriesController@create', 'accessories.manage');
$router->post('accessories/store', 'AccessoriesController@store', 'accessories.manage');
$router->post('accessories/remove', 'AccessoriesController@destroy', 'accessories.manage');
$router->post('accessories/assign', 'AccessoriesController@assign', 'accessories.manage');
$router->post('accessories/delete', 'AccessoriesController@delete', 'accessories.manage');
$router->post('accessories/defective', 'AccessoriesController@defective', 'accessories.manage');


//  POST Method for BuildController
$router->post('build/store', 'BuildController@store', 'build.manage');
$router->post('build/add', 'BuildController@create', 'build.manage');
$router->post('build/remove', 'BuildController@destroy', 'build.manage');


//  POST Method for BuildController
$router->post('computer/create', 'ComputerController@create', 'computers.manage');
$router->post('computer/store', 'ComputerController@store', 'computers.manage');
$router->post('computer/remove', 'ComputerController@destroy', 'computers.manage');
$router->post('computer/return', 'ComputerController@return', 'computers.manage');
$router->post('computer/check', 'ComputerController@checkPCID', 'computers.manage');
$router->post('computer/reset', 'ComputerController@reset', 'computers.manage');
$router->post('computer/add', 'ComputerController@tempInstall', 'computers.manage');
$router->post('computer/delete', 'ComputerController@delete', 'computers.manage');
$router->post('computer/update', 'ComputerController@update', 'computers.manage');

// POST Method for ComputerController
$router->post('computer/uninstall', 'ComputerController@uninstall', 'computers.manage');

// POST Method for CompanyController
$router->post('company/add', 'CompanyController@add', 'company.manage');
$router->post('company/update', 'CompanyController@update', 'company.manage');

// POST Method for Profile
$router->post('profile/update', 'UserController@update', 'profile.manage');
$router->post('profile/password', 'UserController@password', 'profile.manage');
$router->post('profile/signature', 'UserController@signature', 'profile.manage');

// Backup
$router->get('backup', 'UserController@backup', 'backup.manage');

// POST Method for Invitation
$router->post('users/invite', 'UserController@sendCode', 'users.manage');
$router->post('users/reinvite', 'UserController@regenerateCode', 'users.manage');
$router->post('users/update', 'UserController@updateType', 'users.manage');
$router->post('users/remove', 'UserController@removeCode', 'users.manage');
return $router;
