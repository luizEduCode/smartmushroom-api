<?php
require_once './model/FaseCultivoModel.php';
require_once './model/CogumeloModel.php';

class FaseCultivoController
{
    private $model;
    private $cogModel;

    public function __construct()
    {
        $this->model = new FaseCultivoModel();
        $this->cogModel = new CogumeloModel();
    }

    function listarTodos(Request $request, Response $response, array $url)
    {
        $fases = $this->model->selectAll();
        return $response->json($fases, 200);
    }

    function listarIdFaseCultivo(Request $request, Response $response, array $url)
    {
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /fase_cultivo/{idFaseCultivo}'], 400);
        }

        if (!is_numeric($url[0])) {
            return $response->json(['message' => 'ID da fase deve ser numérico'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID da fase inválido'], 400);
        }

        $fase = $this->model->selectId($id);
        if ($fase !== null) {
            return $response->json($fase, 200);
        }

        return $response->json(['message' => 'Fase de Cultivo não encontrada'], 404);
    }

    function listarPorCogumelo(Request $request, Response $response, array $url)
    {
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /fase_cultivo/porCogumelo/{idCogumelo}'], 400);
        }

        if (!is_numeric($url[0])) {
            return $response->json(['message' => 'ID do cogumelo deve ser numérico'], 400);
        }

        $idCogumelo = (int)$url[0];
        if ($idCogumelo <= 0) {
            return $response->json(['message' => 'ID do cogumelo inválido'], 400);
        }

        // opcional: validar se o cogumelo existe
        if ($this->cogModel->selectId($idCogumelo) === null) {
            return $response->json(['message' => 'Cogumelo não encontrado'], 404);
        }

        // precisa existir no FaseCultivoModel
        $fases = $this->model->selectByCogumelo($idCogumelo);
        return $response->json($fases, 200);
    }

    function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $nome      = trim($data['nomeFaseCultivo'] ?? '');
        $idCog     = $data['idCogumelo'] ?? null;
        $desc      = $data['descricaoFaseCultivo'] ?? null;
        $tMin      = $data['temperaturaMin'] ?? null;
        $tMax      = $data['temperaturaMax'] ?? null;
        $uMin      = $data['umidadeMin'] ?? null;
        $uMax      = $data['umidadeMax'] ?? null;
        $co2M      = $data['co2Max'] ?? null;

        if ($nome === '' || !is_numeric($idCog) || (int)$idCog <= 0) {
            return $response->json(['message' => 'nomeFaseCultivo e idCogumelo válidos são obrigatórios'], 400);
        }

        if ($this->cogModel->selectId((int)$idCog) === null) {
            return $response->json(['message' => 'idCogumelo não existe'], 400);
        }

        // valida numéricos
        $nums = [$tMin, $tMax, $uMin, $uMax, $co2M];
        foreach ($nums as $n) {
            if (!is_numeric($n)) {
                return $response->json(['message' => 'Parâmetros numéricos inválidos'], 400);
            }
        }
        $tMin = (float)$tMin;
        $tMax = (float)$tMax;
        $uMin = (float)$uMin;
        $uMax = (float)$uMax;
        $co2M = (float)$co2M;

        if (!self::validarIntervalos($tMin, $tMax, $uMin, $uMax, $co2M)) {
            return $response->json(['message' => 'Parâmetros fora de intervalo (verifique mínimos/máximos e co2Max > 0)'], 400);
        }

        $ok = $this->model->create($nome, (int)$idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2M);
        if ($ok) {
            return $response->json(['message' => 'Fase de Cultivo adicionada com sucesso'], 201);
        }

        return $response->json(['erro' => 'Houve um erro ao adicionar Fase de Cultivo'], 500);
    }

    function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $id       = (int)($data['idFaseCultivo'] ?? 0);
        $nome     = trim($data['nomeFaseCultivo'] ?? '');
        $idCog    = $data['idCogumelo'] ?? null;
        $desc     = $data['descricaoFaseCultivo'] ?? null;
        $tMin     = $data['temperaturaMin'] ?? null;
        $tMax     = $data['temperaturaMax'] ?? null;
        $uMin     = $data['umidadeMin'] ?? null;
        $uMax     = $data['umidadeMax'] ?? null;
        $co2M     = $data['co2Max'] ?? null;

        if ($id <= 0 || $nome === '' || !is_numeric($idCog)) {
            return $response->json(['message' => 'idFaseCultivo, nomeFaseCultivo e idCogumelo válidos são obrigatórios'], 400);
        }

        // existe a fase?
        if ($this->model->selectId($id) === null) {
            return $response->json(['message' => 'Fase de Cultivo não encontrada'], 404);
        }

        // existe o cogumelo?
        if ($this->cogModel->selectId((int)$idCog) === null) {
            return $response->json(['message' => 'idCogumelo não existe'], 400);
        }

        // valida numéricos
        $nums = [$tMin, $tMax, $uMin, $uMax, $co2M];
        foreach ($nums as $n) {
            if (!is_numeric($n)) {
                return $response->json(['message' => 'Parâmetros numéricos inválidos'], 400);
            }
        }

        $tMin = (float)$tMin;
        $tMax = (float)$tMax;
        $uMin = (float)$uMin;
        $uMax = (float)$uMax;
        $co2M = (float)$co2M;
        $idCog = (int)$idCog;

        if (!self::validarIntervalos($tMin, $tMax, $uMin, $uMax, $co2M)) {
            return $response->json(['message' => 'Parâmetros fora de intervalo (temperaturas/umidades e co2Max > 0)'], 400);
        }

        $ok = $this->model->update($id, $nome, $idCog, $desc, $tMin, $tMax, $uMin, $uMax, $co2M);
        $atual = $this->model->selectId($id);

        return $response->json([
            'message' => $ok ? 'Fase de Cultivo atualizada' : 'Sem alterações',
            'fase'    => $atual
        ], 200);
    }

    function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /fase_cultivo/{idFaseCultivo}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        if ($this->model->selectId($id) === null) {
            return $response->json(['message' => 'Fase de Cultivo não encontrada'], 404);
        }

        $ok = $this->model->delete($id);
        if ($ok) {
            return $response->json(['message' => 'Fase de Cultivo deletada com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao deletar Fase de Cultivo'], 500);
    }


    private static function validarIntervalos($tMin, $tMax, $uMin, $uMax, $co2M): bool
    {
        if ($tMin >= $tMax) return false;
        if ($uMin >= $uMax) return false;
        if ($uMin < 0 || $uMax > 100) return false;
        if ($co2M <= 0) return false;
        return true;
    }
}
