<?php
require_once './core/Conexao.php';

class FaseCultivoModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function selectAll()
    {
        $sql =
            'SELECT 
            fc.idFaseCultivo,
            fc.nomeFaseCultivo,
            fc.idCogumelo,
            c.nomeCogumelo,
            fc.descricaoFaseCultivo,
            fc.temperaturaMin,
            fc.temperaturaMax,
            fc.umidadeMin,
            fc.umidadeMax,
            fc.co2Max
        FROM fase_cultivo fc
        JOIN cogumelo c ON c.idCogumelo = fc.idCogumelo
        ORDER BY c.nomeCogumelo, fc.nomeFaseCultivo';

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectId($idFaseCultivo)
    {
        $sql =
            'SELECT 
            fc.idFaseCultivo,
            fc.nomeFaseCultivo,
            fc.idCogumelo,
            c.nomeCogumelo,
            fc.descricaoFaseCultivo,
            fc.temperaturaMin,
            fc.temperaturaMax,
            fc.umidadeMin,
            fc.umidadeMax,
            fc.co2Max
        FROM fase_cultivo fc
        JOIN cogumelo c ON c.idCogumelo = fc.idCogumelo
        WHERE fc.idFaseCultivo = ?';

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idFaseCultivo]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function selectByCogumelo($idCogumelo)
    {
        $sql =
            'SELECT 
            fc.idFaseCultivo,
            fc.nomeFaseCultivo,
            fc.idCogumelo,
            c.nomeCogumelo,
            fc.descricaoFaseCultivo,
            fc.temperaturaMin,
            fc.temperaturaMax,
            fc.umidadeMin,
            fc.umidadeMax,
            fc.co2Max
        FROM fase_cultivo fc
        JOIN cogumelo c ON c.idCogumelo = fc.idCogumelo
        WHERE fc.idCogumelo = ?
        ORDER BY nomeFaseCultivo';

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idCogumelo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($nome, $idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2Max)
    {
        $sql = 'INSERT INTO fase_cultivo
                    (nomeFaseCultivo, 
                    idCogumelo, 
                    descricaoFaseCultivo, 
                    temperaturaMin, 
                    temperaturaMax, 
                    umidadeMin, 
                    umidadeMax, 
                    co2Max)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nome, $idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2Max]);
        return $stmt->rowCount() > 0;
    }

    public function update($idFaseCultivo, $nome, $idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2Max)
    {
        $sql = 'UPDATE fase_cultivo
                    SET nomeFaseCultivo = ?, 
                        idCogumelo = ?, 
                        descricaoFaseCultivo = ?, 
                        temperaturaMin = ?, 
                        temperaturaMax = ?, 
                        umidadeMin = ?, 
                        umidadeMax = ?, 
                        co2Max = ?
                WHERE idFaseCultivo = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nome, $idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2Max, $idFaseCultivo]);
        return $stmt->rowCount() > 0;
    }

    public function delete($idFaseCultivo)
    {
        $sql = 'DELETE FROM fase_cultivo
                WHERE idFaseCultivo = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idFaseCultivo]);
        return ($stmt->rowCount() > 0);
    }
}
