<?php
// middleware/AuthMiddleware.php

require_once __DIR__ . '/../helpers/jwt.php';

class AuthMiddleware
{
    public static function requireAuth(): ?array
    {
        $token = JWT::getBearerToken();

        if (!$token) {
            response([
                'success' => false,
                'message' => 'Token de autenticação não fornecido',
            ], 401);
            return null;
        }

        $payload = JWT::decode($token);

        if (!$payload) {
            response([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ], 401);
            return null;
        }

        return $payload;
    }

    public static function optionalAuth(): ?array
    {
        $token = JWT::getBearerToken();
        if (!$token) {
            return null;
        }

        return JWT::decode($token);
    }
}

