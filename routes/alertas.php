<?php
// routes/alertas.php

global $router;

$router->get('/alertas', [AlertaController::class, 'index']);
$router->get('/alertas/{id}', [AlertaController::class, 'show']);
$router->post('/alertas', [AlertaController::class, 'store']);
$router->put('/alertas/{id}', [AlertaController::class, 'update']);
$router->delete('/alertas/{id}', [AlertaController::class, 'delete']);
$router->post('/alertas/{id}/ativar', [AlertaController::class, 'ativar']);
$router->post('/alertas/{id}/desativar', [AlertaController::class, 'desativar']);
$router->post('/alertas/{id}/read', [AlertaController::class, 'marcarComoLido']);

