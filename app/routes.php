<?php
// Every URL in the site, and the controller method that handles it.

$r = new Router();

// Public pages
$r->get('/',             [HomeController::class, 'index']);
$r->get('/about',        [HomeController::class, 'about']);
$r->get('/pay-with-qr',  [HomeController::class, 'qr']);

// Sign in / register
$r->get('/login',        [AuthController::class, 'loginForm']);
$r->post('/login',       [AuthController::class, 'login']);
$r->get('/register',     [AuthController::class, 'registerForm']);
$r->post('/register',    [AuthController::class, 'register']);
$r->post('/logout',      [AuthController::class, 'logout']);
$r->get('/logout',       [AuthController::class, 'logout']);

// Passenger
$r->get('/passenger',               [PassengerController::class, 'home']);
$r->get('/passenger/qr',            [PassengerController::class, 'qr']);
$r->get('/passenger/qr/token',      [PassengerController::class, 'qrToken']);
$r->post('/passenger/qr/freeze',    [PassengerController::class, 'freeze']);
$r->post('/passenger/qr/unfreeze',  [PassengerController::class, 'unfreeze']);
$r->get('/passenger/track',         [PassengerController::class, 'track']);
$r->get('/passenger/fleet',         [PassengerController::class, 'fleet']);
$r->get('/passenger/rides',         [PassengerController::class, 'rides']);
$r->get('/passenger/reload',        [PassengerController::class, 'reloadForm']);
$r->post('/passenger/reload',       [PassengerController::class, 'reload']);
$r->get('/passenger/profile',       [PassengerController::class, 'profile']);
$r->post('/passenger/profile',      [PassengerController::class, 'updateProfile']);

// Driver
$r->get('/driver',                  [DriverController::class, 'home']);
$r->post('/driver/trip/start',      [DriverController::class, 'startTrip']);
$r->post('/driver/trip/end',        [DriverController::class, 'endTrip']);
$r->get('/driver/count',            [DriverController::class, 'count']);
$r->get('/driver/count/data',       [DriverController::class, 'countData']);
$r->post('/driver/count/change',    [DriverController::class, 'countChange']);
$r->get('/scan',                    [DriverController::class, 'scan']);
$r->post('/scan',                   [DriverController::class, 'scanSubmit']);
$r->post('/driver/gps',             [DriverController::class, 'gps']);
$r->get('/driver/earnings',         [DriverController::class, 'earnings']);
$r->get('/driver/route',            [DriverController::class, 'route']);

// Admin (this is also the operator side)
$r->get('/admin',                       [AdminController::class, 'home']);
$r->get('/admin/users',                 [AdminController::class, 'users']);
$r->get('/admin/users/export',          [AdminController::class, 'exportUsers']);
$r->get('/admin/users/new',             [AdminController::class, 'newUser']);
$r->post('/admin/users/new',            [AdminController::class, 'createUser']);
$r->get('/admin/users/{id}',            [AdminController::class, 'editUser']);
$r->post('/admin/users/{id}',           [AdminController::class, 'updateUser']);
$r->post('/admin/users/{id}/status',    [AdminController::class, 'toggleUser']);
$r->get('/admin/tracking',              [AdminController::class, 'tracking']);
$r->get('/admin/fleet',                 [AdminController::class, 'fleet']);
$r->post('/admin/units',                [AdminController::class, 'addUnit']);
$r->post('/admin/units/{id}/status',    [AdminController::class, 'unitStatus']);
$r->post('/admin/routes',               [AdminController::class, 'addRoute']);
$r->post('/admin/routes/{id}/fare',     [AdminController::class, 'routeFare']);
$r->get('/admin/reports',               [AdminController::class, 'reports']);
$r->get('/admin/reports/export',        [AdminController::class, 'exportReport']);
$r->get('/admin/logs',                  [AdminController::class, 'logs']);

return $r;
