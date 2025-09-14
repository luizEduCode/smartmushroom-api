<?php

$pdo = new PDO(
    'mysql:host=localhost;dbname=smartmushroom_db', // Altere conforme necessário
    'root',
    ''
);

header('Content-Type: application/json'); // Defina o cabeçalho para indicar que a resposta é JSON

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $idSala = (isset($_GET['idSala'])) ? $_GET['idSala'] : '';

    if (empty($idSala)) {
        echo json_encode(['mensagem' => 'ID da sala não informado'], JSON_UNESCAPED_UNICODE);
        http_response_code(400); // Bad Request
        exit;
    }

    $idAtuador = (isset($_GET['idAtuador'])) ? $_GET['idAtuador'] : '';

    if (empty($idAtuador)) {
        echo json_encode(['mensagem' => 'ID da sala não informado'], JSON_UNESCAPED_UNICODE);
        http_response_code(400); // Bad Request
        exit;
    }

    $sql = 'SELECT
                a.idAtuador,
                a.tipoAtuador,
                ca.statusAtuador,
                a.dataCriacao
            FROM
                atuador a
            JOIN
                controle_atuador ca ON a.idAtuador = ca.idAtuador
            WHERE
                a.idSala = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idSala]);
    $resultados = $stm->fetchAll(PDO::FETCH_ASSOC); // Busca todos os resultados

    echo json_encode(['atuadores' => $resultados], JSON_UNESCAPED_UNICODE);
    http_response_code(200);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parse_str(file_get_contents('php://input') ?? '', $_POST);

    $idAtuador     = $_POST['idAtuador']     ?? '';
    $statusAtuador = $_POST['statusAtuador'] ?? '';

    if (empty($idAtuador) || !in_array($statusAtuador, ['ativo', 'inativo'], true)) {
        echo json_encode(['mensagem' => 'Parâmetros inválidos'], JSON_UNESCAPED_UNICODE);
        http_response_code(400);
        exit;
    }

    // Verifica se o atuador existe
    $sql = 'SELECT 1 FROM controle_atuador WHERE idAtuador = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idAtuador]);
    if (!$stm->fetch()) {
        echo json_encode(['mensagem' => 'Atuador não encontrado'], JSON_UNESCAPED_UNICODE);
        http_response_code(404);
        exit;
    }

    // Atualiza o status exatamente como veio no POST
    $sql = 'UPDATE controle_atuador SET statusAtuador = ? WHERE idAtuador = ?';
    $stm = $pdo->prepare($sql);
    if ($stm->execute([$statusAtuador, $idAtuador])) {
        echo json_encode(['mensagem' => 'Status do atuador atualizado'], JSON_UNESCAPED_UNICODE);
        http_response_code(200);
    } else {
        echo json_encode(['mensagem' => 'Erro ao atualizar status'], JSON_UNESCAPED_UNICODE);
        http_response_code(500);
    }
}
