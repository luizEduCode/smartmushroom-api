<?php

$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    $idSala = (isset($_GET['idSala'])) ? $_GET['idSala'] : '';

    $sql = 'Adicionar select dos parâmetros';
    $stm = $pdo->prepare($sql);
    $stm->execute();

    $idSala = $stm->fetchAll(PDO::FETCH_OBJ);

    echo json_encode(['sala' => $idSala]);
    http_response_code(200);
};