<?php
// TODO: Regra de negocio:
/* 
Ao criar um novo lote devemos criar também um novo histórico fase
- Receber do fronte o tipo de fase do lote a ser criado;
- Acessar a Model de histórico_fase
- Adicionar infomação de lote e fase_cultivo a historico_fase
*/
require_once './model/LoteModel.php';
require_once './model/SalaModel.php';
require_once './model/CogumeloModel.php';

class LoteController
{
    private $model;
    private $salaModel;
    private $cogModel;

    public function __construct()
    {
        $this->model = new LoteModel();
        $this->salaModel = new SalaModel();
        $this->cogModel = new CogumeloModel();
    }

    function listarTodos(Request $request, Response $response, array $url)
    {
        $lotes = $this->model->selectAll();
        return $response->json($lotes, 200);
    }

    function listarSalasDisponiveis(Request $request, Response $response, array $url)
    {
        $salas = $this->model->selectSalasSemLoteAtivo();

        if (empty($salas)) {
            return $response->json([
                'message' => 'Nenhuma sala disponível para criação de lote'
            ], 200);
        }

        return $response->json($salas, 200);
    }

    function listarAtivos(Request $request, Response $response, array $url)
    {
        $ativos = $this->model->selectAtivos();

        if (empty($ativos)) {
            return $response->json(['message' => 'Nenhum lote ativo encontrado'], 200);
        }

        return $response->json($ativos, 200);
    }

    function listarIdLote(Request $request, Response $response, array $url)
    {
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /lote/{idLote}'], 400);
        }
        if (!is_numeric($url[0])) {
            return $response->json(['message' => 'ID do lote deve ser numérico'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID do lote inválido'], 400);
        }

        $lote = $this->model->selectId($id);
        if ($lote !== null) {
            return $response->json($lote, 200);
        }

        return $response->json(['message' => 'Lote não encontrado'], 404);
    }

    function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idSala     = $data['idSala']      ?? null;
        $idCogumelo = $data['idCogumelo']  ?? null;
        $dataInicio = $data['dataInicio']  ?? null; // 'YYYY-MM-DD'
        $dataFim    = $data['dataFim']     ?? null; // opcional
        $status     = $data['status']      ?? 'ativo';

        // default para hoje se não vier dataInicio 
        if ($dataInicio === null || $dataInicio === '') {
            $dataInicio = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        }

        // validações básicas
        if (
            !is_numeric($idSala) || (int)$idSala <= 0 ||
            !is_numeric($idCogumelo) || (int)$idCogumelo <= 0 ||
            !is_string($dataInicio) || $dataInicio === ''
        ) {
            return $response->json(['message' => 'idSala, idCogumelo e dataInicio são obrigatórios e válidos'], 400);
        }

        // valida FK
        if ($this->salaModel->selectId((int)$idSala) === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);
        }
        if ($this->cogModel->selectId((int)$idCogumelo) === null) {
            return $response->json(['message' => 'Cogumelo não encontrado'], 404);
        }

        // normalizações
        $idSala = (int)$idSala;
        $idCogumelo = (int)$idCogumelo;
        $status = strtolower(trim($status));

        // valida status e datas
        if (!self::validarStatus($status)) {
            return $response->json(['message' => "Status inválido. Use 'ativo' ou 'finalizado'"], 400);
        }
        if (!self::isValidDate($dataInicio)) {
            return $response->json(['message' => 'dataInicio inválida (esperado YYYY-MM-DD)'], 400);
        }
        if ($dataFim !== null && $dataFim !== '' && !self::isValidDate($dataFim)) {
            return $response->json(['message' => 'dataFim inválida (esperado YYYY-MM-DD)'], 400);
        }
        if (!self::validarDatas($dataInicio, $dataFim)) {
            return $response->json(['message' => 'dataFim deve ser igual ou posterior a dataInicio'], 400);
        }

        // normalizar dataFim vazia para null
        $dataFim = ($dataFim === '' ? null : $dataFim);


        if ($this->model->salaOcupada($idSala)) {
            return $response->json(['message' => 'A sala já possui um lote ativo'], 409);
        }

        $novoId = $this->model->create($idSala, $idCogumelo, $dataInicio, $dataFim, $status);
        if ($novoId > 0) {
            return $response->json([
                'message' => 'Lote criado com sucesso',
                'idLote'  => $novoId
            ], 201);
        }

