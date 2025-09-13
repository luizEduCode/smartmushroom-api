<?php

//TODO: Adicionar iluminação ao historico_fase
/* 
Para isso será necessário alterar o banco de dados, adicionando uma nova coluna de iluminação fase_cultivo e refatorar as models de historico_fase e fase_cultivo
*/
require_once './model/HistoricoFaseModel.php';
require_once './model/LoteModel.php';
require_once './model/FaseCultivoModel.php';

class HistoricoFaseController
{
    private $model;
    private $loteModel;
    private $faseModel;

    public function __construct()
    {
        $this->model = new HistoricoFaseModel();
        $this->loteModel = new LoteModel();
        $this->faseModel = new FaseCultivoModel();
    }

    public function listarTodos(Request $request, Response $response, array $url)
    {
        $dados = $this->model->selectAll();
        return $response->json($dados, 200);
    }

    public function listarIdHistorico(Request $request, Response $response, array $url) 
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /historico_fase/listarIdHistorico/{idHistorico}'], 400);
        }

        $id = (int)$url[0];
        $row = $this->model->selectByIdHistorico($id);
        if (!$row || (is_array($row) && count($row) === 0)) {
            return $response->json(['message' => 'Histórico não encontrado'], 404);
        }

        return $response->json($row, 200);
    }

    public function listarIdLote(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /historico_fase/listarIdLote/{idLote}'], 400);
        }

        $idLote = (int)$url[0];
        $dados = $this->model->selectByIdLote($idLote);
        if (!$dados || count($dados) === 0) {
            return $response->json(['message' => 'Sem histórico para este lote'], 200);
        }
        return $response->json($dados, 200);
    }

    public function listarIdFase(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /historico_fase/listarIdFase/{idFase}'], 400);
        }

        $idFase = (int)$url[0];
        $dados = $this->model->selectAtivosByIdFase($idFase);

        if (empty($dados)) {
            return $response->json(['message' => 'Sem histórico para esta fase'], 200);
        }

        return $response->json($dados, 200);
    }


    public function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idLote = $data['idLote'] ?? null;
        $idFaseCultivo = $data['idFaseCultivo'] ?? null;

        if (!is_numeric($idLote) || (int)$idLote <= 0 || !is_numeric($idFaseCultivo) || (int)$idFaseCultivo <= 0) {
            return $response->json(['message' => 'Campos obrigatórios inválidos: idLote, idFaseCultivo'], 400);
        }

        // FKs
        $lote = $this->loteModel->selectId((int)$idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        $fase = $this->faseModel->selectId((int)$idFaseCultivo);
        if ($fase === null) {
            return $response->json(['message' => 'Fase de cultivo não encontrada'], 404);
        }

        // Bloqueio: não permitir histórico em lote finalizado
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido adicionar novo histórico'], 409);
        }

        // Compatibilidade de cogumelo entre lote e fase
        $idCogLote = (int)($lote['idCogumelo'] ?? 0);
        $idCogFase = (int)($fase['idCogumelo'] ?? 0);
        if ($idCogLote !== $idCogFase) {
            return $response->json([
                'message' => 'Incompatibilidade: o cogumelo do lote é diferente do cogumelo da fase',
                'detalhes' => [
                    'idLote' => (int)$idLote,
                    'idFaseCultivo' => (int)$idFaseCultivo,
                    'idCogumeloLote' => $idCogLote,
                    'idCogumeloFase' => $idCogFase,
                ]
            ], 409);
        }

        // Criar histórico
        $novoId = $this->model->create((int)$idLote, (int)$idFaseCultivo);
        if ($novoId > 0) {
            $created = $this->model->selectByIdHistorico((int)$novoId);
            return $response->json([
                'message' => 'Histórico adicionado com sucesso',
                'idHistorico' => $novoId,
                'historico' => $created
            ], 201);
        }

        return $response->json(['message' => 'Erro ao adicionar histórico'], 500);
    }

    public function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idHistorico = (int)($data['idHistorico'] ?? 0);
        $idLote = $data['idLote'] ?? null;
        $idFaseCultivo = $data['idFaseCultivo'] ?? null;

        if ($idHistorico <= 0) {
            return $response->json(['message' => 'idHistorico inválido'], 400);
        }
        if (!is_numeric($idLote) || (int)$idLote <= 0 || !is_numeric($idFaseCultivo) || (int)$idFaseCultivo <= 0) {
            return $response->json(['message' => 'Campos obrigatórios ausentes ou inválidos (idLote, idFaseCultivo)'], 400);
        }

        // existe o histórico?
        $existente = $this->model->selectByIdHistorico($idHistorico);
        if ($existente === null) {
            return $response->json(['message' => 'Histórico não encontrado'], 404);
        }

        // valida FKs
        $lote = $this->loteModel->selectId((int)$idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }
        $fase = $this->faseModel->selectId((int)$idFaseCultivo);
        if ($fase === null) {
            return $response->json(['message' => 'Fase de cultivo não encontrada'], 404);
        }

        // Bloqueio: não permitir histórico em lote finalizado
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido alterar histórico para este lote'], 409);
        }

        // Compatibilidade de cogumelo entre lote e fase
        $idCogLote = (int)($lote['idCogumelo'] ?? 0);
        $idCogFase = (int)($fase['idCogumelo'] ?? 0);
        if ($idCogLote !== $idCogFase) {
            return $response->json([
                'message'  => 'Incompatibilidade: o cogumelo do lote é diferente do cogumelo da fase',
                'detalhes' => [
                    'idHistorico' => $idHistorico,
                    'idLote' => (int)$idLote,
                    'idFaseCultivo' => (int)$idFaseCultivo,
                    'idCogumeloLote' => $idCogLote,
                    'idCogumeloFase' => $idCogFase,
                ]
            ], 409);
        }

        // evitar "no-op" (opcional)
        if ((int)$existente['idLote'] === (int)$idLote && (int)$existente['idFaseCultivo'] === (int)$idFaseCultivo) {
            return $response->json([
                'message'   => 'Sem alterações',
                'historico' => $existente
            ], 200);
        }

        // atualizar
        $ok = $this->model->update($idHistorico, (int)$idLote, (int)$idFaseCultivo);

        // estado atual
        $atual = $this->model->selectByIdHistorico($idHistorico);

        return $response->json([
            'message'   => $ok ? 'Histórico atualizado' : 'Sem alterações',
            'historico' => $atual
        ], 200);
    }

    public function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /historico_fase/{idHistorico}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        // existe o histórico?
        $hist = $this->model->selectByIdHistorico($id);
        if ($hist === null) {
            return $response->json(['message' => 'Histórico não encontrado'], 404);
        }

        // bloqueio: não permitir excluir histórico se o lote estiver finalizado
        $lote = $this->loteModel->selectId((int)$hist['idLote']);
        if ($lote === null) {
            return $response->json(['message' => 'Lote relacionado ao histórico não encontrado'], 404);
        }
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido excluir histórico'], 409);
        }

        // executa exclusão
        $ok = $this->model->delete($id);
        if ($ok) {
            return $response->json(['message' => 'Histórico deletado com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao deletar histórico'], 500);
    }
}
