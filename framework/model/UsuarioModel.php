<?php

require_once './core/Conexao.php';

class UsuarioModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    private function baseSelect(): string
    {
        return "SELECT 
                    idUsuario,
                    nomeUsuario,
                    email,
                    tipo,
                    dataCriacao
                FROM usuario";
    }

    public function selectAll(): array
    {
        $sql = $this->baseSelect() . " ORDER BY idUsuario DESC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectById(int $idUsuario): ?array
    {
        $sql = $this->baseSelect() . " WHERE idUsuario = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectByEmail(string $email): ?array
    {
        $sql = $this->baseSelect() . " WHERE email = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectAuthByEmail(string $email): ?array
    {
        $sql = "SELECT idUsuario, nomeUsuario, email, senha, tipo
                FROM usuario
                WHERE email = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $nomeUsuario, string $email, string $senhaHash, string $tipo): int
    {
        $sql = "INSERT INTO usuario (nomeUsuario, email, senha, tipo)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nomeUsuario, $email, $senhaHash, $tipo]);
        return (int)$this->conexao->lastInsertId();
    }

    public function update(int $idUsuario, string $nomeUsuario, string $email, string $tipo, ?string $senhaHash = null): bool
    {
        if ($senhaHash === null) {
            $sql = "UPDATE usuario
                       SET nomeUsuario = ?, email = ?, tipo = ?
                     WHERE idUsuario = ?";
            $params = [$nomeUsuario, $email, $tipo, $idUsuario];
        } else {
            $sql = "UPDATE usuario
                       SET nomeUsuario = ?, email = ?, senha = ?, tipo = ?
                     WHERE idUsuario = ?";
            $params = [$nomeUsuario, $email, $senhaHash, $tipo, $idUsuario];
        }

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);
        return ($stmt->rowCount() > 0);
    }

    public function delete(int $idUsuario): bool
    {
        $sql = "DELETE FROM usuario WHERE idUsuario = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idUsuario]);
        return ($stmt->rowCount() > 0);
    }
}
