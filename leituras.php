<?php


$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    //echo 'Request via get';
    $idLote = (isset($_GET['idLote'])) ? $_GET['idLote'] : '';

    $sql = 'SELECT * FROM leitura WHERE idLote = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idLote]);

    $idLote = $stm->fetchALL(PDO::FETCH_OBJ);

    echo json_encode(['leitura' => $idLote]);
    http_response_code(200);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $idSala = isset($_POST['idSala']) ? $_POST['idSala'] : '';
    $umidade = isset($_POST['umidade']) ? $_POST['umidade'] : '';
    $temperatura = isset($_POST['temperatura']) ? $_POST['temperatura'] : '';
    $co2 = isset($_POST['co2']) ? $_POST['co2'] : '';
    $luz = isset($_POST['luz']) ? $_POST['luz'] : '';

    try {
        // Verifica se há lote ativo na sala
        $stmt = $pdo->prepare("SELECT idLote FROM lote WHERE idSala = ? AND status = 'ativo' LIMIT 1");
        $stmt->execute([$idSala]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lote) {
            throw new Exception("Nenhum lote ativo encontrado para esta sala");
        }

        $idLote = $lote['idLote'];

        // Insere a leitura
        $sql = 'INSERT INTO leitura (idLote, umidade, temperatura, co2, luz) VALUES (?, ?, ?, ?, ?)';
        $stm = $pdo->prepare($sql);
        $sucesso = $stm->execute([$idLote, $umidade, $temperatura, $co2, $luz]);

        if ($sucesso) {
            echo json_encode(['mensagem' => 'Inserido com sucesso']);
            http_response_code(201);
        } else {
            throw new Exception("Falha ao inserir leitura");
        }
    } catch (Exception $e) {
        echo json_encode(['mensagem' => $e->getMessage()]);
        http_response_code(500);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
};

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
};
