<?php
// controllers/AuthController.php

require_once __DIR__ . '/../helpers/jwt.php';

class AuthController
{
    private CidadaoModel $model;

    public function __construct()
    {
        $this->model = new CidadaoModel();
    }

    // POST /auth/register
    public function register(): void
    {
        $body = getJsonBody();
        requireFields($body, ['nome', 'email', 'senha']);

        // Validar email
        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            response([
                'success' => false,
                'message' => 'Email inválido',
            ], 422);
        }

        // Verificar se email já existe
        if ($this->model->buscarPorEmail($body['email'])) {
            response([
                'success' => false,
                'message' => 'Email já cadastrado',
            ], 409);
        }

        // Criar hash da senha
        $senhaHash = password_hash($body['senha'], PASSWORD_DEFAULT);

        $data = [
            'nome' => $body['nome'],
            'email' => $body['email'],
            'senha_hash' => $senhaHash,
            'contato' => $body['contato'] ?? $body['email'],
            'faixa_etaria' => $body['faixa_etaria'] ?? null,
            'regiao' => $body['regiao'] ?? null,
            'preferencia_midia' => $body['preferencia_midia'] ?? 'texto',
        ];

        $id = $this->model->criar($data);
        $cidadao = $this->model->buscarPorId($id);

        // Gerar token
        $token = JWT::encode(['cidadao_id' => $id, 'email' => $body['email']]);

        response([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso',
            'data' => [
                'cidadao' => $cidadao,
                'token' => $token,
            ],
        ], 201);
    }

    // POST /auth/login
    public function login(): void
    {
        $body = getJsonBody();
        requireFields($body, ['email', 'senha']);

        $cidadao = $this->model->buscarPorEmail($body['email']);

        if (!$cidadao || !password_verify($body['senha'], $cidadao['senha_hash'] ?? '')) {
            response([
                'success' => false,
                'message' => 'Email ou senha inválidos',
            ], 401);
        }

        if (isset($cidadao['ativo']) && !$cidadao['ativo']) {
            response([
                'success' => false,
                'message' => 'Conta desativada',
            ], 403);
        }

        // Gerar token
        $token = JWT::encode(['cidadao_id' => $cidadao['cidadao_id'], 'email' => $cidadao['email']]);

        response([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data' => [
                'cidadao' => $cidadao,
                'token' => $token,
            ],
        ]);
    }

    // POST /auth/refresh
    public function refresh(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();

        $cidadao = $this->model->buscarPorId($payload['cidadao_id']);

        if (!$cidadao) {
            response([
                'success' => false,
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        $token = JWT::encode(['cidadao_id' => $cidadao['cidadao_id'], 'email' => $cidadao['email']]);

        response([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
        ]);
    }

    // GET /auth/me
    public function me(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();

        $cidadao = $this->model->buscarPorId($payload['cidadao_id']);

        if (!$cidadao) {
            response([
                'success' => false,
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        // Remover senha_hash da resposta
        unset($cidadao['senha_hash']);
        unset($cidadao['token_refresh']);

        response([
            'success' => true,
            'data' => $cidadao,
        ]);
    }
}