        return $response->json(['message' => 'Erro ao criar lote'], 500);
    }

    function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body não recebido'], 400);
        }

        $idLote    = (int)($data['idLote'] ?? 0);
        $idSala    = $data['idSala']      ?? null;
        $idCog     = $data['idCogumelo']  ?? null;
        $dataInicio = $data['dataInicio']  ?? null;
        $dataFim   = $data['dataFim']     ?? null;
        $status    = $data['status']      ?? null;

        if ($idLote <= 0) {
            return $response->json(['message' => 'idLote inválido'], 400);
        }

        // existe o lote?
        $loteAtual = $this->model->selectId($idLote);
        if ($loteAtual === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        // campos obrigatórios para update completo (ajuste se quiser permitir parciais)
        if (
            !is_numeric($idSala) || (int)$idSala <= 0 ||
            !is_numeric($idCog)  || (int)$idCog  <= 0 ||
            !is_string($dataInicio) || $dataInicio === '' ||
            !is_string($status) || $status === ''
        ) {
            return $response->json(['message' => 'Campos obrigatórios ausentes ou inválidos (idSala, idCogumelo, dataInicio, status)'], 400);
        }

        // valida FK
        if ($this->salaModel->selectId((int)$idSala) === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);
        }
        if ($this->cogModel->selectId((int)$idCog) === null) {
            return $response->json(['message' => 'Cogumelo não encontrado'], 404);
        }

        $idSala = (int)$idSala;
        $idCog  = (int)$idCog;
        $status = strtolower(trim($status));

        if (!self::validarStatus($status)) {
            return $response->json(['message' => "Status inválido. Use 'ativo' ou 'finalizado'"], 400);
        }
        if (!self::isValidDate($dataInicio)) {
            return $response->json(['message' => 'dataInicio inválida (esperado YYYY-MM-DD)'], 400);
        }
        if ($dataFim !== null && $dataFim !== '' && !self::isValidDate($dataFim)) {
            return $response->json(['message' => 'dataFim inválida (esperado YYYY-MM-DD)'], 400);
        }

        $agora = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        if ($status === 'finalizado') {
            // define dataFim se não vier
            if ($dataFim === null || $dataFim === '') {
                $dataFim = $agora;
            }
        } elseif ($status === 'ativo') {
            // ao voltar para ativo, limpar dataFim
            $dataFim = null;

            // garantir que não exista outro lote ativo na mesma sala (excluindo este)
            if ($this->model->salaOcupada($idSala)) {
                return $response->json(['message' => 'Essa sala já possui outro lote ativo'], 409);
            }
        }

        if (!self::validarDatas($dataInicio, $dataFim)) {
            return $response->json(['message' => 'dataFim deve ser igual ou posterior a dataInicio'], 400);
        }

        $ok = $this->model->update($idLote, $idSala, $idCog, $dataInicio, $dataFim, $status);
        $atual = $this->model->selectId($idLote);

        return $response->json([
            'message' => $ok ? 'Lote atualizado' : 'Sem alterações',
            'lote'    => $atual
        ], 200);
    }

    function deletar(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /lote/{idLote}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $lote = $this->model->selectId($id);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        $ok = $this->model->finalizar($id);
        if ($ok) {
            return $response->json(['message' => 'Lote finalizado com sucesso'], 200);
        }

        return $response->json(['message' => 'Lote já estava finalizado ou não pôde ser alterado'], 400);
    }

        function deletar_fisico(Request $request, Response $response, array $url)
    {
        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /lote/{idLote}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);
        }

        $lote = $this->model->selectId($id);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        $ok = $this->model->finalizar_fisico($id);
        if ($ok) {
            return $response->json(['message' => 'Lote excluido com sucesso'], 200);
        }

        return $response->json(['message' => 'Lote já estava finalizado ou não pôde ser alterado'], 400);
    }


    /* ===================== helpers ===================== */

    private static function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private static function validarStatus(string $status): bool
    {
        return in_array($status, ['ativo', 'finalizado'], true);
    }

    private static function validarDatas(string $dataInicio, ?string $dataFim): bool
    {
        if ($dataFim === null || $dataFim === '') return true;
        return (strtotime($dataFim) >= strtotime($dataInicio));
    }
}
