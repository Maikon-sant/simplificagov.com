<?php
// models/CidadaoModel.php

class CidadaoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data): int
    {
        $sql = "INSERT INTO cidadao (nome, contato, email, senha_hash, faixa_etaria, regiao, preferencia_midia, data_cadastro)
                VALUES (:nome, :contato, :email, :senha_hash, :faixa, :regiao, :midia, CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome'      => $data['nome'],
            ':contato'   => $data['contato'] ?? $data['email'] ?? null,
            ':email'     => $data['email'] ?? null,
            ':senha_hash' => $data['senha_hash'] ?? null,
            ':faixa'     => $data['faixa_etaria'] ?? null,
            ':regiao'    => $data['regiao'] ?? null,
            ':midia'     => $data['preferencia_midia'] ?? 'texto',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function listar(): array
    {
        $sql = "SELECT * FROM cidadao ORDER BY data_cadastro DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function atualizarPreferencia(int $cidadaoId, string $midia): bool
    {
        $sql = "UPDATE cidadao SET preferencia_midia = :midia WHERE cidadao_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':midia' => $midia,
            ':id'    => $cidadaoId,
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM cidadao WHERE cidadao_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $c = $stmt->fetch();
        return $c ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM cidadao WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $c = $stmt->fetch();
        return $c ?: null;
    }

    public function listarComPaginacao(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT cidadao_id, nome, email, contato, faixa_etaria, regiao, preferencia_midia, data_cadastro, ativo
                FROM cidadao 
                ORDER BY data_cadastro DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarTotal(): int
    {
        $sql = "SELECT COUNT(*) as total FROM cidadao";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }
}
