<?php
require_once './core/Conexao.php';

class AtuadorModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function selectAll(): array
    {
        $sql = "SELECT idAtuador, idSala, nomeAtuador, tipoAtuador, dataCriacao
                  FROM atuador
              ORDER BY idAtuador DESC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function selectIdAtuador(int $idAtuador): ?array
    {
        $sql = "SELECT idAtuador, idSala, nomeAtuador, tipoAtuador, dataCriacao
                  FROM atuador
                 WHERE idAtuador = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idAtuador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectIdSala(int $idSala): array
    {
        $sql = "SELECT idAtuador, idSala, nomeAtuador, tipoAtuador, dataCriacao
                  FROM atuador
                 WHERE idSala = ?
              ORDER BY idAtuador DESC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO atuador (idSala, nomeAtuador, tipoAtuador)
                VALUES (?, ?, ?)";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$data['idSala'], $data['nomeAtuador'], $data['tipoAtuador']]); //aqui eu testei receber a array de data ou inves das variáveis. 

        if ($stmt->rowCount() > 0) {
            return (int)$this->conexao->lastInsertId();
        }
        return 0;
    }

    public function update(int $idAtuador, int $idSala, string $nomeAtuador, string $tipoAtuador): bool
    {
        $sql = "UPDATE atuador
                   SET idSala = ?, nomeAtuador = ?, tipoAtuador = ?
                 WHERE idAtuador = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala, $nomeAtuador, $tipoAtuador, $idAtuador]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $idAtuador): bool
    {
        $sql = "DELETE FROM atuador WHERE idAtuador = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idAtuador]);
        return $stmt->rowCount() > 0;
    }
}
