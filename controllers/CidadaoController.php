<?php
// controllers/CidadaoController.php

class CidadaoController
{
    private CidadaoModel $model;

    public function __construct()
    {
        $this->model = new CidadaoModel();
    }

    // GET /cidadao
    public function index(): void
    {
        try {
            $query = getQueryParams();
            $limit = isset($query['limit']) ? (int) $query['limit'] : 50;
            $offset = isset($query['offset']) ? (int) $query['offset'] : 0;

            $lista = $this->model->listarComPaginacao($limit, $offset);
            $total = $this->model->contarTotal();

            // Remover senhas
            foreach ($lista as &$item) {
                unset($item['senha_hash']);
                unset($item['token_refresh']);
            }

            response([
                'success' => true,
                'data'    => $lista,
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
                'message' => 'Erro ao listar cidadãos',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                    ? 'Erro interno' 
                    : $e->getMessage()
            ], 500);
        }
    }

    // GET /cidadao/{id}
    public function show(int $id): void
    {
        $cidadao = $this->model->buscarPorId($id);
        
        if (!$cidadao) {
            response([
                'success' => false,
                'message' => 'Cidadão não encontrado',
            ], 404);
        }

        // Remover senha
        unset($cidadao['senha_hash']);
        unset($cidadao['token_refresh']);

        // Carregar preferências de temas
        $prefModel = new PreferenciaTemaModel();
        $cidadao['preferencias_temas'] = $prefModel->listarPorCidadao($id);

        response([
            'success' => true,
            'data'    => $cidadao,
        ]);
    }

    // POST /cidadao
    // body: { "nome": "...", "contato": "...", "faixa_etaria": "...", "regiao":"...", "preferencia_midia":"voz|texto" }
    public function store(): void
    {
        $body = getJsonBody();
        requireFields($body, ['nome', 'contato']);

        $id = $this->model->criar($body);

        $cidadao = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Cidadão cadastrado com sucesso',
            'data'    => $cidadao,
        ], 201);
    }

    // POST /cidadao/{id}/preferencia
    // body: { "preferencia_midia": "voz" }
    public function atualizarPreferencia(int $id): void
    {
        $body = getJsonBody();
        requireFields($body, ['preferencia_midia']);

        $midia = $body['preferencia_midia'];
        if (!in_array($midia, ['voz', 'texto'], true)) {
            response([
                'success' => false,
                'message' => 'preferencia_midia deve ser "voz" ou "texto"',
            ], 422);
        }

        if (!$this->model->buscarPorId($id)) {
            response([
                'success' => false,
                'message' => 'Cidadão não encontrado',
            ], 404);
        }

        $this->model->atualizarPreferencia($id, $midia);

        $cidadao = $this->model->buscarPorId($id);

        response([
            'success' => true,
            'message' => 'Preferência de mídia atualizada',
            'data'    => $cidadao,
        ]);
    }
}
