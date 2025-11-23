<?php
// routes/cidadao.php

global $router;

$router->get('/cidadao', [CidadaoController::class, 'index']);
$router->get('/cidadao/{id}', [CidadaoController::class, 'show']);
$router->post('/cidadao', [CidadaoController::class, 'store']);
$router->post('/cidadao/{id}/preferencia', [CidadaoController::class, 'atualizarPreferencia']);
