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
$router->get('dashboard', 'UserController@dashboard', true);

// Profile
$router->get('profile', 'UserController@profile', true);

// Employee
$router->get('employee', 'EmployeeController@index', true);
$router->get('employee/{EmployeeID}', 'EmployeeController@show', true);

// Parts
$router->get('parts', 'PartsController@index', true);
$router->get('parts/{id}', 'PartsController@show', true);

// Accessories
$router->get('accessories', 'AccessoriesController@index', true);

// Build
$router->get('build', 'BuildController@index', true);
$router->get('build/check', 'BuildController@check', true);

// Computer
$router->get('computer', 'ComputerController@index', true);
$router->get('computer/specifications/{id}', 'ComputerController@specifications', true);
$router->get('computer/returned/{EmployeeID}', 'ComputerController@returned', true);

// Users
$router->get('users', 'UserController@users', true);

// POST Method for EmployeeController
$router->post('employee/register', 'EmployeeController@create', true);
$router->post('employee/upload', 'EmployeeController@store', true);
$router->post('employee/update', 'EmployeeController@update', true);
$router->post('employee/resigned', 'EmployeeController@destroy', true);

// POST Method for PartsController
$router->post('parts/create', 'PartsController@create', true);
$router->post('parts/store', 'PartsController@store', true);
$router->post('parts/update', 'PartsController@update', true);
$router->post('parts/defective', 'PartsController@destroy', true);


//  POST Method for AccessoriesController
$router->post('accessories/create', 'AccessoriesController@create', true);
$router->post('accessories/store', 'AccessoriesController@store', true);
$router->post('accessories/remove', 'AccessoriesController@destroy', true);
$router->post('accessories/assign', 'AccessoriesController@assign', true);
$router->post('accessories/delete', 'AccessoriesController@delete', true);
$router->post('accessories/defective', 'AccessoriesController@defective', true);


//  POST Method for BuildController
$router->post('build/store', 'BuildController@store', true);
$router->post('build/add', 'BuildController@create', true);
$router->post('build/remove', 'BuildController@destroy', true);


//  POST Method for BuildController
$router->post('computer/create', 'ComputerController@create', true);
$router->post('computer/store', 'ComputerController@store', true);
$router->post('computer/remove', 'ComputerController@destroy', true);
$router->post('computer/return', 'ComputerController@return', true);
$router->post('computer/check', 'ComputerController@checkPCID', true);
$router->post('computer/reset', 'ComputerController@reset', true);
$router->post('computer/add', 'ComputerController@tempInstall', true);
$router->post('computer/delete', 'ComputerController@delete', true);
$router->post('computer/update', 'ComputerController@update', true);

// POST Method for ComputerController
$router->post('computer/uninstall', 'ComputerController@uninstall', true);

// POST Method for CompanyController
$router->post('company/add', 'CompanyController@add', true);
$router->post('company/update', 'CompanyController@update', true);

// POST Method for Profile
$router->post('profile/update', 'UserController@update', true);
$router->post('profile/password', 'UserController@password', true);
$router->post('profile/signature', 'UserController@signature', true);

// Backup
$router->get('backup', 'UserController@backup', true);

// POST Method for Invitation
$router->post('users/invite', 'UserController@sendCode', true);
$router->post('users/reinvite', 'UserController@regeneratecode', true);
$router->post('users/update', 'UserController@updateType', true);
$router->post('users/remove', 'UserController@removeCode', true);
return $router;