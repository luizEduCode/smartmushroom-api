<?php
require_once './core/Conexao.php';

class HistoricoFaseModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    private function baseSelect(): string
    {
        return 'SELECT 
                hf.idHistorico, hf.dataMudanca,
                hf.idLote, l.status AS loteStatus, l.dataInicio AS loteDataInicio, l.dataFim AS loteDataFim,
                l.idSala, s.nomeSala,
                l.idCogumelo, c.nomeCogumelo,
                hf.idFaseCultivo,
                fc.nomeFaseCultivo, fc.descricaoFaseCultivo,
                fc.temperaturaMin, fc.temperaturaMax, fc.umidadeMin, fc.umidadeMax, fc.co2Max
            FROM historico_fase hf
            INNER JOIN lote         l  ON l.idLote = hf.idLote
            INNER JOIN sala         s  ON s.idSala = l.idSala
            INNER JOIN cogumelo     c  ON c.idCogumelo = l.idCogumelo
            INNER JOIN fase_cultivo fc ON fc.idFaseCultivo = hf.idFaseCultivo';
    }

    public function selectAll(): array
    {
        $sql = $this->baseSelect() . ' ORDER BY hf.idLote DESC, hf.dataMudanca DESC, hf.idHistorico DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectByIdLote(int $idLote): array
    {
        $sql = $this->baseSelect() . ' WHERE hf.idLote = ? 
            ORDER BY hf.dataMudanca DESC, hf.idHistorico DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectByIdHistorico(int $idHistorico): ?array
    {
        $sql = $this->baseSelect() . ' WHERE hf.idHistorico = ? LIMIT 1';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idHistorico]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectAtivosByIdFase(int $idFaseCultivo): array
    {
        $sql = $this->baseSelect() . ' 
        WHERE hf.idFaseCultivo = ? 
          AND l.status = ?
        ORDER BY hf.dataMudanca DESC, hf.idHistorico DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idFaseCultivo, 'ativo']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $idLote, int $idFaseCultivo): int
    {
        $sql = "INSERT INTO historico_fase (idLote, idFaseCultivo) VALUES (?, ?)";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote, $idFaseCultivo]);

        if ($stmt->rowCount() > 0) {
            return (int)$this->conexao->lastInsertId();
        }
        return 0;
    }

    public function update(int $idHistorico, int $idLote, int $idFaseCultivo): bool
    {
        $sql = "UPDATE historico_fase SET idLote = ?, idFaseCultivo = ? WHERE idHistorico = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote, $idFaseCultivo, $idHistorico]);

        // true se alterou algo; false se 0 linhas (sem alterações) ou erro
        return ($stmt->rowCount() > 0);
    }

    public function delete(int $idHistorico): bool
    {
        $sql = "DELETE FROM historico_fase WHERE idHistorico = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idHistorico]);
        return ($stmt->rowCount() > 0);
    }
}
