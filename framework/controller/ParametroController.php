<?php
require_once './model/ParametroModel.php';
require_once './model/LoteModel.php';

class ParametroController
{
    private $model;
    private $loteModel;

    public function __construct()
    {
        $this->model = new ParametroModel();
        $this->loteModel = new LoteModel();
    }

    public function listarTodos(Request $request, Response $response, array $url)
    {
        $dados = $this->model->selectAll();
        return $response->json($dados, 200);
    }

    public function listarIdParametro(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /parametros/listarIdParametro/{idParametro}'], 400);
        }
        $id = (int)$url[0];
        $dado = $this->model->selectByIdParametro($id);
        if ($dado === null) {
            return $response->json(['message' => 'Parâmetro não encontrado'], 404);
        }
        return $response->json($dado, 200);
    }

    public function listarIdLote(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /parametros/listarIdLote/{idLote}'], 400);
        }
        $idLote = (int)$url[0];
        $dados = $this->model->selectByIdLote($idLote);

        if (empty($dados)) {
            return $response->json(['message' => 'Sem parâmetros cadastrados para este lote'], 200);
        }

        // Caso queira apenas a configuração mais recente:
        // $maisRecente = $dados[0];
        // return $response->json($maisRecente, 200);

        return $response->json($dados, 200);
    }

    // public function adicionar(Request $request, Response $response, array $url)
    // {
    //     $data = $request->body();
    //     if (empty($data)) {
    //         return $response->json(['message' => 'Body não recebido'], 400);
    //     }

    //     $idLote = $data['idLote'] ?? null;
    //     $uMin = $data['umidadeMin'] ?? null;
    //     $uMax = $data['umidadeMax'] ?? null;
    //     $tMin = $data['temperaturaMin'] ?? null;
    //     $tMax = $data['temperaturaMax'] ?? null;
    //     $co2Max = $data['co2Max'] ?? null;
    //     $luz = $data['luz'] ?? 'ligado';


    //     if (
    //         !is_numeric($idLote) || (int)$idLote <= 0 ||
    //         !is_numeric($uMin) || !is_numeric($uMax) ||
    //         !is_numeric($tMin) || !is_numeric($tMax) ||
    //         !is_numeric($co2Max)
    //     ) {
    //         return $response->json(['message' => 'Campos inválidos: idLote, umidadeMin/Max, temperaturaMin/Max e co2Max'], 400);
    //     }

    //     // Lote existente?
    //     $lote = $this->loteModel->selectId((int)$idLote);
    //     if ($lote === null) {
    //         return $response->json(['message' => 'Lote não encontrado'], 404);
    //     }

    //     // Bloqueio: não permitir criar parâmetros em lote finalizado
    //     $statusLote = strtolower(trim($lote['status'] ?? ''));
    //     $dataFim    = $lote['dataFim'] ?? null;
    //     if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
    //         return $response->json(['message' => 'Lote finalizado: não é permitido adicionar parâmetros'], 409);
    //     }

    //     $novoId = $this->model->create(
    //         (int)$idLote,
    //         (float)$uMin,
    //         (float)$uMax,
    //         (float)$tMin,
    //         (float)$tMax,
    //         (float)$co2Max,
    //         (string)$luz,
    //     );

    //     if ($novoId > 0) {
    //         $created = $this->model->selectByIdParametro($novoId);
    //         return $response->json([
    //             'message' => 'Parâmetros adicionados com sucesso',
    //             'idParametro' => $novoId,
    //             'parametro' => $created
    //         ], 201);
    //     }

    //     return $response->json(['message' => 'Erro ao adicionar parâmetros'], 500);
    // }

    public function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idLote = $data['idLote'] ?? null;
        $uMin   = $data['umidadeMin'] ?? null;
        $uMax   = $data['umidadeMax'] ?? null;
        $tMin   = $data['temperaturaMin'] ?? null;
        $tMax   = $data['temperaturaMax'] ?? null;
        $co2Max = $data['co2Max'] ?? null;

        // Validação numérica
        if (
            !is_numeric($idLote) || (int)$idLote <= 0 ||
            !is_numeric($uMin) || !is_numeric($uMax) ||
            !is_numeric($tMin) || !is_numeric($tMax) ||
            !is_numeric($co2Max)
        ) {
            return $response->json([
                'message' => 'Campos inválidos: idLote, umidadeMin/Max, temperaturaMin/Max e co2Max'
            ], 400);
        }

        // Lote existente?
        $lote = $this->loteModel->selectId((int)$idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        // Bloqueio: não permitir criar parâmetros em lote finalizado
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json([
                'message' => 'Lote finalizado: não é permitido adicionar parâmetros'
            ], 409);
        }

        // Criação no banco
        $novoId = $this->model->create(
            (int)$idLote,
            (float)$uMin,
            (float)$uMax,
            (float)$tMin,
            (float)$tMax,
            (float)$co2Max
        );

        if ($novoId > 0) {
            $created = $this->model->selectByIdParametro($novoId);
            return $response->json([
                'message'     => 'Parâmetros adicionados com sucesso',
                'idParametro' => $novoId,
                'parametro'   => $created
            ], 201);
        }

        return $response->json(['message' => 'Erro ao adicionar parâmetros'], 500);
    }

    // public function alterar($request, $response, $url)
    // {
    // }

    public function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /parametros/deletar/{idParametro}'], 400);
        }
        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $existente = $this->model->selectByIdParametro($id);
        if ($existente === null) {
            return $response->json(['message' => 'Parâmetro não encontrado'], 404);
        }

        // Bloqueio: não permitir exclusão se o lote estiver finalizado
        $lote = $this->loteModel->selectId((int)$existente['idLote']);
        if ($lote === null) {
            return $response->json(['message' => 'Lote relacionado não encontrado'], 404);
        }
        $statusLote = strtolower(trim($lote['status'] ?? ''));
        $dataFim    = $lote['dataFim'] ?? null;
        if ($statusLote === 'finalizado' || ($dataFim !== null && $dataFim !== '')) {
            return $response->json(['message' => 'Lote finalizado: não é permitido excluir parâmetros'], 409);
        }

        $ok = $this->model->delete($id);
        if ($ok) {
            return $response->json(['message' => 'Parâmetro deletado com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao deletar parâmetro'], 500);
    }


}
