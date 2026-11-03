<?php
// Front controller: every request to the site starts here.
require __DIR__ . '/../app/bootstrap.php';

$router = require APP_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
