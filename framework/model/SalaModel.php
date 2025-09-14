<?php

require_once './core/Conexao.php';

class SalaModel
{
    //como repetimos o $conexao = Conexao::getInstance(); podemos movelo para o cosntrutor da classe

    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function selectAll()
    {
        $sql = 'SELECT * FROM sala';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectId($idSala)
    {
        $sql = 'SELECT * FROM sala WHERE idSala = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create($nomeSala, $descricaoSala)
    {
        $sql = 'INSERT INTO sala (nomeSala, descricaoSala) VALUES (?, ?)';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nomeSala, $descricaoSala]);

        return ($stmt->rowCount() > 0);
    }

    public function update($idSala, $nomeSala, $descricaoSala)
    {
        $sql = 'UPDATE sala SET nomeSala = ?, descricaoSala = ? WHERE idSala = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nomeSala, $descricaoSala, $idSala]);

        return ($stmt->rowCount() > 0);
    }

    public function delete($idSala)
    {
        $sql = "DELETE FROM sala WHERE idSala = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idSala]);
        return ($stmt->rowCount() > 0);
    }

    // No arquivo SalaModel.php, adicione este método:

public function selectSalasComLotesAtivos()
{
    $sql = 'SELECT 
    s.idSala,
    s.nomeSala, 
    l.idLote, 
    l.dataInicio, 
    l.status AS status, 
    c.nomeCogumelo, 
    f.nomeFaseCultivo,
    (
        SELECT temperatura 
        FROM leitura 
        WHERE idLote = l.idLote 
        ORDER BY dataCriacao DESC 
        LIMIT 1
    ) AS temperatura,
    (
        SELECT umidade 
        FROM leitura 
        WHERE idLote = l.idLote 
        ORDER BY dataCriacao DESC 
        LIMIT 1
    ) AS umidade,
    (
        SELECT co2 
        FROM leitura 
        WHERE idLote = l.idLote 
        ORDER BY dataCriacao DESC 
        LIMIT 1
    ) AS co2
FROM sala s
INNER JOIN lote l 
    ON l.idSala = s.idSala 
   AND l.status = "ativo"
LEFT JOIN cogumelo c 
    ON c.idCogumelo = l.idCogumelo
LEFT JOIN historico_fase hf 
    ON hf.idLote = l.idLote
   AND hf.dataMudanca = (
        SELECT MAX(dataMudanca)
        FROM historico_fase
        WHERE idLote = l.idLote
    )
LEFT JOIN fase_cultivo f 
    ON f.idFaseCultivo = hf.idFaseCultivo
ORDER BY s.idSala, l.idLote;
';

    $stmt = $this->conexao->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
