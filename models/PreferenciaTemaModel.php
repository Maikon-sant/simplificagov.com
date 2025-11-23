<?php
// models/PreferenciaTemaModel.php

class PreferenciaTemaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function adicionar(int $cidadaoId, string $tema, string $nivelInteresse = 'medio'): bool
    {
        $sql = "INSERT INTO preferencias_temas (cidadao_id, tema, nivel_interesse)
                VALUES (:cidadao_id, :tema, :nivel)
                ON DUPLICATE KEY UPDATE nivel_interesse = VALUES(nivel_interesse)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cidadao_id' => $cidadaoId,
            ':tema' => $tema,
            ':nivel' => $nivelInteresse,
        ]);
    }

    public function remover(int $cidadaoId, string $tema): bool
    {
        $sql = "DELETE FROM preferencias_temas WHERE cidadao_id = :cidadao_id AND tema = :tema";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cidadao_id' => $cidadaoId,
            ':tema' => $tema,
        ]);
    }

    public function listarPorCidadao(int $cidadaoId): array
    {
        $sql = "SELECT * FROM preferencias_temas WHERE cidadao_id = :cidadao_id ORDER BY tema";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cidadao_id' => $cidadaoId]);
        return $stmt->fetchAll();
    }

    public function atualizarNivel(int $cidadaoId, string $tema, string $nivelInteresse): bool
    {
        $sql = "UPDATE preferencias_temas 
                SET nivel_interesse = :nivel 
                WHERE cidadao_id = :cidadao_id AND tema = :tema";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nivel' => $nivelInteresse,
            ':cidadao_id' => $cidadaoId,
            ':tema' => $tema,
        ]);
    }
}

