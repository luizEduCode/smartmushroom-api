<?php
require_once './model/AtuadorModel.php';
require_once './model/SalaModel.php';

class AtuadorController
{
    private $model;
    private $salaModel;

    public function __construct()
    {
        $this->model = new AtuadorModel();
        $this->salaModel = new SalaModel();
    }

    function listarTodos(Request $request, Response $response, array $url)
    {
        $dados = $this->model->selectAll();
        return $response->json($dados, 200);
    }

    function listarIdAtuador(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /atuador/listarIdAtuador/{idAtuador}'], 400);
        }
        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID do atuador inválido'], 400);
        }

        $atuador = $this->model->selectIdAtuador($id);
        if ($atuador !== null) {
            return $response->json($atuador, 200);
        }
        return $response->json(['message' => 'Atuador não encontrado'], 404);
    }

    function listarIdSala(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /atuador/listarIdSala/{idSala}'], 400);
        }
        $idSala = (int)$url[0];
        if ($idSala <= 0) {
            return $response->json(['message' => 'ID da sala inválido'], 400);
        }

        $salas = $this->model->selectIdSala($idSala);
        if (empty($salas)) {
            return $response->json(['message' => 'Nenhum atuador encontrado para esta sala'], 200);
        }
        return $response->json($salas, 200);
    }

    function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idSala      = $data['idSala']      ?? null;
        $nomeAtuador = isset($data['nomeAtuador']) ? trim($data['nomeAtuador']) : null;
        $tipoAtuador = isset($data['tipoAtuador']) ? strtolower(trim($data['tipoAtuador'])) : null;

        if (!is_numeric($idSala) || (int)$idSala <= 0 || !$nomeAtuador || !$tipoAtuador) {
            return $response->json(['message' => 'Campos inválidos: idSala, nomeAtuador e tipoAtuador'], 400);
        }

        if ($this->salaModel->selectId((int)$idSala) === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);
        }

        if (!self::validarAtuador($tipoAtuador)) {
            return $response->json(['message' => "Tipo de atuador inválido. Use 'umidade', 'temperatura', 'co2' ou 'luz'"], 400);
        }

        // pode manter o create recebendo array; normalize:
        $novoId = $this->model->create([
            'idSala'      => (int)$idSala,
            'nomeAtuador' => $nomeAtuador,
            'tipoAtuador' => $tipoAtuador
        ]);

        if ($novoId > 0) {
            $novoAtuador = $this->model->selectIdAtuador($novoId);
            return $response->json([
                'message'   => 'Atuador adicionado com sucesso',
                'idAtuador' => $novoId,
                'atuador'   => $novoAtuador
            ], 201);
        }

        return $response->json(['message' => 'Erro ao adicionar atuador'], 500);
    }

    function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idAtuador   = (int)($data['idAtuador'] ?? 0);
        $idSala      = $data['idSala']      ?? null;
        $nomeAtuador = isset($data['nomeAtuador']) ? trim($data['nomeAtuador']) : null;
        $tipoAtuador = isset($data['tipoAtuador']) ? strtolower(trim($data['tipoAtuador'])) : null;

        if ($idAtuador <= 0) {
            return $response->json(['message' => 'idAtuador inválido'], 400);
        }

        $atuadorAtual = $this->model->selectIdAtuador($idAtuador);
        if ($atuadorAtual === null) {
            return $response->json(['message' => 'Atuador não encontrado'], 404);
        }

        if (!is_numeric($idSala) || (int)$idSala <= 0 || !$nomeAtuador || !$tipoAtuador) {
            return $response->json(['message' => 'Campos obrigatórios ausentes ou inválidos (idSala, nomeAtuador, tipoAtuador)'], 400);
        }

        // FK sala
        $salaAtual = $this->salaModel->selectId((int)$idSala);
        if ($salaAtual === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);
        }

        // tipo
        if (!self::validarAtuador($tipoAtuador)) {
            return $response->json(['message' => "Tipo de atuador inválido. Use 'umidade', 'temperatura', 'co2' ou 'luz'"], 400);
        }

        // no-op (evita UPDATE desnecessário)
        if (
            (int)$atuadorAtual['idSala'] === (int)$idSala &&
            $atuadorAtual['nomeAtuador'] === $nomeAtuador &&
            strtolower($atuadorAtual['tipoAtuador']) === $tipoAtuador
        ) {
            return $response->json(['message' => 'Sem alterações', 'atuador' => $atuadorAtual], 200);
        }

        $ok = $this->model->update($idAtuador, (int)$idSala, $nomeAtuador, $tipoAtuador);
        $atual = $this->model->selectIdAtuador($idAtuador);

        return $response->json([
            'message' => $ok ? 'Atuador atualizado' : 'Sem alterações',
            'atuador' => $atual
        ], 200);
    }

    function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /atuador/{idAtuador}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $atuador = $this->model->selectIdAtuador($id);
        if ($atuador === null) {
            return $response->json(['message' => 'Atuador não encontrado'], 404);
        }

        $ok = $this->model->delete($id);
        if ($ok) {
            return $response->json(['message' => 'Atuador deletado com sucesso'], 200);
        }

        return $response->json(['message' => 'Erro ao deletar atuador'], 500);
    }

    /*===================== helpers ===================== */
    private static function validarAtuador(string $atuador): bool
    {
        return in_array(strtolower($atuador), ['umidade', 'temperatura', 'co2', 'luz'], true);
    }
}
