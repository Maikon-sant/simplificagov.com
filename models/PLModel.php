<?php
// models/PLModel.php

class PLModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function buscarPorNumeroAno(string $numero, int $ano): ?array
    {
        $sql = "SELECT * FROM pl_dados_legais WHERE numero_pl = :numero AND ano = :ano";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero' => $numero,
            ':ano'    => $ano,
        ]);

        $pl = $stmt->fetch();
        return $pl ?: null;
    }

    public function buscarPorId(int $plId): ?array
    {
        $sql = "SELECT * FROM pl_dados_legais WHERE pl_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $plId]);
        $pl = $stmt->fetch();
        if (!$pl) {
            return null;
        }

        // carregar tramitações
        $sqlTram = "SELECT * FROM pl_tramitacoes WHERE pl_id = :id ORDER BY data_tramitacao DESC";
        $stmtTr  = $this->db->prepare($sqlTram);
        $stmtTr->execute([':id' => $plId]);
        $tram = $stmtTr->fetchAll();

        // carregar traduções
        $sqlTrad = "SELECT * FROM traducao_simples WHERE pl_id = :id";
        $stmtTrad = $this->db->prepare($sqlTrad);
        $stmtTrad->execute([':id' => $plId]);
        $trad = $stmtTrad->fetch();

        $pl['tramitacoes'] = $tram;
        $pl['traducao']    = $trad;

        return $pl;
    }

    public function salvarDoServico(array $camara = null, array $senado = null): int
    {
        // Define dados prioritários (Câmara ou Senado)
        $origem  = null;
        $numero  = null;
        $ano     = null;
        $texto   = null;
        $status  = null;
        $entidade = null;
        $proposito = null;

        if ($camara) {
            $origem   = $camara['origem'];
            $numero   = $camara['numero'];
            $ano      = (int) $camara['ano'];
            $texto    = $camara['texto_original'] ?? null;
            $status   = $camara['status']['descricaoSituacao'] ?? null;
            $entidade = 'Camara';
        }

        if ($senado && !$numero) {
            $origem   = $senado['origem'];
            $numero   = $senado['codigoMateria'];
            $ano      = (int) date('Y'); // ajuste se quiser
            $texto    = $senado['texto_original'] ?? null;
            $status   = null;
            $entidade = 'Senado';
        }

        $sql = "INSERT INTO pl_dados_legais (numero_pl, ano, texto_original, tramitacao_atual, entidade, origem)
                VALUES (:numero, :ano, :texto, :status, :entidade, :origem)
                ON DUPLICATE KEY UPDATE 
                    texto_original = VALUES(texto_original),
                    tramitacao_atual = VALUES(tramitacao_atual),
                    entidade = VALUES(entidade),
                    origem = VALUES(origem),
                    atualizado_em = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero'   => $numero,
            ':ano'      => $ano,
            ':texto'    => $texto,
            ':status'   => $status,
            ':entidade' => $entidade,
            ':origem'   => $origem,
        ]);

        // pegar id
        if ($this->db->lastInsertId()) {
            $plId = (int) $this->db->lastInsertId();
        } else {
            // buscar novamente
            $pl = $this->buscarPorNumeroAno($numero, $ano);
            $plId = (int) $pl['pl_id'];
        }

        // salvando tramitações básicas se vierem da Câmara
        if (!empty($camara['tramitacoes'])) {
            $this->salvarTramitacoes($plId, $camara['tramitacoes']);
        }

        return $plId;
    }

    public function salvarTramitacoes(int $plId, array $tramitacoes): void
    {
        $sql = "INSERT INTO pl_tramitacoes (pl_id, descricao, data_tramitacao) 
                VALUES (:pl_id, :descricao, :data)
                ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)";
        $stmt = $this->db->prepare($sql);

        foreach ($tramitacoes as $t) {
            $descricao = $t['descricaoTramitacao'] ?? ($t['despacho'] ?? '');
            $data      = substr($t['dataHora'], 0, 10) ?? null;

            $stmt->execute([
                ':pl_id'     => $plId,
                ':descricao' => $descricao,
                ':data'      => $data,
            ]);
        }
    }

    public function salvarTraducaoSimples(int $plId, string $resumo, array $toolkit, string $versaoLlm): void
    {
        $sql = "INSERT INTO traducao_simples (pl_id, resumo_curto_chat, toolkit_completo_json, data_traducao, versao_llm)
                VALUES (:pl_id, :resumo, :toolkit, CURDATE(), :versao)
                ON DUPLICATE KEY UPDATE
                    resumo_curto_chat = VALUES(resumo_curto_chat),
                    toolkit_completo_json = VALUES(toolkit_completo_json),
                    data_traducao = CURDATE(),
                    versao_llm = VALUES(versao_llm)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pl_id'   => $plId,
            ':resumo'  => $resumo,
            ':toolkit' => json_encode($toolkit, JSON_UNESCAPED_UNICODE),
            ':versao'  => $versaoLlm,
        ]);
    }

    public function listar(int $limit = 50, int $offset = 0, array $filtros = [], ?int $cidadaoId = null): array
    {
        $where = [];
        $params = [];

        // Filtros de busca avançada
        if (!empty($filtros['ano'])) {
            $where[] = 'ano = :ano';
            $params[':ano'] = (int) $filtros['ano'];
        }

        if (!empty($filtros['numero'])) {
            $where[] = 'numero_pl = :numero';
            $params[':numero'] = $filtros['numero'];
        }

        if (!empty($filtros['entidade'])) {
            $where[] = 'entidade = :entidade';
            $params[':entidade'] = $filtros['entidade'];
        }

        if (!empty($filtros['busca'])) {
            $where[] = '(texto_original LIKE :busca OR origem LIKE :busca OR tramitacao_atual LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['ano_min'])) {
            $where[] = 'ano >= :ano_min';
            $params[':ano_min'] = (int) $filtros['ano_min'];
        }

        if (!empty($filtros['ano_max'])) {
            $where[] = 'ano <= :ano_max';
            $params[':ano_max'] = (int) $filtros['ano_max'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Ordenação
        $orderBy = 'atualizado_em DESC';
        if (!empty($filtros['ordenar'])) {
            $ordenacao = $filtros['ordenar'];
            $direcao = strtoupper($filtros['direcao'] ?? 'DESC');
            
            $camposValidos = ['relevancia_score', 'visualizacoes', 'atualizado_em', 'ano', 'numero_pl'];
            if (in_array($ordenacao, $camposValidos)) {
                $orderBy = "$ordenacao $direcao";
            }
        }

        // Seleção com contadores
        $sql = "SELECT pl.*, 
                       COALESCE((SELECT COUNT(*) FROM favoritos WHERE pl_id = pl.pl_id), 0) as favoritos_count,
                       pl.visualizacoes,
                       pl.relevancia_score";
        
        if ($cidadaoId) {
            $sql .= ", CASE WHEN f.cidadao_id IS NOT NULL THEN 1 ELSE 0 END as is_favorito";
        }
        
        $sql .= " FROM pl_dados_legais pl";
        
        if ($cidadaoId) {
            $sql .= " LEFT JOIN favoritos f ON pl.pl_id = f.pl_id AND f.cidadao_id = :cidadao_id";
            $params[':cidadao_id'] = $cidadaoId;
        }
        
        $sql .= " $whereClause
                  ORDER BY $orderBy
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function contarTotal(array $filtros = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filtros['ano'])) {
            $where[] = 'ano = :ano';
            $params[':ano'] = (int) $filtros['ano'];
        }

        if (!empty($filtros['numero'])) {
            $where[] = 'numero_pl = :numero';
            $params[':numero'] = $filtros['numero'];
        }

        if (!empty($filtros['entidade'])) {
            $where[] = 'entidade = :entidade';
            $params[':entidade'] = $filtros['entidade'];
        }

        if (!empty($filtros['busca'])) {
            $where[] = '(texto_original LIKE :busca OR origem LIKE :busca OR tramitacao_atual LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['ano_min'])) {
            $where[] = 'ano >= :ano_min';
            $params[':ano_min'] = (int) $filtros['ano_min'];
        }

        if (!empty($filtros['ano_max'])) {
            $where[] = 'ano <= :ano_max';
            $params[':ano_max'] = (int) $filtros['ano_max'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM pl_dados_legais $whereClause";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public function registrarVisualizacao(int $plId, ?int $cidadaoId = null, ?string $ipAddress = null): void
    {
        $sql = "INSERT INTO visualizacoes (pl_id, cidadao_id, ip_address) 
                VALUES (:pl_id, :cidadao_id, :ip)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pl_id' => $plId,
            ':cidadao_id' => $cidadaoId,
            ':ip' => $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
