<?php
require_once './core/Conexao.php';

class ControleAtuadorModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    private function baseSelect(): string
    {
        return 'SELECT
                    ca.idControle,
                    ca.idAtuador,
                    a.nomeAtuador,
                    a.tipoAtuador,
                    ca.idLote,
                    l.status AS loteStatus,
                    l.dataInicio AS loteDataInicio,
                    l.dataFim AS loteDataFim,
                    s.nomeSala,
                    c.nomeCogumelo,
                    ca.statusAtuador,
                    ca.dataCriacao
                FROM controle_atuador ca
                INNER JOIN atuador a ON a.idAtuador = ca.idAtuador
                INNER JOIN lote l ON l.idLote = ca.idLote
                INNER JOIN sala s ON s.idSala = l.idSala
                INNER JOIN cogumelo c ON c.idCogumelo = l.idCogumelo';
    }

    public function selectAll(): array
    {
        $sql = $this->baseSelect() . ' ORDER BY ca.dataCriacao DESC, ca.idControle DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectByIdControle(int $idControle): ?array
    {
        $sql = $this->baseSelect() . ' WHERE ca.idControle = ? LIMIT 1';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idControle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectByIdAtuador(int $idAtuador): array
    {
        $sql = $this->baseSelect() . ' WHERE ca.idAtuador = ? ORDER BY ca.dataCriacao DESC, ca.idControle DESC LIMIT 1';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idAtuador]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function selectLatestStatusByLote(int $idLote): array
    {
        $sql = "
      SELECT t.idControle, t.idAtuador, a.nomeAtuador, a.tipoAtuador,
             t.idLote, t.statusAtuador, t.dataCriacao
      FROM (
        SELECT ca.*,
               ROW_NUMBER() OVER(PARTITION BY ca.idAtuador
                                 ORDER BY ca.dataCriacao DESC, ca.idControle DESC) rn
        FROM controle_atuador ca
        WHERE ca.idLote = :idLote
      ) t
      JOIN atuador a ON a.idAtuador = t.idAtuador
      WHERE t.rn = 1
      ORDER BY t.idAtuador;
    ";
        $st = $this->conexao->prepare($sql);
        $st->execute([':idLote' => $idLote]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }


    public function create(int $idAtuador, int $idLote, string $statusAtuador): int
    {
        $sql = 'INSERT INTO controle_atuador (idAtuador, idLote, statusAtuador)
                VALUES (?, ?, ?)';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idAtuador, $idLote, $statusAtuador]);

        return ($stmt->rowCount() > 0) ? (int)$this->conexao->lastInsertId() : 0;
    }

    /**
     * Atualiza todos os registros do atuador definindo o status informado.
     * Filtra por sala via JOIN com a tabela lote (ca.idLote = l.idLote).
     * Ex: UPDATE controle_atuador ca
     *        JOIN lote l ON ca.idLote = l.idLote
     *      SET ca.statusAtuador = ?
     *      WHERE ca.idAtuador = ? AND l.idSala = ?
     */
    // public function updateStatusByAtuador(int $idAtuador, string $statusAtuador): bool
    // {
    //     $sql = 'UPDATE controle_atuador SET statusAtuador = ?
    //              WHERE idAtuador = ?';
    //     $st = $this->conexao->prepare($sql);
    //     $st->execute([$statusAtuador, $idAtuador]);
    //     return ($st->rowCount() > 0);
    // }

    // /**
    //  * Mesma semântica de updateStatusByAtuador — compatibilidade com controller.
    //  */
    // public function updateStatus(int $idAtuador, string $statusAtuador): bool
    // {
    //     return $this->updateStatusByAtuador($idAtuador, $statusAtuador);
    // }

    /**
     * Exclusão lógica: atualiza o status para 'inativo'.
     */
    public function deleteLogico(int $idControle): bool
    {
        $sql = "UPDATE controle_atuador SET statusAtuador = 'inativo' WHERE idControle = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idControle]);
        return ($stmt->rowCount() > 0);
    }

    /**
     * Exclusão física: remove permanentemente o registro do banco de dados.
     */
    public function deleteFisico(int $idControle): bool
    {
        $sql = 'DELETE FROM controle_atuador WHERE idControle = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idControle]);
        return ($stmt->rowCount() > 0);
    }
}
