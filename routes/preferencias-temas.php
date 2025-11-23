<?php
// routes/preferencias-temas.php

global $router;

$router->get('/preferencias-temas', [PreferenciaTemaController::class, 'index']);
$router->post('/preferencias-temas', [PreferenciaTemaController::class, 'store']);
$router->put('/preferencias-temas/{tema}', [PreferenciaTemaController::class, 'update']);
$router->delete('/preferencias-temas/{tema}', [PreferenciaTemaController::class, 'delete']);

