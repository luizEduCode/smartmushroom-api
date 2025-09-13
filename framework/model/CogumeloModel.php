<?php

require_once './core/Conexao.php';

class CogumeloModel
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::getInstance();
    }

    public function selectAll()
    {
        $sql = 'SELECT * FROM cogumelo';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectId($idCogumelo)
    {
        $sql = 'SELECT * FROM cogumelo WHERE idCogumelo = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idCogumelo]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create($nomeCogumelo, $descricao)
    {
        $sql = 'INSERT INTO cogumelo (nomeCogumelo, descricao) VALUES (?, ?)';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nomeCogumelo, $descricao]);

        return ($stmt->rowCount() > 0);
    }

    public function update($idCogumelo, $nomeCogumelo, $descricao)
    {
        $sql = 'UPDATE cogumelo SET nomeCogumelo = ?, descricao = ? WHERE idCogumelo = ?';
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$nomeCogumelo, $descricao, $idCogumelo]);

        return ($stmt->rowCount() > 0);
    }

    public function delete($idCogumelo)
    {
        $sql = "DELETE FROM cogumelo WHERE idCogumelo = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([$idCogumelo]);
        return ($stmt->rowCount() > 0);
    }
}
