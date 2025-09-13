<?php

// header("Access-Control-Allow-Origin: *");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: GET, PUT");

// Conexão com tratamento de erros

$pdo = new PDO('mysql:host=localhost:3306;dbname=smartmushroom_db', 'root', '');


// GET - Buscar configurações
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['idLote'])) {
        http_response_code(400);
        echo json_encode(["error" => "Parâmetro 'idLote' obrigatório"]);
        exit;
    }

    $idLote = $_GET['idLote'];

    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.temperaturaMin, 
                c.temperaturaMax, 
                c.umidadeMin, 
                c.umidadeMax, 
                c.co2Max,
                l.idSala
            FROM configuracao c
            JOIN lote l ON c.idLote = l.idLote
            WHERE c.idLote = ? 
            ORDER BY c.idConfig DESC 
            LIMIT 1
        ");
        $stmt->execute([$idLote]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            echo json_encode([
                "configuracao" => $config,
                "idSala" => $config['idSala'] // Inclui o idSala na resposta
            ]);
        } else {
            // Se não encontrar configuração, busca apenas o idSala do lote
            $stmt = $pdo->prepare("
                SELECT idSala FROM lote WHERE idLote = ? LIMIT 1
            ");
            $stmt->execute([$idLote]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lote) {
                http_response_code(404);
                echo json_encode([
                    "error" => "Configuração não encontrada",
                    "idSala" => $lote['idSala']
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Lote não encontrado"]);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erro no servidor: " . $e->getMessage()]);
    }
    exit;
}

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
            $data['temperaturaMin'] ?? 22, // Valores padrão
            $data['temperaturaMax'] ?? 26,
            $data['umidadeMin'] ?? 65,
            $data['umidadeMax'] ?? 75,
            $data['co2Max'] ?? 1500
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


// Método não suportado
http_response_code(405);
echo json_encode(["error" => "Método não permitido"]);
