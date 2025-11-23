<?php
declare(strict_types=1);

// Error handler (deve ser o primeiro)
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json; charset=utf-8');

// Carregar configurações
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Core e helpers
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/http.php';
require_once __DIR__ . '/helpers/validator.php';
require_once __DIR__ . '/helpers/jwt.php';

// Autoloader simples para controllers, models e services
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/services/' . $class . '.php',
        __DIR__ . '/core/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Instanciar Router
$router = new Router();

// Disponibilizar router globalmente para os arquivos de rotas
global $router;

// Registrar rotas
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/leis.php';
require_once __DIR__ . '/routes/cidadao.php';
require_once __DIR__ . '/routes/parlamentares.php';
require_once __DIR__ . '/routes/favoritos.php';
require_once __DIR__ . '/routes/alertas.php';
require_once __DIR__ . '/routes/preferencias-temas.php';
require_once __DIR__ . '/routes/estatisticas.php';

// Rota default para /
$router->get('/', function () {
    response([
        'success' => true,
        'message' => 'API SimplificaGov ativa',
        'versao' => '1.0.0',
        'documentacao' => 'https://simplificagov.com/docs'
    ], 200);
});

// Capturar URI solicitada
$requestedUri = $_SERVER['REQUEST_URI'] ?? '/';

// Remover query string
$requestedUri = parse_url($requestedUri, PHP_URL_PATH);

// Remover o caminho base do projeto se existir
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && strpos($requestedUri, $scriptName) === 0) {
    $requestedUri = substr($requestedUri, strlen($scriptName));
}

// Normalizar
$requestedUri = '/' . trim($requestedUri, '/');
$requestedUri = $requestedUri === '' ? '/' : $requestedUri;

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $requestedUri);
} catch (Throwable $e) {
    response([
        'success' => false,
        'message' => 'Erro interno no servidor',
        'error'   => $e->getMessage()
    ], 500);
}
