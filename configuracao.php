<?php

$pdo = new PDO('mysql:host=localhost:3306;dbname=smartmushroom_db', 'root', '');

// PUT - Atualizar configurações
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['idLote'])) {
        http_response_code(400);
        echo json_encode(["error" => "Campo 'idLote' obrigatório"]);
        exit;
    }

    // Verificar se o idLote existe na tabela lote
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM lote WHERE idLote = ?");
    $stmtCheck->execute([$data['idLote']]);
    if ($stmtCheck->fetchColumn() == 0) {
        http_response_code(400);
        echo json_encode(["error" => "O idLote informado não existe na tabela 'lote'"]);
        exit;
    }

    try {
        // Inserir nova configuração
        $stmt = $pdo->prepare("
            INSERT INTO configuracao 
            (idLote, temperaturaMin, temperaturaMax, umidadeMin, umidadeMax, co2Max) 
            VALUES (?, ?, ?, ?, ?, ?);
        ");
        $stmt->execute([
            $data['idLote'],
            $data['temperaturaMin'], // Valores padrão
            $data['temperaturaMax'],
            $data['umidadeMin'],
            $data['umidadeMax'],
            $data['co2Max']
        ]);

        //var_dump($data);
        // Se o campo idFase for enviado, atualiza a fase também
        if (!empty($data['idFaseCultivo'])) {
            $stmt = $pdo->prepare("UPDATE historico_fase SET idFaseCultivo = ? WHERE idLote = ?");
            $stmt->execute([$data['idFaseCultivo'], $data['idLote']]);
        }

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao salvar: " . $e->getMessage()]);
    }
    exit;
}
