<?php
// routes/auth.php

global $router;

$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/refresh', [AuthController::class, 'refresh']);
$router->get('/auth/me', [AuthController::class, 'me']);

