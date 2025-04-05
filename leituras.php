<?php


$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    //echo 'Request via get';
    $idLote = (isset($_GET['idLote']))?$_GET['idLote']:'';

    $sql = 'SELECT * FROM leitura WHERE idLote = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idLote]);

    $idLote = $stm->fetchALL(PDO::FETCH_OBJ);

    echo json_encode(['leitura' => $idLote]);
    http_response_code(200);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST'){

    $idLote = (isset($_POST['idLote']))?$_POST['idLote']:'';
    $umidade = (isset($_POST['umidade']))?$_POST['umidade']:'';
    $temperatura = (isset($_POST['temperatura']))?$_POST['temperatura']:'';
    $co2 = (isset($_POST['co2']))?$_POST['co2']:'';
    $luz = (isset($_POST['luz']))?$_POST['luz']:'';

    $sql = 'INSERT INTO leitura (idLote, umidade, temperatura, co2, luz) VALUES (?, ?, ?, ?, ?)';
    $stm = $pdo->prepare($sql);
    $sucesso = $stm->execute([$idLote, $umidade, $temperatura, $co2, $luz]);

    if ($sucesso){
        echo json_encode(['mensagem'=>'Inserido com sucesso']);
        http_response_code(201);
    } else {
        echo json_encode(['mensagem'=>'Falha ao inserir']);
        http_response_code(500);
    };

};

if ($_SERVER['REQUEST_METHOD'] == 'PUT'){

};

if ($_SERVER['REQUEST_METHOD'] == 'DELETE'){

};