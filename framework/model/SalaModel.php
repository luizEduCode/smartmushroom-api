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
}
