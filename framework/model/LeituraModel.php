<?php
require_once './core/Conexao.php';

class LeituraModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    private function baseSelect(): string
    {
        return 'SELECT
                    le.idLeitura,
                    le.idLote,
                    le.umidade,
                    le.temperatura,
                    le.co2,
                    le.luz,
                    le.dataCriacao,
                    l.status      AS loteStatus,
                    l.dataInicio  AS loteDataInicio
                FROM leitura le
                INNER JOIN lote l ON l.idLote = le.idLote';
    }

    public function selectAll(): array
    {
        $sql = $this->baseSelect() . ' ORDER BY le.dataCriacao DESC, le.idLeitura DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectByIdLeitura(int $idLeitura): ?array
    {
        $sql = $this->baseSelect() . ' WHERE le.idLeitura = ? LIMIT 1';
        $st  = $this->conexao->prepare($sql);
        $st->execute([$idLeitura]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function selectByIdLote(int $idLote): array
    {
        $sql = $this->baseSelect() . ' WHERE le.idLote = ?
                ORDER BY le.dataCriacao DESC, le.idLeitura DESC';
        $st  = $this->conexao->prepare($sql);
        $st->execute([$idLote]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $idLote, float $umidade, float $temperatura, float $co2, string $luz = 'ligado'): int
    {
        $sql = 'INSERT INTO leitura (idLote, umidade, temperatura, co2, luz)
                VALUES (?,?,?,?,?)';
        $st  = $this->conexao->prepare($sql);
        $st->execute([$idLote, $umidade, $temperatura, $co2, $luz]);
        return $st->rowCount() > 0 ? (int)$this->conexao->lastInsertId() : 0;
    }

    public function update(int $idLeitura, int $idLote, float $umidade, float $temperatura, float $co2, string $luz): bool
    {
        $sql = 'UPDATE leitura
                   SET idLote = ?, umidade = ?, temperatura = ?, co2 = ?, luz = ?
                 WHERE idLeitura = ?';
        $st  = $this->conexao->prepare($sql);
        $st->execute([$idLote, $umidade, $temperatura, $co2, $luz, $idLeitura]);
        return $st->rowCount() > 0;
    }

    public function delete(int $idLeitura): bool
    {
        $sql = 'DELETE FROM leitura WHERE idLeitura = ?';
        $st  = $this->conexao->prepare($sql);
        $st->execute([$idLeitura]);
        return $st->rowCount() > 0;
    }
}
