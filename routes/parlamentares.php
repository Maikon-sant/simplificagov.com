<?php
// routes/parlamentares.php

global $router;

$router->get('/parlamentares', [ParlamentarController::class, 'index']);
$router->get('/parlamentares/{id}', [ParlamentarController::class, 'show']);
$router->get('/parlamentares/{id}/analytics', [ParlamentarController::class, 'analytics']);
$router->post('/parlamentares', [ParlamentarController::class, 'store']);
$router->put('/parlamentares/{id}', [ParlamentarController::class, 'update']);
$router->delete('/parlamentares/{id}', [ParlamentarController::class, 'delete']);
$router->post('/parlamentares/{id}/leis/{pl_id}', [ParlamentarController::class, 'associarLei']);

