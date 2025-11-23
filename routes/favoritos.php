<?php
// routes/favoritos.php

global $router;

$router->get('/favoritos', [FavoritoController::class, 'index']);
$router->post('/favoritos/{pl_id}', [FavoritoController::class, 'adicionar']);
$router->delete('/favoritos/{pl_id}', [FavoritoController::class, 'remover']);
$router->get('/favoritos/verificar/{pl_id}', [FavoritoController::class, 'verificar']);

