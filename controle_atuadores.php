<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartmushroom_db', 'root', '');

$idSala = isset($_GET['idSala']) ? $_GET['idSala'] : '';

$sql = "SELECT a.nomeAtuador, a.tipoAtuador, ca.statusAtuador
        FROM atuador a
        JOIN (
            SELECT idAtuador, MAX(dataCriacao) AS dataCriacao
            FROM controle_atuador
            GROUP BY idAtuador
        ) ult ON a.idAtuador = ult.idAtuador
        JOIN controle_atuador ca ON ca.idAtuador = ult.idAtuador AND ca.dataCriacao = ult.dataCriacao
        WHERE a.idSala = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idSala]);

$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['atuadores' => $resultado]);




if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parse_str(file_get_contents('php://input') ?? '', $_POST);
    
    $idAtuador = $_POST['idAtuador'] ?? '';
    $statusAtuador = $_POST['statusAtuador'] ?? '';

    if (empty($idAtuador)) {
        echo json_encode(['mensagem' => 'ID do atuador não informado']);
        http_response_code(400);
        exit;
    }

    // Obter o status atual do atuador
    $sql = 'SELECT statusAtuador FROM controle_atuador WHERE idAtuador = ?';
    $stm = $pdo->prepare($sql);
    $stm->execute([$idAtuador]);
    $atuadorAtual = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$atuadorAtual) {
        echo json_encode(['mensagem' => 'Atuador não encontrado']);
        http_response_code(404);
        exit;
    }

    // Alternar o status: se for 'ativo', muda para 'inativo', e se for 'inativo', muda para 'ativo'
    $novoStatus = ($atuadorAtual['statusAtuador'] == 'ativo') ? 'inativo' : 'ativo';

    // Atualizar o status do atuador
    $sql = 'UPDATE controle_atuador SET statusAtuador = ? WHERE idAtuador = ?';
    $stm = $pdo->prepare($sql);
    $sucesso = $stm->execute([$novoStatus, $idAtuador]);

    if ($sucesso) {
        echo json_encode(['mensagem' => 'Status do atuador atualizado com sucesso']);
        http_response_code(200); // OK
    } else {
        echo json_encode(['mensagem' => 'Erro ao atualizar status do atuador']);
        http_response_code(500); // Erro Interno
    }
} else {
    echo json_encode(['mensagem' => 'Método HTTP inválido. Use POST']);
    http_response_code(405); // Método Não Permitido
}

?>
