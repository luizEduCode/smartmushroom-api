<?php

$pdo = new PDO('mysql:host=localhost:3306;dbname=smartmushroom_db;port=3308', 'root', '');

// Método GET - Listar salas e lotes
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $action = $_GET['action'] ?? '';

        // Listar salas disponíveis
        if ($action == 'salas-disponiveis') {
            // 1. Busca os IDs das salas que têm lotes ATIVOS
            $sqlAtivos = "SELECT DISTINCT idSala FROM lote WHERE status = 'ativo'";
            $stmtAtivos = $pdo->query($sqlAtivos);
            $salasOcupadas = $stmtAtivos->fetchAll(PDO::FETCH_COLUMN);

            // 2. Busca todas as salas que NÃO estão na lista de ocupadas
            $sql = "SELECT idSala, nomeSala FROM sala";

            if (!empty($salasOcupadas)) {
                $placeholders = implode(',', array_fill(0, count($salasOcupadas), '?'));
                $sql .= " WHERE idSala NOT IN ($placeholders)";
            }

            $sql .= " ORDER BY nomeSala";

            $stmt = $pdo->prepare($sql);

            if (!empty($salasOcupadas)) {
                foreach ($salasOcupadas as $index => $idSala) {
                    $stmt->bindValue($index + 1, $idSala, PDO::PARAM_INT);
                }
            }

            $stmt->execute();
            $salasDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'salas_disponiveis' => $salasDisponiveis,
                    'salas_ocupadas' => $salasOcupadas
                ]
            ]);
        }
        // Listar informações completas de uma sala
        elseif ($action == 'info-sala') {
            $idSala = $_GET['idSala'] ?? '';

            $sql = 'SELECT 
                s.nomeSala, 
                l.idLote, 
                l.dataInicio, 
                l.status, 
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
            WHERE s.idSala = ? AND l.status = "ativo"
            ORDER BY s.nomeSala DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idSala]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        }

        // Listar tipos de cogumelos
        elseif ($action == 'tipos-cogumelos') {
            $stmt = $pdo->query("SELECT idCogumelo, nomeCogumelo FROM cogumelo");
            $cogumelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $cogumelos
            ]);
        }
        // Listar fases de cultivo
        elseif ($action == 'fases-cultivo') {
            $stmt = $pdo->query("SELECT idFaseCultivo, nomeFaseCultivo FROM fase_cultivo");
            $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $fases
            ]);
        } else {
            throw new Exception("Ação não especificada ou inválida");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


// Método POST - Criar novo lote
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception("Dados inválidos");
        }

        // Verifica se a sala está disponível
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lote WHERE idSala = ? AND status = 'ativo'");
        $stmt->execute([$input['idSala']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Sala já possui um lote ativo");
        }

        // Inicia transação
        $pdo->beginTransaction();

        // Insere o lote
        $stmt = $pdo->prepare("INSERT INTO lote (idSala, idCogumelo, dataInicio, status) VALUES (?, ?, NOW(), 'ativo')");
        $stmt->execute([$input['idSala'], $input['idCogumelo']]);
        $idLote = $pdo->lastInsertId();

        // Insere a fase inicial
        $stmt = $pdo->prepare("INSERT INTO historico_fase (idLote, idFaseCultivo, dataMudanca) VALUES (?, ?, NOW())");
        $stmt->execute([$idLote, $input['idFase']]);

        // Insere leitura inicial
        $stmt = $pdo->prepare("INSERT INTO leitura (idLote, temperatura, umidade, co2, luz, dataCriacao) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$idLote, $input['temperatura'] ?? 0, $input['umidade'] ?? 0, $input['co2'] ?? 0, $input['luz'] ?? 0]);

        // Insere valores
        $stmt = $pdo->prepare("INSERT INTO configuracao (idLote, temperaturaMin, temperaturaMax, umidadeMin, umidadeMax, co2Max) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$idLote, 22, 26, 65, 75, 1500]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'idLote' => $idLote,
            'message' => 'Lote criado com sucesso'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar lote: ' . $e->getMessage()
        ]);
    }
}

// Método DELETE - Alternativa para finalizar lote
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $idLote = $_GET['idLote'] ?? '';

        if (empty($idLote)) {
            throw new Exception("ID do lote não fornecido");
        }

        $stmt = $pdo->prepare("UPDATE lote SET status = 'finalizado', dataFim = NOW() WHERE idLote = ?");
        $stmt->execute([$idLote]);

        echo json_encode([
            'success' => true,
            'message' => 'Lote finalizado com sucesso'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao finalizar lote: ' . $e->getMessage()
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    try {
        $idLote = $_GET['idLote'] ?? '';

        if (empty($idLote)) {
            throw new Exception("ID do lote não fornecido");
        }

        $stmt = $pdo->prepare("DELETE from lote WHERE idLote = ?");
        $stmt->execute([$idLote]);

        echo json_encode([
            'success' => true,
            'message' => 'Lote excluido com sucesso'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao excluir lote: ' . $e->getMessage()
        ]);
    }
}
