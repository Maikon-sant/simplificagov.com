<?php
// models/ParlamentarModel.php

class ParlamentarModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data): int
    {
        $sql = "INSERT INTO parlamentares (nome, nome_civil, cpf, partido, uf, casa, cargo, email, telefone, biografia, foto_url, focus)
                VALUES (:nome, :nome_civil, :cpf, :partido, :uf, :casa, :cargo, :email, :telefone, :biografia, :foto_url, :focus)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $data['nome'],
            ':nome_civil' => $data['nome_civil'] ?? null,
            ':cpf' => $data['cpf'] ?? null,
            ':partido' => $data['partido'] ?? null,
            ':uf' => $data['uf'] ?? null,
            ':casa' => $data['casa'],
            ':cargo' => $data['cargo'] ?? null,
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':biografia' => $data['biografia'] ?? null,
            ':foto_url' => $data['foto_url'] ?? null,
            ':focus' => isset($data['focus']) ? json_encode($data['focus'], JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function buscarPorId(int $id, bool $incluirAnalytics = false): ?array
    {
        $sql = "SELECT * FROM parlamentares WHERE parlamentar_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch();
        
        if ($p) {
            // Decodificar focus JSON se existir
            if ($p['focus']) {
                $p['focus'] = json_decode($p['focus'], true);
            }
            
            // Carregar leis relacionadas
            $p['leis'] = $this->buscarLeisPorParlamentar($id);
            
            // Calcular analytics apenas se solicitado
            if ($incluirAnalytics) {
                $p['analytics'] = $this->buscarAnalytics($id);
            }
        }
        
        return $p ?: null;
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['ativo = 1'];
        $params = [];

        if (!empty($filtros['casa'])) {
            $where[] = 'casa = :casa';
            $params[':casa'] = $filtros['casa'];
        }

        if (!empty($filtros['uf'])) {
            $where[] = 'uf = :uf';
            $params[':uf'] = $filtros['uf'];
        }

        if (!empty($filtros['partido'])) {
            $where[] = 'partido = :partido';
            $params[':partido'] = $filtros['partido'];
        }

        if (!empty($filtros['busca'])) {
            $where[] = '(nome LIKE :busca OR nome_civil LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        $sql = "SELECT * FROM parlamentares 
                WHERE " . implode(' AND ', $where) . "
                ORDER BY nome 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $parlamentares = $stmt->fetchAll();
        
        // Decodificar focus JSON para cada parlamentar
        foreach ($parlamentares as &$parlamentar) {
            if ($parlamentar['focus']) {
                $parlamentar['focus'] = json_decode($parlamentar['focus'], true);
            }
        }
        
        return $parlamentares;
    }

    public function contarTotal(array $filtros = []): int
    {
        $where = ['ativo = 1'];
        $params = [];

        if (!empty($filtros['casa'])) {
            $where[] = 'casa = :casa';
            $params[':casa'] = $filtros['casa'];
        }

        if (!empty($filtros['uf'])) {
            $where[] = 'uf = :uf';
            $params[':uf'] = $filtros['uf'];
        }

        if (!empty($filtros['partido'])) {
            $where[] = 'partido = :partido';
            $params[':partido'] = $filtros['partido'];
        }

        if (!empty($filtros['busca'])) {
            $where[] = '(nome LIKE :busca OR nome_civil LIKE :busca)';
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        $sql = "SELECT COUNT(*) as total FROM parlamentares WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public function atualizar(int $id, array $data): bool
    {
        $campos = [];
        $params = [':id' => $id];

        $allowedFields = ['nome', 'nome_civil', 'cpf', 'partido', 'uf', 'casa', 'cargo', 'email', 'telefone', 'biografia', 'foto_url', 'ativo', 'focus'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'focus') {
                    $campos[] = "$field = :$field";
                    $params[":$field"] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                } else {
                    $campos[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE parlamentares SET " . implode(', ', $campos) . " WHERE parlamentar_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deletar(int $id): bool
    {
        $sql = "UPDATE parlamentares SET ativo = 0 WHERE parlamentar_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function associarLei(int $parlamentarId, int $plId, string $tipoRelacao): bool
    {
        $sql = "INSERT INTO parlamentar_lei (parlamentar_id, pl_id, tipo_relacao)
                VALUES (:parlamentar_id, :pl_id, :tipo)
                ON DUPLICATE KEY UPDATE tipo_relacao = VALUES(tipo_relacao)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':parlamentar_id' => $parlamentarId,
            ':pl_id' => $plId,
            ':tipo' => $tipoRelacao,
        ]);
    }

    private function buscarLeisPorParlamentar(int $parlamentarId): array
    {
        $sql = "SELECT pl.*, pl_rel.tipo_relacao,
                       COALESCE((SELECT COUNT(*) FROM favoritos WHERE pl_id = pl.pl_id), 0) as favoritos_count
                FROM parlamentar_lei pl_rel
                INNER JOIN pl_dados_legais pl ON pl_rel.pl_id = pl.pl_id
                WHERE pl_rel.parlamentar_id = :id
                ORDER BY pl.atualizado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $parlamentarId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca analytics do cache ou calcula se necessário
     */
    public function buscarAnalytics(int $parlamentarId, bool $forcarRecalculo = false): array
    {
        if (!$forcarRecalculo) {
            $sql = "SELECT engajamento_score, total_projetos, total_visualizacoes, total_favoritos, areas_foco_json, data_calculo
                    FROM parlamentar_analytics 
                    WHERE parlamentar_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $parlamentarId]);
            $cache = $stmt->fetch();
            
            if ($cache) {
                return [
                    'engajamento_score' => (float) $cache['engajamento_score'],
                    'total_projetos' => (int) $cache['total_projetos'],
                    'total_visualizacoes' => (int) $cache['total_visualizacoes'],
                    'total_favoritos' => (int) $cache['total_favoritos'],
                    'areas_foco' => json_decode($cache['areas_foco_json'], true) ?? [],
                    'data_calculo' => $cache['data_calculo'],
                ];
            }
        }
        
        // Se não há cache ou forçou recálculo, calcular
        return $this->calcularAnalytics($parlamentarId);
    }

    /**
     * Calcula analytics de engajamento e áreas de foco do parlamentar
     */
    private function calcularAnalytics(int $parlamentarId): array
    {
        // Buscar projetos do parlamentar
        $leis = $this->buscarLeisPorParlamentar($parlamentarId);
        
        $totalProjetos = count($leis);
        $totalVisualizacoes = 0;
        $totalFavoritos = 0;
        $palavrasChave = [];
        
        foreach ($leis as $lei) {
            $totalVisualizacoes += (int) ($lei['visualizacoes'] ?? 0);
            $totalFavoritos += (int) ($lei['favoritos_count'] ?? 0);
            
            // Extrair palavras-chave do texto e ementa
            $texto = strtolower(($lei['texto_original'] ?? '') . ' ' . ($lei['ementa'] ?? ''));
            $palavras = $this->extrairPalavrasChave($texto);
            $palavrasChave = array_merge($palavrasChave, $palavras);
        }
        
        // Contar frequência de palavras-chave
        $frequencia = array_count_values($palavrasChave);
        arsort($frequencia);
        
        // Top 5 áreas de foco
        $areasFoco = array_slice($frequencia, 0, 5, true);
        
        // Calcular score de engajamento
        // Fórmula: (projetos * 10) + (visualizações * 0.1) + (favoritos * 5)
        $engajamentoScore = ($totalProjetos * 10) + ($totalVisualizacoes * 0.1) + ($totalFavoritos * 5);
        
        // Atualizar ou inserir analytics no cache
        $this->salvarAnalytics($parlamentarId, $engajamentoScore, $totalProjetos, $totalVisualizacoes, $totalFavoritos, $areasFoco);
        
        return [
            'engajamento_score' => round($engajamentoScore, 2),
            'total_projetos' => $totalProjetos,
            'total_visualizacoes' => $totalVisualizacoes,
            'total_favoritos' => $totalFavoritos,
            'areas_foco' => $areasFoco,
        ];
    }

    /**
     * Extrai palavras-chave relevantes do texto
     */
    private function extrairPalavrasChave(string $texto): array
    {
        // Remover caracteres especiais e normalizar
        $texto = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $texto);
        
        // Lista de stopwords em português
        $stopwords = [
            'a', 'o', 'e', 'de', 'do', 'da', 'em', 'para', 'com', 'por', 'que', 'um', 'uma',
            'é', 'são', 'ser', 'foi', 'está', 'estão', 'ter', 'tem', 'terá', 'terão',
            'se', 'não', 'mais', 'como', 'quando', 'onde', 'qual', 'quais',
            'lei', 'projeto', 'lei', 'artigo', 'artigos', 'parágrafo', 'inciso',
            'federal', 'nacional', 'brasil', 'brasileiro', 'brasileira',
            'poder', 'poderes', 'público', 'pública', 'públicas', 'públicos',
        ];
        
        // Dividir em palavras
        $palavras = preg_split('/\s+/', $texto);
        
        // Filtrar palavras relevantes
        $palavrasChave = [];
        foreach ($palavras as $palavra) {
            $palavra = trim($palavra);
            // Considerar apenas palavras com 4+ caracteres e que não sejam stopwords
            if (mb_strlen($palavra) >= 4 && !in_array($palavra, $stopwords, true)) {
                $palavrasChave[] = $palavra;
            }
        }
        
        return $palavrasChave;
    }

    /**
     * Salva analytics no cache
     */
    private function salvarAnalytics(int $parlamentarId, float $engajamentoScore, int $totalProjetos, int $totalVisualizacoes, int $totalFavoritos, array $areasFoco): void
    {
        $sql = "INSERT INTO parlamentar_analytics 
                (parlamentar_id, engajamento_score, total_projetos, total_visualizacoes, total_favoritos, areas_foco_json, data_calculo)
                VALUES (:parlamentar_id, :engajamento, :projetos, :visualizacoes, :favoritos, :areas_foco, NOW())
                ON DUPLICATE KEY UPDATE
                    engajamento_score = VALUES(engajamento_score),
                    total_projetos = VALUES(total_projetos),
                    total_visualizacoes = VALUES(total_visualizacoes),
                    total_favoritos = VALUES(total_favoritos),
                    areas_foco_json = VALUES(areas_foco_json),
                    data_calculo = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':parlamentar_id' => $parlamentarId,
            ':engajamento' => $engajamentoScore,
            ':projetos' => $totalProjetos,
            ':visualizacoes' => $totalVisualizacoes,
            ':favoritos' => $totalFavoritos,
            ':areas_foco' => json_encode($areasFoco, JSON_UNESCAPED_UNICODE),
        ]);
    }
}

