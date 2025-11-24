<?php
// controllers/LeiController.php

class LeiController
{
    private PLModel $plModel;

    public function __construct()
    {
        $this->plModel = new PLModel();
    }

    // GET /leis?tipo=PL&numero=118&ano=2007
    public function index(): void
    {
        try {
            $query = getQueryParams();

            // Verificar autenticação opcional para incluir is_favorito
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            $payload = AuthMiddleware::optionalAuth();
            $cidadaoId = $payload ? $payload['cidadao_id'] : null;

            if (!empty($query['numero']) && !empty($query['ano'])) {
                // Busca específica com cache
                $numero = $query['numero'];
                $ano = (int) $query['ano'];
                $tipo = $query['tipo'] ?? 'PL';

                $cache = $this->plModel->buscarPorNumeroAno($numero, $ano);

                if ($cache) {
                    // Adicionar campos faltantes
                    $favoritoModel = new FavoritoModel();
                    $cache['favoritos_count'] = $favoritoModel->contarFavoritosPorLei($cache['pl_id']);
                    $cache['visualizacoes'] = $cache['visualizacoes'] ?? 0;
                    $cache['relevancia_score'] = $cache['relevancia_score'] ?? 0;

                    if ($cidadaoId) {
                        $cache['is_favorito'] = $favoritoModel->verificarFavorito($cidadaoId, $cache['pl_id']);
                    }

                    response([
                        'success' => true,
                        'source' => 'cache',
                        'data' => $cache,
                    ]);
                }

                // não tem no cache -> buscar APIs
                $camara = CamaraService::buscarProposicao($tipo, $numero, (string) $ano);
                $senado = SenadoService::buscarMateria($tipo, $numero, (string) $ano);

                if (!$camara && !$senado) {
                    response([
                        'success' => false,
                        'message' => 'Nenhum projeto encontrado nas APIs oficiais',
                    ], 404);
                }

                $plId = $this->plModel->salvarDoServico($camara, $senado);
                $pl = $this->plModel->buscarPorId($plId);

                // Adicionar campos faltantes
                $favoritoModel = new FavoritoModel();
                $pl['favoritos_count'] = $favoritoModel->contarFavoritosPorLei($plId);
                $pl['visualizacoes'] = $pl['visualizacoes'] ?? 0;
                $pl['relevancia_score'] = $pl['relevancia_score'] ?? 0;

                if ($cidadaoId) {
                    $pl['is_favorito'] = $favoritoModel->verificarFavorito($cidadaoId, $plId);
                }

                response([
                    'success' => true,
                    'source' => 'api-externa',
                    'data' => $pl,
                    'camara' => $camara,
                    'senado' => $senado,
                ]);
            }

            // Lista geral com busca avançada e paginação
            $limit = isset($query['limit']) ? (int) $query['limit'] : 50;
            $offset = isset($query['offset']) ? (int) $query['offset'] : 0;

            // Filtros de busca avançada
            $filtros = [];
            if (!empty($query['ano']))
                $filtros['ano'] = $query['ano'];
            if (!empty($query['numero']))
                $filtros['numero'] = $query['numero'];
            if (!empty($query['entidade']))
                $filtros['entidade'] = $query['entidade'];
            if (!empty($query['busca']))
                $filtros['busca'] = $query['busca'];
            if (!empty($query['ano_min']))
                $filtros['ano_min'] = $query['ano_min'];
            if (!empty($query['ano_max']))
                $filtros['ano_max'] = $query['ano_max'];
            if (!empty($query['ordenar']))
                $filtros['ordenar'] = $query['ordenar'];
            if (!empty($query['direcao']))
                $filtros['direcao'] = $query['direcao'];

            $leis = $this->plModel->listar($limit, $offset, $filtros, $cidadaoId);
            $total = $this->plModel->contarTotal($filtros);

            response([
                'success' => true,
                'data' => $leis,
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
                'message' => 'Erro ao listar leis',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production'
                    ? 'Erro interno'
                    : $e->getMessage()
            ], 500);
        }
    }

    // GET /leis/{id}
    public function show(int $id): void
    {
        // Verificar autenticação opcional
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $payload = AuthMiddleware::optionalAuth();
        $cidadaoId = $payload ? $payload['cidadao_id'] : null;

        $pl = $this->plModel->buscarPorId($id);
        if (!$pl) {
            response([
                'success' => false,
                'message' => 'PL não encontrado',
            ], 404);
        }

        // Registrar visualização
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->plModel->registrarVisualizacao($id, $cidadaoId, $ipAddress);

        // Adicionar campos faltantes
        $favoritoModel = new FavoritoModel();
        $pl['favoritos_count'] = $favoritoModel->contarFavoritosPorLei($id);
        $pl['visualizacoes'] = ($pl['visualizacoes'] ?? 0) + 1; // Incrementar visualização atual
        $pl['relevancia_score'] = $pl['relevancia_score'] ?? 0;

        if ($cidadaoId) {
            $pl['is_favorito'] = $favoritoModel->verificarFavorito($cidadaoId, $id);
        }

        response([
            'success' => true,
            'data' => $pl,
        ]);
    }

    // POST /leis/{id}/traduzir
    public function traduzir(int $id): void
    {
        $pl = $this->plModel->buscarPorId($id);
        if (!$pl) {
            response([
                'success' => false,
                'message' => 'PL não encontrado',
            ], 404);
        }

        $textoOriginal = $pl['texto_original'] ?? '';
        if (!$textoOriginal) {
            response([
                'success' => false,
                'message' => 'PL sem texto_original cadastrado',
            ], 400);
        }

        // Gera tradução completa usando a nova IA (SimplificaGov)
        // Passando a URL de origem se disponível, ou vazio
        $fonteUrl = $pl['origem'] ?? '';
        $traducaoCompleta = IAService::gerarTraducaoCompleta($textoOriginal, $fonteUrl);

        // Extrai o resumo de áudio para compatibilidade com o campo resumo_curto_chat
        $resumo = $traducaoCompleta['resumo_audio'] ?? 'Resumo indisponível.';

        // Salva no banco
        // O campo toolkit_completo_json armazenará o JSON completo da nova estrutura
        $this->plModel->salvarTraducaoSimples($pl['pl_id'], $resumo, $traducaoCompleta, 'openai-gpt-4o');

        response([
            'success' => true,
            'message' => 'Tradução simples gerada com sucesso',
            'data' => [
                'resumo' => $resumo,
                'toolkit' => $traducaoCompleta,
            ],
        ]);
    }
}
