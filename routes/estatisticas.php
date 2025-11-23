<?php
// routes/estatisticas.php

global $router;

$router->get('/estatisticas', [EstatisticaController::class, 'index']);
$router->get('/estatisticas/leis', [EstatisticaController::class, 'leis']);
$router->get('/estatisticas/cidadaos', [EstatisticaController::class, 'cidadaos']);

