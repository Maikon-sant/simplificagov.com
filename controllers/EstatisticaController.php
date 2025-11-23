<?php
// controllers/EstatisticaController.php

class EstatisticaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // GET /estatisticas
    public function index(): void
    {
        try {
            $estatisticas = [
                'leis' => $this->getEstatisticasLeis(),
                'cidadaos' => $this->getEstatisticasCidadaos(),
                'favoritos' => $this->getEstatisticasFavoritos(),
                'parlamentares' => $this->getEstatisticasParlamentares(),
                'alertas' => $this->getEstatisticasAlertas(),
                'visualizacoes' => $this->getEstatisticasVisualizacoes(),
            ];

            response([
                'success' => true,
                'data' => $estatisticas,
            ]);
        } catch (Throwable $e) {
            response([
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                    ? 'Erro interno' 
                    : $e->getMessage()
            ], 500);
        }
    }

    // GET /estatisticas/leis
    public function leis(): void
    {
        try {
            $estatisticas = $this->getEstatisticasLeis();
            response([
                'success' => true,
                'data' => $estatisticas,
            ]);
        } catch (Throwable $e) {
            response([
                'success' => false,
                'message' => 'Erro ao obter estatísticas de leis',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                    ? 'Erro interno' 
                    : $e->getMessage()
            ], 500);
        }
    }

    // GET /estatisticas/cidadaos
    public function cidadaos(): void
    {
        try {
            $estatisticas = $this->getEstatisticasCidadaos();
            response([
                'success' => true,
                'data' => $estatisticas,
            ]);
        } catch (Throwable $e) {
            response([
                'success' => false,
                'message' => 'Erro ao obter estatísticas de cidadãos',
                'error' => defined('ENVIRONMENT') && ENVIRONMENT === 'production' 
                    ? 'Erro interno' 
                    : $e->getMessage()
            ], 500);
        }
    }

    private function getEstatisticasLeis(): array
    {
        // Total de leis
        $sql = "SELECT COUNT(*) as total FROM pl_dados_legais";
        $stmt = $this->db->query($sql);
        $total = (int) $stmt->fetch()['total'];

        // Por entidade
        $sql = "SELECT entidade, COUNT(*) as total 
                FROM pl_dados_legais 
                WHERE entidade IS NOT NULL
                GROUP BY entidade";
        $stmt = $this->db->query($sql);
        $porEntidade = $stmt->fetchAll();

        // Mais visualizadas
        $sql = "SELECT pl_id, numero_pl, ano, visualizacoes 
                FROM pl_dados_legais 
                ORDER BY visualizacoes DESC 
                LIMIT 10";
        $stmt = $this->db->query($sql);
        $maisVisualizadas = $stmt->fetchAll();

        // Mais favoritadas
        $sql = "SELECT pl.pl_id, pl.numero_pl, pl.ano, COUNT(f.favorito_id) as favoritos_count
                FROM pl_dados_legais pl
                LEFT JOIN favoritos f ON pl.pl_id = f.pl_id
                GROUP BY pl.pl_id
                ORDER BY favoritos_count DESC
                LIMIT 10";
        $stmt = $this->db->query($sql);
        $maisFavoritadas = $stmt->fetchAll();

        // Por ano
        $sql = "SELECT ano, COUNT(*) as total 
                FROM pl_dados_legais 
                WHERE ano IS NOT NULL
                GROUP BY ano 
                ORDER BY ano DESC 
                LIMIT 10";
        $stmt = $this->db->query($sql);
        $porAno = $stmt->fetchAll();

        return [
            'total' => $total,
            'por_entidade' => $porEntidade,
            'mais_visualizadas' => $maisVisualizadas,
            'mais_favoritadas' => $maisFavoritadas,
            'por_ano' => $porAno,
        ];
    }

    private function getEstatisticasCidadaos(): array
    {
        // Total
        $sql = "SELECT COUNT(*) as total FROM cidadao";
        $stmt = $this->db->query($sql);
        $total = (int) $stmt->fetch()['total'];

        // Por preferência de mídia
        $sql = "SELECT preferencia_midia, COUNT(*) as total 
                FROM cidadao 
                GROUP BY preferencia_midia";
        $stmt = $this->db->query($sql);
        $porMidia = $stmt->fetchAll();

        // Por região
        $sql = "SELECT regiao, COUNT(*) as total 
                FROM cidadao 
                WHERE regiao IS NOT NULL
                GROUP BY regiao 
                ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $porRegiao = $stmt->fetchAll();

        // Cadastros por mês (últimos 12 meses)
        $sql = "SELECT DATE_FORMAT(data_cadastro, '%Y-%m') as mes, COUNT(*) as total
                FROM cidadao
                WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY mes
                ORDER BY mes DESC";
        $stmt = $this->db->query($sql);
        $porMes = $stmt->fetchAll();

        return [
            'total' => $total,
            'por_preferencia_midia' => $porMidia,
            'por_regiao' => $porRegiao,
            'cadastros_por_mes' => $porMes,
        ];
    }

    private function getEstatisticasFavoritos(): array
    {
        try {
            // Total de favoritos
            $sql = "SELECT COUNT(*) as total FROM favoritos";
            $stmt = $this->db->query($sql);
            $total = (int) $stmt->fetch()['total'];

            // Cidadãos com mais favoritos
            $sql = "SELECT c.cidadao_id, c.nome, COUNT(f.favorito_id) as total_favoritos
                    FROM cidadao c
                    INNER JOIN favoritos f ON c.cidadao_id = f.cidadao_id
                    GROUP BY c.cidadao_id
                    ORDER BY total_favoritos DESC
                    LIMIT 10";
            $stmt = $this->db->query($sql);
            $topCidadaos = $stmt->fetchAll();

            return [
                'total' => $total,
                'top_cidadaos' => $topCidadaos,
            ];
        } catch (PDOException $e) {
            // Se a tabela não existir, retorna valores padrão
            return [
                'total' => 0,
                'top_cidadaos' => [],
            ];
        }
    }

    private function getEstatisticasParlamentares(): array
    {
        try {
            // Total
            $sql = "SELECT COUNT(*) as total FROM parlamentares WHERE ativo = 1";
            $stmt = $this->db->query($sql);
            $total = (int) $stmt->fetch()['total'];

            // Por casa
            $sql = "SELECT casa, COUNT(*) as total 
                    FROM parlamentares 
                    WHERE ativo = 1
                    GROUP BY casa";
            $stmt = $this->db->query($sql);
            $porCasa = $stmt->fetchAll();

            // Por partido
            $sql = "SELECT partido, COUNT(*) as total 
                    FROM parlamentares 
                    WHERE ativo = 1 AND partido IS NOT NULL
                    GROUP BY partido 
                    ORDER BY total DESC 
                    LIMIT 10";
            $stmt = $this->db->query($sql);
            $porPartido = $stmt->fetchAll();

            // Por UF
            $sql = "SELECT uf, COUNT(*) as total 
                    FROM parlamentares 
                    WHERE ativo = 1 AND uf IS NOT NULL
                    GROUP BY uf 
                    ORDER BY total DESC";
            $stmt = $this->db->query($sql);
            $porUF = $stmt->fetchAll();

            return [
                'total' => $total,
                'por_casa' => $porCasa,
                'por_partido' => $porPartido,
                'por_uf' => $porUF,
            ];
        } catch (PDOException $e) {
            // Se a tabela não existir, retorna valores padrão
            return [
                'total' => 0,
                'por_casa' => [],
                'por_partido' => [],
                'por_uf' => [],
            ];
        }
    }

    private function getEstatisticasAlertas(): array
    {
        try {
            // Total
            $sql = "SELECT COUNT(*) as total FROM alertas";
            $stmt = $this->db->query($sql);
            $total = (int) $stmt->fetch()['total'];

            // Ativos
            $sql = "SELECT COUNT(*) as total FROM alertas WHERE ativo = 1";
            $stmt = $this->db->query($sql);
            $ativos = (int) $stmt->fetch()['total'];

            // Por tipo
            $sql = "SELECT tipo_alerta, COUNT(*) as total 
                    FROM alertas 
                    GROUP BY tipo_alerta";
            $stmt = $this->db->query($sql);
            $porTipo = $stmt->fetchAll();

            return [
                'total' => $total,
                'ativos' => $ativos,
                'inativos' => $total - $ativos,
                'por_tipo' => $porTipo,
            ];
        } catch (PDOException $e) {
            // Se a tabela não existir, retorna valores padrão
            return [
                'total' => 0,
                'ativos' => 0,
                'inativos' => 0,
                'por_tipo' => [],
            ];
        }
    }

    private function getEstatisticasVisualizacoes(): array
    {
        try {
            // Total
            $sql = "SELECT COUNT(*) as total FROM visualizacoes";
            $stmt = $this->db->query($sql);
            $total = (int) $stmt->fetch()['total'];

            // Últimas 24 horas
            $sql = "SELECT COUNT(*) as total 
                    FROM visualizacoes 
                    WHERE data_visualizacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt = $this->db->query($sql);
            $ultimas24h = (int) $stmt->fetch()['total'];

            // Por dia (últimos 30 dias)
            $sql = "SELECT DATE(data_visualizacao) as dia, COUNT(*) as total
                    FROM visualizacoes
                    WHERE data_visualizacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY dia
                    ORDER BY dia DESC";
            $stmt = $this->db->query($sql);
            $porDia = $stmt->fetchAll();

            return [
                'total' => $total,
                'ultimas_24h' => $ultimas24h,
                'por_dia' => $porDia,
            ];
        } catch (PDOException $e) {
            // Se a tabela não existir, retorna valores padrão
            return [
                'total' => 0,
                'ultimas_24h' => 0,
                'por_dia' => [],
            ];
        }
    }
}

