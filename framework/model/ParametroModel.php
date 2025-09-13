<?php
require_once './core/Conexao.php';

class ParametroModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    private function baseSelect(): string
    {
        return "SELECT 
                    idConfig,
                    idLote,
                    umidadeMin,
                    umidadeMax,
                    temperaturaMin,
                    temperaturaMax,
                    co2Max,
                    dataCriacao
                FROM configuracao";
    }

    public function selectAll(): array
    {
        $sql = $this->baseSelect() . " ORDER BY dataCriacao DESC, idConfig DESC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectByIdParametro(int $id): ?array
    {
        $sql = $this->baseSelect() . " WHERE idConfig = ? LIMIT 1";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna TODAS as configs do lote (mais comum é usar a mais recente no controller)
     */
    public function selectByIdLote(int $idLote): array
    {
        $sql = $this->baseSelect() . " WHERE idLote = ? ORDER BY dataCriacao DESC, idConfig DESC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        int $idLote,
        float $uMin,
        float $uMax,
        float $tMin,
        float $tMax,
        float $co2Max,
    ): int {
        $sql = "INSERT INTO configuracao
                   (idLote, umidadeMin, umidadeMax, temperaturaMin, temperaturaMax, co2Max)
                VALUES (?,?,?,?,?,?)";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote, $uMin, $uMax, $tMin, $tMax, $co2Max]);
        return $stmt->rowCount() > 0 ? (int)$this->conexao->lastInsertId() : 0;
    }

    public function update(
        int $idConfig,
        int $idLote,
        float $uMin,
        float $uMax,
        float $tMin,
        float $tMax,
        float $co2Max
    ): bool {
        $sql = "UPDATE configuracao
                   SET idLote = ?, umidadeMin = ?, umidadeMax = ?, 
                       temperaturaMin = ?, temperaturaMax = ?, co2Max = ?
                 WHERE idConfig = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote, $uMin, $uMax, $tMin, $tMax, $co2Max, $idConfig]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $idConfig): bool
    {
        $sql = "DELETE FROM configuracao WHERE idConfig = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idConfig]);
        return $stmt->rowCount() > 0;
    }
}
