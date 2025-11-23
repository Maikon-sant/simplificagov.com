<?php
// models/AlertaModel.php

class AlertaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data): int
    {
        $sql = "INSERT INTO alertas (cidadao_id, pl_id, tipo_alerta, titulo, descricao, filtros_json, ativo, lido)
                VALUES (:cidadao_id, :pl_id, :tipo, :titulo, :descricao, :filtros, :ativo, :lido)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cidadao_id' => $data['cidadao_id'],
            ':pl_id' => $data['pl_id'] ?? null,
            ':tipo' => $data['tipo_alerta'],
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':filtros' => isset($data['filtros_json']) ? json_encode($data['filtros_json']) : null,
            ':ativo' => $data['ativo'] ?? 1,
            ':lido' => $data['lido'] ?? 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT a.*, pl.numero_pl, pl.ano 
                FROM alertas a
                LEFT JOIN pl_dados_legais pl ON a.pl_id = pl.pl_id
                WHERE a.alerta_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $a = $stmt->fetch();
        
        if ($a) {
            if ($a['filtros_json']) {
                $a['filtros_json'] = json_decode($a['filtros_json'], true);
            }
            // Adicionar campos read e type para compatibilidade
            $a['read'] = (bool) ($a['lido'] ?? 0);
            $a['type'] = $a['tipo_alerta'] ?? null;
        }
        
        return $a ?: null;
    }

    public function listarPorCidadao(int $cidadaoId, bool $apenasAtivos = true): array
    {
        $where = ['cidadao_id = :cidadao_id'];
        $params = [':cidadao_id' => $cidadaoId];

        if ($apenasAtivos) {
            $where[] = 'ativo = 1';
        }

        $sql = "SELECT a.*, pl.numero_pl, pl.ano 
                FROM alertas a
                LEFT JOIN pl_dados_legais pl ON a.pl_id = pl.pl_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.data_criacao DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $alertas = $stmt->fetchAll();

        foreach ($alertas as &$alerta) {
            if ($alerta['filtros_json']) {
                $alerta['filtros_json'] = json_decode($alerta['filtros_json'], true);
            }
            // Adicionar campos read e type para compatibilidade
            $alerta['read'] = (bool) ($alerta['lido'] ?? 0);
            $alerta['type'] = $alerta['tipo_alerta'] ?? null;
        }

        return $alertas;
    }

    public function atualizar(int $id, array $data): bool
    {
        $campos = [];
        $params = [':id' => $id];

        $allowedFields = ['tipo_alerta', 'titulo', 'descricao', 'ativo', 'filtros_json', 'pl_id', 'lido'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'filtros_json') {
                    $campos[] = "$field = :$field";
                    $params[":$field"] = json_encode($data[$field]);
                } else {
                    $campos[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE alertas SET " . implode(', ', $campos) . " WHERE alerta_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM alertas WHERE alerta_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function ativar(int $id): bool
    {
        return $this->atualizar($id, ['ativo' => 1]);
    }

    public function desativar(int $id): bool
    {
        return $this->atualizar($id, ['ativo' => 0]);
    }

    public function atualizarUltimaNotificacao(int $id): bool
    {
        $sql = "UPDATE alertas SET ultima_notificacao = CURRENT_TIMESTAMP WHERE alerta_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function listarAlertasAtivosParaNotificacao(): array
    {
        $sql = "SELECT a.*, c.email, c.contato, c.preferencia_midia
                FROM alertas a
                INNER JOIN cidadao c ON a.cidadao_id = c.cidadao_id
                WHERE a.ativo = 1
                ORDER BY a.data_criacao DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Marca um alerta como lido
     */
    public function marcarComoLido(int $id, int $cidadaoId): bool
    {
        $sql = "UPDATE alertas 
                SET lido = 1, data_leitura = NOW() 
                WHERE alerta_id = :id AND cidadao_id = :cidadao_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':cidadao_id' => $cidadaoId,
        ]);
    }

    /**
     * Marca um alerta como não lido
     */
    public function marcarComoNaoLido(int $id, int $cidadaoId): bool
    {
        $sql = "UPDATE alertas 
                SET lido = 0, data_leitura = NULL 
                WHERE alerta_id = :id AND cidadao_id = :cidadao_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':cidadao_id' => $cidadaoId,
        ]);
    }
}

