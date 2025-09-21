<?php

require_once './core/Conexao.php';

class LoteModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function selectAll(): array
    {
        $sql = 'SELECT 
                    l.idLote,
                    l.idSala,
                    s.nomeSala,
                    l.idCogumelo,
                    c.nomeCogumelo,
                    l.dataInicio,
                    l.dataFim,
                    l.status
                FROM 
                    lote l
                JOIN sala s ON s.idSala = l.idSala
                JOIN cogumelo c ON c.idCogumelo = l.idCogumelo
                ORDER BY 
                    l.idLote DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectAtivos(): array
    {
        $sql = 'SELECT 
                l.idLote,
                l.idSala,
                s.nomeSala,
                l.idCogumelo,
                c.nomeCogumelo,
                l.dataInicio,
                l.dataFim,
                l.status
            FROM lote l
            JOIN sala s ON s.idSala = l.idSala
            JOIN cogumelo c ON c.idCogumelo = l.idCogumelo
            WHERE l.status = "ativo"
            ORDER BY l.idLote DESC';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function selectId(int $idLote): ?array
    {
        $sql = 'SELECT 
                    l.idLote,
                    l.idSala,
                    s.nomeSala,
                    l.idCogumelo,
                    c.nomeCogumelo,
                    l.dataInicio,
                    l.dataFim,
                    l.status
                FROM 
                    lote l
                JOIN sala s ON s.idSala = l.idSala
                JOIN cogumelo c ON c.idCogumelo = l.idCogumelo
                WHERE idLote = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idLote]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    //Consulta de sobreposição para verificar se a sala já está ocupada

    public function salaOcupada(int $idSala): bool
    {
        $sql = "SELECT COUNT(*) as total 
                FROM lote 
                WHERE idSala = ? 
                AND status = 'ativo'";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] > 0;
    }

    public function selectSalasSemLoteAtivo(): array
    {
        $sql = "
        SELECT s.idSala, s.nomeSala, s.descricaoSala, s.dataCriacao
        FROM sala s
        LEFT JOIN lote l
          ON l.idSala = s.idSala
         AND l.status = 'ativo'
        WHERE l.idLote IS NULL
        ORDER BY s.idSala
    ";
        $st = $this->conexao->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(
    int $idSala, 
    int $idCogumelo, 
    string $dataInicio, 
    ?string $dataFim, 
    string $status, 
    int $idFaseCultivo
): int {
    try {
        $this->conexao->beginTransaction();

        // 1. Criar lote
        $sql = 'INSERT INTO lote (idSala, idCogumelo, dataInicio, dataFim, status)
                VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala, $idCogumelo, $dataInicio, $dataFim, $status]);

        $idLote = (int)$this->conexao->lastInsertId();

        if ($idLote <= 0) {
            $this->conexao->rollBack();
            return 0;
        }

        // 2. Buscar parâmetros da fase de cultivo
        $sqlFase = 'SELECT 
                        temperaturaMin, 
                        temperaturaMax, 
                        umidadeMin, 
                        umidadeMax, 
                        co2Max
                    FROM fase_cultivo 
                    WHERE idFaseCultivo = ?';
        $stmtFase = $this->conexao->prepare($sqlFase);
        $stmtFase->execute([$idFaseCultivo]);
        $fase = $stmtFase->fetch(PDO::FETCH_ASSOC);

        if (!$fase) {
            $this->conexao->rollBack();
            return 0;
        }

        // 3. Inserir configuração vinculada ao lote
        $sqlConfig = 'INSERT INTO configuracao 
                        (idLote, temperaturaMin, temperaturaMax, umidadeMin, umidadeMax, co2Max) 
                      VALUES (?, ?, ?, ?, ?, ?)';
        $stmtConfig = $this->conexao->prepare($sqlConfig);
        $stmtConfig->execute([
            $idLote,
            $fase['temperaturaMin'],
            $fase['temperaturaMax'],
            $fase['umidadeMin'],
            $fase['umidadeMax'],
            $fase['co2Max']
        ]);

        // 4. Registrar fase inicial no histórico
        $sqlHist = 'INSERT INTO historico_fase (idLote, idFaseCultivo) VALUES (?, ?)';
        $stmtHist = $this->conexao->prepare($sqlHist);
        $stmtHist->execute([$idLote, $idFaseCultivo]);

        $this->conexao->commit();

        return $idLote;

    } catch (\Exception $e) {
        $this->conexao->rollBack();
        throw $e;
    }
}


    public function update(int $idLote, int $idSala, int $idCogumelo, string $dataInicio, ?string $dataFim, string $status): bool
    {
        $sql = 'UPDATE lote
                   SET idSala = ?, idCogumelo = ?, dataInicio = ?, dataFim = ?, status = ?
                 WHERE idLote = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala, $idCogumelo, $dataInicio, $dataFim, $status, $idLote]);

        // true se alterou algo; false se 0 linhas (sem alterações) ou erro
        return ($stmt->rowCount() > 0);
    }

    public function finalizar(int $idLote): bool
    {
        $sql = "UPDATE lote 
               SET status = 'finalizado', 
                   dataFim = CURDATE()
             WHERE idLote = ? 
               AND status <> 'finalizado'";
        $st = $this->conexao->prepare($sql);
        $st->execute([$idLote]);
        return ($st->rowCount() > 0);
    }

public function finalizar_fisico(int $idLote): bool
{
    try {
        $sql = "DELETE FROM lote WHERE idLote = ?";
        $st = $this->conexao->prepare($sql);
        $st->execute([$idLote]);
        return ($st->rowCount() > 0);
        
    } catch (PDOException $e) {
        // Log do erro (opcional)
        error_log("Erro ao excluir lote {$idLote}: " . $e->getMessage());
        throw $e; // Re-lança a exceção para tratamento na controller
    }
}
}
