<?php

$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    $idSala = (isset($_GET['idSala'])) ? $_GET['idSala'] : '';

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
    WHERE hf.dataMudanca = (
        SELECT MAX(dataMudanca)
        FROM historico_fase
        WHERE idLote = l.idLote
        AND l.status = "ativo"
    )';

    // AND l.status = "ativo" -- faz com que apenas as salas ativas sejam mostradas

    $stm = $pdo->prepare($sql);
    $stm->execute();

    $idSala = $stm->fetchAll(PDO::FETCH_OBJ);

    echo json_encode(['sala' => $idSala]);
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


// Adicionei depois de subir no Drive - 21:43 23/03/2025
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idLote = (isset($_POST['idLote'])) ? $_POST['idLote'] : '';
    $idSala = (isset($_POST['idSala'])) ? $_POST['idSala'] : '';

    // Verifica se a sala já existe
    $sql = 'SELECT COUNT(*) as total FROM sala WHERE idSala = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idSala]);
    $result = $stm->fetch(PDO::FETCH_OBJ);

    if ($result->total > 0) {
        // Atualiza o lote existente
        $sql = 'UPDATE lote SET idSala = ? WHERE idLote = ?';
        $stm = $pdo->prepare($sql);
        $sucesso = $stm->execute([$idSala, $idLote]);

        if ($sucesso) {
            echo json_encode(['mensagem' => 'Lote atualizado com sucesso']);
            http_response_code(200);
        } else {
            echo json_encode(['mensagem' => 'Falha ao atualizar lote']);
            http_response_code(500);
        }
    } else {
        echo json_encode(['mensagem' => 'Sala não encontrada']);
        http_response_code(404);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $idLote = (isset($_GET['idLote'])) ? $_GET['idLote'] : '';

    // Finaliza o lote atual
    $sql = 'UPDATE lote SET status = "finalizado", dataFim = NOW() WHERE idLote = ?';
    $stm = $pdo->prepare($sql);
    $sucesso = $stm->execute([$idLote]);

    if ($sucesso) {
        // Cria um novo lote
        $sql = 'INSERT INTO lote (idSala, idCogumelo, dataInicio, status) VALUES (?, ?, NOW(), "ativo")';
        $stm = $pdo->prepare($sql);
        $sucesso = $stm->execute([$idSala, $idCogumelo]);

        if ($sucesso) {
            echo json_encode(['mensagem' => 'Lote finalizado e novo lote criado com sucesso']);
            http_response_code(200);
        } else {
            echo json_encode(['mensagem' => 'Falha ao criar novo lote']);
            http_response_code(500);
        }
    } else {
        echo json_encode(['mensagem' => 'Falha ao finalizar lote']);
        http_response_code(500);
    }
}
// -------------- Acaba aqui -------------- //

if ($_SERVER['REQUEST_METHOD'] == 'DELETE'){

};