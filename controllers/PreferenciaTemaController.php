<?php
// controllers/PreferenciaTemaController.php

class PreferenciaTemaController
{
    private PreferenciaTemaModel $model;

    public function __construct()
    {
        $this->model = new PreferenciaTemaModel();
    }

    // GET /preferencias-temas
    public function index(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $preferencias = $this->model->listarPorCidadao($cidadaoId);

        response([
            'success' => true,
            'data' => $preferencias,
        ]);
    }

    // POST /preferencias-temas
    public function store(): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $body = getJsonBody();
        requireFields($body, ['tema']);

        $nivelInteresse = $body['nivel_interesse'] ?? 'medio';
        if (!in_array($nivelInteresse, ['baixo', 'medio', 'alto'], true)) {
            response([
                'success' => false,
                'message' => 'nivel_interesse deve ser "baixo", "medio" ou "alto"',
            ], 422);
        }

        $this->model->adicionar($cidadaoId, $body['tema'], $nivelInteresse);

        response([
            'success' => true,
            'message' => 'Preferência de tema adicionada',
        ], 201);
    }

    // PUT /preferencias-temas/{tema}
    public function update(string $tema): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $body = getJsonBody();
        requireFields($body, ['nivel_interesse']);

        if (!in_array($body['nivel_interesse'], ['baixo', 'medio', 'alto'], true)) {
            response([
                'success' => false,
                'message' => 'nivel_interesse deve ser "baixo", "medio" ou "alto"',
            ], 422);
        }

        $this->model->atualizarNivel($cidadaoId, $tema, $body['nivel_interesse']);

        response([
            'success' => true,
            'message' => 'Preferência de tema atualizada',
        ]);
    }

    // DELETE /preferencias-temas/{tema}
    public function delete(string $tema): void
    {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::requireAuth();
        $cidadaoId = $payload['cidadao_id'];

        $this->model->remover($cidadaoId, $tema);

        response([
            'success' => true,
            'message' => 'Preferência de tema removida',
        ]);
    }
}

