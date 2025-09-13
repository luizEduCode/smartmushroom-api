<?php
require_once './model/CogumeloModel.php';

class CogumeloController
{
    private $model;

    public function __construct()
    {
        $this->model = new CogumeloModel();
    }

    function listarTodos(Request $request, Response $response, array $url)
    {
        $sala = $this->model->selectAll();
        $response->json($sala, 200);
    }

    function listarIdCogumelo(Request $request, Response $response, array $url)
    {
        // Se não veio NENHUM parâmetro, devolve Bad Request
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /cogumelo/{idCogumelo}'], 400);
        }
        $idCogumelo = $url[0];

        if (empty($idCogumelo) || !is_numeric($idCogumelo)) {
            return $response->json(['message' => 'ID cogumelo deve ser numérico'], 400);
        }

        if ((int)$idCogumelo <= 0) {
            return $response->json(['message' => 'ID cogumelo inválido'], 400);
        }

        $cogumelo = $this->model->selectId($idCogumelo);

        if (!empty($cogumelo)) {
            return $response->json($cogumelo, 200);
        }

        $response->json(['message' => 'Cogumelo não encontrado'], 404);
    }

    function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();

        //qual a validação mais adequada confirmar o envio de todos os campos do front? 

        if (empty($data)) {
            return $response->json(['message' => 'Campos do front não recebido'], 404);
        }

        $nomeCogumelo = $data['nomeCogumelo'];
        $descricao = $data['descricao'];

        if (empty($nomeCogumelo)) {
            return $response->json(['message' => 'Campo Nome Cogumelo é obrigatório'], 400);
        }
        if (empty($descricao)) {
            return $response->json(['message' => 'Campo Descricao Cogumelo é obrigatório'], 400);
        }

        $sucesso = $this->model->create($nomeCogumelo, $descricao);

        if ($sucesso) {
            return $response->json(['message' => 'Cogumelo adicionada com sucesso'], 201);
        }

        $response->json(['erro' => 'Houve um erro ao adicionar Cogumelo'], 500);
    }

    function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Campos do front não recebido'], 400);
        }

        $idCogumelo = $data['idCogumelo'];
        $nomeCogumelo = $data['nomeCogumelo'];
        $descricao = $data['descricao'];
        if ($idCogumelo <= 0 || $nomeCogumelo === '' || $descricao === '') {
            return $response->json(['message' => 'Dados inválidos'], 400);
        }

        if ($this->model->selectId($idCogumelo) === null) {
            return $response->json(['message' => 'Cogumelo não encontrada'], 404);
        }

        $sucesso = $this->model->update($idCogumelo, $nomeCogumelo, $descricao);
        if (!$sucesso) {
            return $response->json(['message' => 'Nada foi alterado ou ocorreu um erro'], 400);
        }

        $alterado = $this->model->selectId($idCogumelo);
        if ($sucesso) {
            return $response->json([
                'message' => 'Cogumelo alterada com sucesso',
                'alterado' => $alterado,
            ], 200);
        }

        $response->json(['erro' => 'Houve um erro ao alterar Cogumelo'], 500);
    }

    function deletar(Request $request, Response $response, array $url)
    {
        // Se não veio NENHUM parâmetro, devolve Bad Request
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /cogumelo/{idCogumelo}'], 400);
        }
        $idCogumelo = (int) ($url[0] ?? 0);

        if ($idCogumelo <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);;
        }


        if ($this->model->selectId($idCogumelo) === null) {
            return $response->json(['message' => 'Cogumelo não encontrada'], 404);;
        }

        $sucesso = $this->model->delete($idCogumelo);
        if ($sucesso) {
            return $response->json(['message' => 'Cogumelo deletada com sucesso',], 200);
        }

        $response->json(['message' => 'Erro ao deletar Cogumelo'], 500);
    }
}
