<?php
// models/FavoritoModel.php

class FavoritoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function adicionar(int $cidadaoId, int $plId): bool
    {
        $sql = "INSERT INTO favoritos (cidadao_id, pl_id) VALUES (:cidadao_id, :pl_id)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                ':cidadao_id' => $cidadaoId,
                ':pl_id' => $plId,
            ]);
        } catch (PDOException $e) {
            // Duplicado
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function remover(int $cidadaoId, int $plId): bool
    {
        $sql = "DELETE FROM favoritos WHERE cidadao_id = :cidadao_id AND pl_id = :pl_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cidadao_id' => $cidadaoId,
            ':pl_id' => $plId,
        ]);
    }

    public function listarPorCidadao(int $cidadaoId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT pl.*, f.data_favorito
                FROM favoritos f
                INNER JOIN pl_dados_legais pl ON f.pl_id = pl.pl_id
                WHERE f.cidadao_id = :cidadao_id
                ORDER BY f.data_favorito DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cidadao_id', $cidadaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarPorCidadao(int $cidadaoId): int
    {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE cidadao_id = :cidadao_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cidadao_id' => $cidadaoId]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public function verificarFavorito(int $cidadaoId, int $plId): bool
    {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE cidadao_id = :cidadao_id AND pl_id = :pl_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cidadao_id' => $cidadaoId,
            ':pl_id' => $plId,
        ]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0) > 0;
    }

    public function contarFavoritosPorLei(int $plId): int
    {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE pl_id = :pl_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pl_id' => $plId]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }
}

