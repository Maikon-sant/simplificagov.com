<?php
// routes/leis.php

global $router;

$router->get('/leis', [LeiController::class, 'index']);
$router->get('/leis/{id}', [LeiController::class, 'show']);
$router->post('/leis/{id}/traduzir', [LeiController::class, 'traduzir']);
