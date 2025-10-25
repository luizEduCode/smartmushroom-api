<?php
require_once './model/LeituraModel.php';
require_once './model/LoteModel.php';

class LeituraController
{
    private $model;
    private $loteModel;

    public function __construct()
    {
        $this->model     = new LeituraModel();
        $this->loteModel = new LoteModel();
    }

    public function listarTodos(Request $request, Response $response, array $url)
    {
        $dados = $this->model->selectAll();
        return $response->json($dados, 200);
    }

    public function listarIdLeitura(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /leitura/listarIdLeitura/{idLeitura}'], 400);
        }

        $id   = (int)$url[0];
        $dado = $this->model->selectByIdLeitura($id);
        if ($dado === null) {
            return $response->json(['message' => 'Leitura não encontrada'], 404);
        }
        return $response->json($dado, 200);
    }

    public function listarIdLote(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /leitura/listarIdLote/{idLote}'], 400);
        }

        $idLote = (int)$url[0];
        $dados  = $this->model->selectByIdLote($idLote);
        if (empty($dados)) {
            return $response->json(['message' => 'Sem leituras para este lote'], 200);
        }
        return $response->json($dados, 200);
    }

    public function listarUltimaLeitura(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /leitura/listarIdLote/{idLote}'], 400);
        }

        $idLote = (int)$url[0];
        $dados  = $this->model->selectLastByIdLote($idLote);
        if (empty($dados)) {
            return $response->json(['message' => 'Sem leituras para este lote'], 200);
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
        $umidade = $data['umidade'] ?? null;
        $temperatura = $data['temperatura'] ?? null;
        $co2 = $data['co2'] ?? null;
        $luz = $data['luz'] ?? 'ligado';

        if (
            !is_numeric($idLote) || (int)$idLote <= 0
            || !is_numeric($umidade)
            || !is_numeric($temperatura)
            || !is_numeric($co2)
            || !self::validarLuz($luz)
        ) {
            return $response->json(['message' => 'Campos inválidos: idLote, umidade, temperatura, co2 e luz'], 400);
        }

        // Lote existente?
        $lote = $this->loteModel->selectId((int)$idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        // Bloqueio: não permitir leitura em lote finalizado
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido adicionar leitura'], 409);
        }

        $novoId = $this->model->create(
            (int)$idLote,
            (float)$umidade,
            (float)$temperatura,
            (float)$co2,
            strtolower($luz)
        );

        if ($novoId > 0) {
            $created = $this->model->selectByIdLeitura($novoId);
            return $response->json([
                'message'   => 'Leitura adicionada com sucesso',
                'idLeitura' => $novoId,
                'leitura'   => $created
            ], 201);
        }

        return $response->json(['message' => 'Erro ao adicionar leitura'], 500);
    }

    public function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /leitura/deletar/{idLeitura}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $existente = $this->model->selectByIdLeitura($id);
        if ($existente === null) {
            return $response->json(['message' => 'Leitura não encontrada'], 404);
        }

        // Bloqueio: não permitir exclusão se o lote estiver finalizado
        $lote = $this->loteModel->selectId((int)$existente['idLote']);
        if ($lote === null) {
            return $response->json(['message' => 'Lote relacionado não encontrado'], 404);
        }
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido excluir leitura'], 409);
        }

        $ok = $this->model->delete($id);
        if ($ok) {
            return $response->json(['message' => 'Leitura deletada com sucesso'], 200);
        }
        return $response->json(['message' => 'Erro ao deletar leitura'], 500);
    }


    private static function validarLuz(?string $luz): bool
    {
        if ($luz === null) return false;
        $l = strtolower(trim($luz));
        return in_array($l, ['ligado', 'desligado'], true);
    }
}
