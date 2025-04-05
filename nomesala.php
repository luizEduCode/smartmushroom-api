<?php


$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $nomeSala = (isset($_GET['nomeSala'])) ? $_GET['nomeSala'] : '';
    $idLote = (isset($_GET['idLote'])) ? $_GET['idLote'] : '';

    $sql = 'SELECT 
        s.nomeSala, 
        l.idLote, 
        l.dataInicio, 
        l.status AS status, 
        c.nomeCogumelo, 
        f.nomeFaseCultivo,
        (
            SELECT temperatura FROM leitura 
            WHERE idLote = l.idLote 
            ORDER BY dataCriacao DESC 
            LIMIT 1
        ) AS temperatura,
        (
            SELECT umidade FROM leitura 
            WHERE idLote = l.idLote 
            ORDER BY dataCriacao DESC 
            LIMIT 1
        ) AS umidade,
        (
            SELECT co2 FROM leitura 
            WHERE idLote = l.idLote 
            ORDER BY dataCriacao DESC 
            LIMIT 1
        ) AS co2
    FROM sala s
    JOIN lote l ON l.idSala = s.idSala
    JOIN cogumelo c ON c.idCogumelo = l.idCogumelo
    JOIN historico_fase hf ON hf.idLote = l.idLote
    JOIN fase_cultivo f ON f.idFaseCultivo = hf.idFaseCultivo
    WHERE s.nomeSala = :nomeSala
    AND l.idLote = :idLote
    AND hf.dataMudanca = (
        SELECT MAX(dataMudanca)
        FROM historico_fase
        WHERE idLote = l.idLote
    )';

    $stm = $pdo->prepare($sql);
    $stm->execute([':nomeSala' => $nomeSala, ':idLote' => $idLote]);

    $sala = $stm->fetchAll(PDO::FETCH_OBJ);

    echo json_encode(['sala' => $sala]);
    http_response_code(200);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST'){

};

if ($_SERVER['REQUEST_METHOD'] == 'PUT'){

};

if ($_SERVER['REQUEST_METHOD'] == 'DELETE'){

};