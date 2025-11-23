<?php
// core/Router.php

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$method])) {
            response(['success' => false, 'message' => 'Método não suportado'], 405);
            return;
        }

        // Rotas exatas primeiro
        if (isset($this->routes[$method][$uri])) {
            $this->executeHandler($this->routes[$method][$uri], []);
            return;
        }

        // Rotas com parâmetros (ex: /leis/{id})
        foreach ($this->routes[$method] as $route => $handler) {
            // Normaliza a rota para comparação
            $normalizedRoute = rtrim($route, '/') ?: '/';
            
            // Escapa caracteres especiais, mas preserva {param}
            $pattern = preg_quote($normalizedRoute, '#');
            // Substitui \{param\} por padrão regex de captura
            // Aceita caracteres alfanuméricos, underscore, hífen, espaços e caracteres UTF-8 (para temas com acentos)
            $pattern = preg_replace('#\\\{[a-zA-Z_]+\\\}#', '([^/]+)', $pattern);
            $pattern = '#^' . $pattern . '$#u'; // Flag 'u' para suporte UTF-8

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match
                
                // Decodifica URL e converte parâmetros numéricos para inteiros
                $params = array_map(function($param) {
                    $decoded = urldecode($param);
                    return is_numeric($decoded) ? (int) $decoded : $decoded;
                }, $matches);
                
                $this->executeHandler($handler, $params);
                return;
            }
        }

        response(['success' => false, 'message' => 'Rota não encontrada'], 404);
    }

    private function executeHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];

            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }
}
