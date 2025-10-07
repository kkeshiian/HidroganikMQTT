<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Rute untuk Login & Logout (Publik, tidak perlu login)
$routes->get('/login', 'AuthController::index');
$routes->post('/login', 'AuthController::loginProcess');
$routes->get('/logout', 'AuthController::logout');

// Grup rute yang memerlukan login
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Monitoring::index');
    $routes->get('kalibrasi', 'Monitoring::kalibrasi');
    $routes->get('log', 'Monitoring::log');

    $routes->group('users', ['filter' => 'admin'], static function ($routes) {
        $routes->get('new', 'User::new');
        $routes->post('create', 'User::create');
    });
});