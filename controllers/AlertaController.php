<?php
// controllers/AlertaController.php

class AlertaController
{
    private AlertaModel $model;

    public function __construct()
    {
        $this->model = new AlertaModel();
    }

    // GET /alertas
    public function index(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $query = getQueryParams();
        $apenasAtivos = !isset($query['todos']) || $query['todos'] !== '1';

        $alertas = $this->model->listarPorCidadao($cidadaoId, $apenasAtivos);

        response([
            'success' => true,
            'data' => $alertas,
        ]);
    }

    // GET /alertas/{id}
    public function show(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        
        if (!$alerta) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado',
            ], 404);
        }

        if ($alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        response([
            'success' => true,
            'data' => $alerta,
        ]);
    }

    // POST /alertas
    public function store(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $body = getJsonBody();
        requireFields($body, ['tipo_alerta', 'titulo']);

        $tiposValidos = ['tramitacao', 'votacao', 'aprovacao', 'veto', 'personalizado'];
        if (!in_array($body['tipo_alerta'], $tiposValidos, true)) {
            response([
                'success' => false,
                'message' => 'tipo_alerta inválido. Deve ser: ' . implode(', ', $tiposValidos),
            ], 422);
        }

        // Verificar se PL existe (se fornecido)
        if (!empty($body['pl_id'])) {
            $plModel = new PLModel();
            $pl = $plModel->buscarPorId($body['pl_id']);
            if (!$pl) {
                response([
                    'success' => false,
                    'message' => 'Projeto de lei não encontrado',
                ], 404);
            }
        }

        $data = [
            'cidadao_id' => $cidadaoId,
            'pl_id' => $body['pl_id'] ?? null,
            'tipo_alerta' => $body['tipo_alerta'],
            'titulo' => $body['titulo'],
            'descricao' => $body['descricao'] ?? null,
            'filtros_json' => $body['filtros_json'] ?? null,
            'ativo' => $body['ativo'] ?? 1,
        ];

        $id = $this->model->criar($data);
        $alerta = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Alerta criado com sucesso',
            'data' => $alerta,
        ], 201);
    }

    // PUT /alertas/{id}
    public function update(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        if (!$alerta) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado',
            ], 404);
        }

        if ($alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $body = getJsonBody();
        $this->model->atualizar($id, $body);
        $alerta = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Alerta atualizado com sucesso',
            'data' => $alerta,
        ]);
    }

    // DELETE /alertas/{id}
    public function delete(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        if (!$alerta) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado',
            ], 404);
        }

        if ($alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $this->model->deletar($id);

        response([
            'success' => true,
            'message' => 'Alerta removido com sucesso',
        ]);
    }

    // POST /alertas/{id}/ativar
    public function ativar(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        if (!$alerta || $alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado ou acesso negado',
            ], 404);
        }

        $this->model->ativar($id);

        response([
            'success' => true,
            'message' => 'Alerta ativado',
        ]);
    }

    // POST /alertas/{id}/desativar
    public function desativar(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        if (!$alerta || $alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado ou acesso negado',
            ], 404);
        }

        $this->model->desativar($id);

        response([
            'success' => true,
            'message' => 'Alerta desativado',
        ]);
    }

    // POST /alertas/{id}/read
    public function marcarComoLido(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $alerta = $this->model->buscarPorId($id);
        if (!$alerta) {
            response([
                'success' => false,
                'message' => 'Alerta não encontrado',
            ], 404);
        }

        if ($alerta['cidadao_id'] != $cidadaoId) {
            response([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $sucesso = $this->model->marcarComoLido($id, $cidadaoId);
        
        if (!$sucesso) {
            response([
                'success' => false,
                'message' => 'Erro ao marcar alerta como lido',
            ], 500);
        }

        $alertaAtualizado = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Alerta marcado como lido',
            'data' => $alertaAtualizado,
        ]);
    }
}

