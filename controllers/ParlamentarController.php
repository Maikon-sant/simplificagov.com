<?php
// controllers/ParlamentarController.php

class ParlamentarController
{
    private ParlamentarModel $model;

    public function __construct()
    {
        $this->model = new ParlamentarModel();
    }

    // GET /parlamentares
    public function index(): void
    {
        try {
            $query = getQueryParams();
            $limit = isset($query['limit']) ? (int) $query['limit'] : 50;
            $offset = isset($query['offset']) ? (int) $query['offset'] : 0;

            $filtros = [];
            if (!empty($query['casa'])) $filtros['casa'] = $query['casa'];
            if (!empty($query['uf'])) $filtros['uf'] = $query['uf'];
            if (!empty($query['partido'])) $filtros['partido'] = $query['partido'];
            if (!empty($query['busca'])) $filtros['busca'] = $query['busca'];

            $parlamentares = $this->model->listar($filtros, $limit, $offset);
            $total = $this->model->contarTotal($filtros);

            response([
                'success' => true,
                'data' => $parlamentares,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_next' => ($offset + $limit) < $total,
                    'has_prev' => $offset > 0,
                ],
            ]);
        } catch (Throwable $e) {
            response([
                'success' => false,
                'message' => 'Erro ao listar parlamentares',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                    ? 'Erro interno' 
                    : $e->getMessage()
            ], 500);
        }
    }

    // GET /parlamentares/{id}
    public function show(int $id): void
    {
        $query = getQueryParams();
        $incluirAnalytics = isset($query['analytics']) && $query['analytics'] === '1';
        
        $parlamentar = $this->model->buscarPorId($id, $incluirAnalytics);
        
        if (!$parlamentar) {
            response([
                'success' => false,
                'message' => 'Parlamentar não encontrado',
            ], 404);
        }

        response([
            'success' => true,
            'data' => $parlamentar,
        ]);
    }

    // GET /parlamentares/{id}/analytics
    public function analytics(int $id): void
    {
        $parlamentar = $this->model->buscarPorId($id);
        
        if (!$parlamentar) {
            response([
                'success' => false,
                'message' => 'Parlamentar não encontrado',
            ], 404);
        }

        $query = getQueryParams();
        $forcarRecalculo = isset($query['recalcular']) && $query['recalcular'] === '1';
        
        $analytics = $this->model->buscarAnalytics($id, $forcarRecalculo);

        response([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    // POST /parlamentares
    public function store(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        AuthMiddleware::requireAuth();

        $body = getJsonBody();
        requireFields($body, ['nome', 'casa']);

        if (!in_array($body['casa'], ['Camara', 'Senado'], true)) {
            response([
                'success' => false,
                'message' => 'Casa deve ser "Camara" ou "Senado"',
            ], 422);
        }

        $id = $this->model->criar($body);
        $parlamentar = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Parlamentar cadastrado com sucesso',
            'data' => $parlamentar,
        ], 201);
    }

    // PUT /parlamentares/{id}
    public function update(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        AuthMiddleware::requireAuth();

        $parlamentar = $this->model->buscarPorId($id);
        if (!$parlamentar) {
            response([
                'success' => false,
                'message' => 'Parlamentar não encontrado',
            ], 404);
        }

        $body = getJsonBody();
        $this->model->atualizar($id, $body);
        $parlamentar = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Parlamentar atualizado com sucesso',
            'data' => $parlamentar,
        ]);
    }

    // DELETE /parlamentares/{id}
    public function delete(int $id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        AuthMiddleware::requireAuth();

        $parlamentar = $this->model->buscarPorId($id);
        if (!$parlamentar) {
            response([
                'success' => false,
                'message' => 'Parlamentar não encontrado',
            ], 404);
        }

        $this->model->deletar($id);

        response([
            'success' => true,
            'message' => 'Parlamentar removido com sucesso',
        ]);
    }

    // POST /parlamentares/{id}/leis/{pl_id}
    public function associarLei(int $id, int $pl_id): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        AuthMiddleware::requireAuth();

        $body = getJsonBody();
        $tipoRelacao = $body['tipo_relacao'] ?? 'autor';

        if (!in_array($tipoRelacao, ['autor', 'relator', 'coautor'], true)) {
            response([
                'success' => false,
                'message' => 'tipo_relacao deve ser "autor", "relator" ou "coautor"',
            ], 422);
        }

        $this->model->associarLei($id, $pl_id, $tipoRelacao);

        response([
            'success' => true,
            'message' => 'Lei associada ao parlamentar com sucesso',
        ]);
    }
}

