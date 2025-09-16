<?php

// TODO: Refatorar:
/*
Tarefas:
Ajustar Querry da ControleAtuadorModel e retirar informaçoes desnecessárias.
Criar um select por idcontrole com a ultima informação adicionada a tabela.
 - como precisamos do istórico de alteraçoes o front receberá sempre o ultimo dado com a informação de ativo ou inativo.
 
*/

require_once './model/ControleAtuadorModel.php';
require_once './model/AtuadorModel.php';
require_once './model/LoteModel.php';
// require_once './model/UsuarioModel.php'; // Assumindo que não há UsuarioModel no contexto fornecido

class ControleAtuadorController
{
    private $model;
    private $atuadorModel;
    private $loteModel;
    // private $usuarioModel; // Assumindo que não há UsuarioModel no contexto fornecido

    public function __construct()
    {
        $this->model = new ControleAtuadorModel();
        $this->atuadorModel = new AtuadorModel();
        $this->loteModel = new LoteModel();
        // $this->usuarioModel = new UsuarioModel(); // Assumindo que não há UsuarioModel no contexto fornecido
    }

    public function listarTodos(Request $request, Response $response, array $url)
    {
        $dados = $this->model->selectAll();
        return $response->json($dados, 200);
    }

    public function listarIdControle(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /controleAtuador/listarIdControle/{idControle}'], 400);
        }

        $id = (int)$url[0];
        $row = $this->model->selectByIdControle($id);
        if ($row === null) {
            return $response->json(['message' => 'Controle de Atuador não encontrado'], 404);
        }

        return $response->json($row, 200);
    }

    public function listarIdAtuador(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /controleAtuador/listarIdAtuador/{idAtuador}'], 400);
        }

        $idAtuador = (int)$url[0];
        $dados = $this->model->selectByIdAtuador($idAtuador);

        if (empty($dados)) {
            return $response->json(['message' => 'Nenhum controle de atuador encontrado para este atuador'], 200);
        }
        return $response->json($dados, 200);
    }

    public function listarIdLote(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /controleAtuador/listarIdLote/{idLote}'], 400);
        }

        $idLote = (int)$url[0];
        $dados = $this->model->selectByIdLote($idLote);

        if (empty($dados)) {
            return $response->json(['message' => 'Nenhum controle de atuador encontrado para este lote'], 200);
        }
        return $response->json($dados, 200);
    }

    public function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idAtuador     = $data['idAtuador']     ?? null;
        $idLote        = $data['idLote']        ?? null;
        $statusAtuador = $data['statusAtuador'] ?? 'ativo'; // Default para 'ativo' conforme o schema SQL

        // Validação básica
        if (
            !is_numeric($idAtuador) || (int)$idAtuador <= 0 ||
            !is_numeric($idLote)    || (int)$idLote    <= 0
        ) {
            return $response->json(['message' => 'Campos obrigatórios inválidos: idAtuador, idLote'], 400);
        }

        // Valida statusAtuador
        $statusAtuador = strtolower(trim($statusAtuador));
        if (!self::validarStatusAtuador($statusAtuador)) {
            return $response->json(['message' => "Status do atuador inválido. Use 'ativo' ou 'inativo'"], 400);
        }

        // Validação de FK: Atuador
        if ($this->atuadorModel->selectIdAtuador((int)$idAtuador) === null) {
            return $response->json(['message' => 'Atuador não encontrado'], 404);
        }

        // Validação de FK: Lote
        $lote = $this->loteModel->selectId((int)$idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        // Regra de negócio: Não permitir adicionar controle a um lote finalizado
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido adicionar controle de atuador'], 409);
        }

        $novoId = $this->model->create(
            (int)$idAtuador,
            (int)$idLote,
            $statusAtuador
        );

        if ($novoId > 0) {
            $created = $this->model->selectByIdControle($novoId);
            return $response->json([
                'message'   => 'Controle de Atuador adicionado com sucesso',
                'idControle' => $novoId,
                'controle'  => $created
            ], 201);
        }

        return $response->json(['message' => 'Erro ao adicionar controle de atuador'], 500);
    }

// public function alterar(Request $request, Response $response, array $url) 
// {
//     // $request->body() já retorna array, então não precisa de json_decode
//     $data = $request->body();

//     if (empty($data) || !is_array($data)) {
//         return $response->json(['message' => 'Body não recebido ou inválido'], 400);
//     }

//     $idAtuador     = $data['idAtuador'] ?? null;
//     $statusAtuador = isset($data['statusAtuador']) ? strtolower(trim($data['statusAtuador'])) : null;

//     if (!is_numeric($idAtuador) || (int)$idAtuador <= 0 || !self::validarStatusAtuador($statusAtuador)) {
//         return $response->json([
//             'message' => 'Parâmetros inválidos: idAtuador e statusAtuador (ativo|inativo) são obrigatórios'
//         ], 400);
//     }

//     $idAtuador = (int)$idAtuador;

