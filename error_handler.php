<?php
/**
 * Error Handler para capturar erros e exibir de forma amigável
 * Adicione require_once __DIR__ . '/error_handler.php'; no início do index.php
 */

// Desabilitar exibição de erros em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Handler de erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno no servidor',
            'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                ? 'Erro interno' 
                : $error['message'] . ' em ' . $error['file'] . ':' . $error['line']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
});

// Handler de exceções
set_exception_handler(function($exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor',
        'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production'
            ? 'Erro interno'
            : $exception->getMessage() . ' em ' . $exception->getFile() . ':' . $exception->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
});

