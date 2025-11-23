<?php
// controllers/FavoritoController.php

class FavoritoController
{
    private FavoritoModel $model;

    public function __construct()
    {
        $this->model = new FavoritoModel();
    }

    // GET /favoritos
    public function index(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $query = getQueryParams();
        $limit = isset($query['limit']) ? (int) $query['limit'] : 50;
        $offset = isset($query['offset']) ? (int) $query['offset'] : 0;

        $favoritos = $this->model->listarPorCidadao($cidadaoId, $limit, $offset);
        $total = $this->model->contarPorCidadao($cidadaoId);

        response([
            'success' => true,
            'data' => $favoritos,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_next' => ($offset + $limit) < $total,
                'has_prev' => $offset > 0,
            ],
        ]);
    }

    // POST /favoritos/{pl_id}
    public function adicionar(int $pl_id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        // Verificar se PL existe
        $plModel = new PLModel();
        $pl = $plModel->buscarPorId($pl_id);
        if (!$pl) {
            response([
                'success' => false,
                'message' => 'Projeto de lei não encontrado',
            ], 404);
        }

        // Verificar se já está favoritado
        if ($this->model->verificarFavorito($cidadaoId, $pl_id)) {
            response([
                'success' => false,
                'message' => 'Lei já está nos favoritos',
            ], 409);
        }

        $this->model->adicionar($cidadaoId, $pl_id);

        response([
            'success' => true,
            'message' => 'Lei adicionada aos favoritos',
        ], 201);
    }

    // DELETE /favoritos/{pl_id}
    public function remover(int $pl_id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        if (!$this->model->verificarFavorito($cidadaoId, $pl_id)) {
            response([
                'success' => false,
                'message' => 'Lei não está nos favoritos',
            ], 404);
        }

        $this->model->remover($cidadaoId, $pl_id);

        response([
            'success' => true,
            'message' => 'Lei removida dos favoritos',
        ]);
    }

    // GET /favoritos/verificar/{pl_id}
    public function verificar(int $pl_id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $isFavorito = $this->model->verificarFavorito($cidadaoId, $pl_id);

        response([
            'success' => true,
            'data' => [
                'is_favorito' => $isFavorito,
            ],
        ]);
    }
}