//     if ($this->atuadorModel->selectIdAtuador($idAtuador) === null) {
//         return $response->json(['message' => 'Atuador não encontrado'], 404);
//     }

//     $controles = $this->model->selectByIdAtuador($idAtuador);
//     if (empty($controles)) {
//         return $response->json(['message' => 'Controle de Atuador não encontrado para este atuador'], 404);
//     }

//     $ok = $this->model->updateStatusByAtuador($idAtuador, $statusAtuador);

//     if ($ok) {
//         $atualizados = $this->model->selectByIdAtuador($idAtuador);
//         return $response->json([
//             'message' => 'Status do atuador atualizado',
//             'controles' => $atualizados
//         ], 200);
//     }

//     return $response->json(['message' => 'Erro ao atualizar status do atuador'], 500);
// }

public function alterar(Request $request, Response $response, array $url) 
{
    // Lê o JSON cru do POST
    $json = file_get_contents('php://input');

    if (empty($json)) {
        return $response->json(['message' => 'Body não recebido'], 400);
    }

    $data = json_decode($json, true); // true retorna array associativo

    if (empty($data)) {
        return $response->json(['message' => 'Body não recebido ou JSON inválido'], 400);
    }

    $idAtuador     = $data['idAtuador'] ?? null;
    $statusAtuador = isset($data['statusAtuador']) ? strtolower(trim($data['statusAtuador'])) : null;

    if (!is_numeric($idAtuador) || (int)$idAtuador <= 0 || !self::validarStatusAtuador($statusAtuador)) {
        return $response->json(['message' => 'Parâmetros inválidos: idAtuador e statusAtuador (ativo|inativo) são obrigatórios'], 400);
    }

    // Verifica se o atuador existe
    if ($this->atuadorModel->selectIdAtuador((int)$idAtuador) === null) {
        return $response->json(['message' => 'Atuador não encontrado'], 404);
    }

    // Verifica se há pelo menos um controle para esse atuador
    $controles = $this->model->selectByIdAtuador((int)$idAtuador);
    if (empty($controles)) {
        return $response->json(['message' => 'Controle de Atuador não encontrado para este atuador'], 404);
    }

    // Atualiza o status
    $ok = $this->model->updateStatusByAtuador((int)$idAtuador, $statusAtuador);

    if ($ok) {
        $atualizados = $this->model->selectByIdAtuador((int)$idAtuador);
        return $response->json(['message' => 'Status do atuador atualizado', 'controles' => $atualizados], 200);
    }

    return $response->json(['message' => 'Erro ao atualizar status do atuador'], 500);
}





    /**
     * Realiza a exclusão LÓGICA de um controle de atuador, setando seu status para 'inativo'.
     */
    public function deletarLogico(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /controleAtuador/deletar/{idControle} ou /deletarLogico/{idControle}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $existente = $this->model->selectByIdControle($id);
        if ($existente === null) {
            return $response->json(['message' => 'Controle de Atuador não encontrado'], 404);
        }

        // Regra de negócio: Não permitir deletar controle se o lote associado estiver finalizado
        $lote = $this->loteModel->selectId((int)$existente['idLote']);
        if ($lote === null) {
            // Isso idealmente não deveria acontecer se as FKs forem impostas, mas é bom para robustez
            return $response->json(['message' => 'Lote relacionado ao controle não encontrado'], 404);
        }
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido inativar controle de atuador'], 409);
        }

        $ok = $this->model->deleteLogico($id);
        if ($ok) {
            return $response->json(['message' => 'Controle de Atuador inativado com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao inativar controle de atuador (pode já estar inativo)'], 500);
    }

    /**
     * Realiza a exclusão FÍSICA de um controle de atuador. Use com cuidado.
     */
    public function deletarFisico(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /controleAtuador/deletarFisico/{idControle}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $existente = $this->model->selectByIdControle($id);
        if ($existente === null) {
            return $response->json(['message' => 'Controle de Atuador não encontrado'], 404);
        }

        // Regra de negócio: Não permitir deletar controle se o lote associado estiver finalizado
        $lote = $this->loteModel->selectId((int)$existente['idLote']);
        if ($lote !== null) { // Apenas checa se o lote ainda existe
            $statusLote = strtolower(trim($lote['status'] ?? ''));
            $dataFim    = $lote['dataFim'] ?? null;
            if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
                return $response->json(['message' => 'Lote finalizado: não é permitido excluir permanentemente o controle de atuador'], 409);
            }
        }

        $ok = $this->model->deleteFisico($id);
        if ($ok) {
            return $response->json(['message' => 'Controle de Atuador excluído permanentemente com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao excluir permanentemente o controle de atuador'], 500);
    }

    /* ===================== helpers ===================== */

private static function validarStatusAtuador(?string $status): bool
{
    if (!is_string($status) || $status === '') {
        return false;
    }
    return in_array($status, ['ativo', 'inativo'], true);
}
}
