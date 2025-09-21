<?php
require_once './model/SalaModel.php';

class SalaController
{
    private $model;

    public function __construct()
    {
        $this->model = new SalaModel();
    }


    function listarTodos(Request $request, Response $response, array $url)
    {
        $sala = $this->model->selectAll();
        $response->json($sala, 200);
    }

    function listarIdSala(Request $request, Response $response, array $url)
    {
        // Se não veio NENHUM parâmetro, devolve Bad Request
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /sala/{idSala}'], 400);
        }
        $idSala = $url[0];

        if (empty($idSala) || !is_numeric($idSala)) {
            return $response->json(['message' => 'ID da sala deve ser numérico'], 400);
        }

        if ((int)$idSala <= 0) {
            return $response->json(['message' => 'ID da sala inválido'], 400);
        }

        $sala = $this->model->selectId($idSala);

        if (!empty($sala)) {
            return $response->json($sala, 200);
        }

        $response->json(['message' => 'Sala não encontrada'], 404);
    }

    function adicionar(Request $request, Response $response, array $url)
    {
        $data = $request->body();

        //qual a validação mais adequada confirmar o envio de todos os campos do front? 

        if (empty($data)) {
            return $response->json(['message' => 'Campos do front não recebido'], 404);
        }

        $nomeSala = $data['nomeSala'];
        $descricaoSala = $data['descricaoSala'];

        if (empty($nomeSala)) {
            return $response->json(['message' => 'Campo Nome Sala é obrigatório'], 400);
        }
        if (empty($descricaoSala)) {
            return $response->json(['message' => 'Campo Descricao Sala é obrigatório'], 400);
        }

        $sucesso = $this->model->create($nomeSala, $descricaoSala);

        if ($sucesso) {
            return $response->json(['message' => 'Sala adicionada com sucesso'], 201);
        }

        $response->json(['erro' => 'Houve um erro ao adicionar sala'], 500);
    }

    function alterar(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Campos do front não recebido'], 400);
        }

        $idSala = $data['idSala'];
        $nomeSala = $data['nomeSala'];
        $descricaoSala = $data['descricaoSala'];
        if ($idSala <= 0 || $nomeSala === '' || $descricaoSala === '') {
            return $response->json(['message' => 'Dados inválidos'], 400);
        }

        if ($this->model->selectId($idSala) === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);
        }

        $sucesso = $this->model->update($idSala, $nomeSala, $descricaoSala);
        if (!$sucesso) {
            return $response->json(['message' => 'Nada foi alterado ou ocorreu um erro'], 400);
        }

        $alterado = $this->model->selectId($idSala);
        if ($sucesso) {
            return $response->json([
                'message' => 'Sala alterada com sucesso',
                'alterado' => $alterado,
            ], 200);
        }

        $response->json(['erro' => 'Houve um erro ao alterar Sala'], 500);
    }

    function deletar(Request $request, Response $response, array $url)
    {
        // Se não veio NENHUM parâmetro, devolve Bad Request
        if (!isset($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /sala/{idSala}'], 400);
        }
        $idSala = (int) ($url[0] ?? 0);

        if ($idSala <= 0) {
            return $response->json(['message' => 'ID inválido'], 400);;
        }


        if ($this->model->selectId($idSala) === null) {
            return $response->json(['message' => 'Sala não encontrada'], 404);;
        }

        $sucesso = $this->model->delete($idSala);
        if ($sucesso) {
            return $response->json(['message' => 'Sala deletada com sucesso',], 200);
        }

        $response->json(['message' => 'Erro ao deletar sala'], 500);
    }

    // No arquivo SalaController.php, adicione este método:

function listarSalasComLotesAtivos(Request $request, Response $response, array $url) 
{
    try {
        $salas = $this->model->selectSalasComLotesAtivos();
        
        if (!empty($salas)) {
            // Agrupar os resultados por sala para uma estrutura mais organizada
            $salasAgrupadas = [];
            foreach ($salas as $sala) {
                $idSala = $sala['idSala'];
                
                if (!isset($salasAgrupadas[$idSala])) {
                    $salasAgrupadas[$idSala] = [
                        'idSala'   => $sala['idSala'],
                        'nomeSala' => $sala['nomeSala'],
                        'lotes'    => []
                    ];
                }
                
                // Adicionar lote apenas se tiver idLote (evita linhas vazias)
                if (!empty($sala['idLote'])) {
                    $salasAgrupadas[$idSala]['lotes'][] = [
                        'idLote'         => $sala['idLote'],
                        'dataInicio'     => $sala['dataInicio'],
                        'status'         => $sala['status'],
                        'idCogumelo'     => $sala['idCogumelo'],   // 🔹 agora aparece
                        'nomeCogumelo'   => $sala['nomeCogumelo'],
                        'idFaseCultivo'   => $sala['idFaseCultivo'],
                        'nomeFaseCultivo'=> $sala['nomeFaseCultivo'],
                        'temperatura'    => $sala['temperatura'],
                        'umidade'        => $sala['umidade'],
                        'co2'            => $sala['co2']
                    ];
                }
            }
            
            // Converter array associativo para numérico
            $resultado = array_values($salasAgrupadas);
            
            return $response->json(['salas' => $resultado], 200);
        }
        
        return $response->json(['message' => 'Nenhuma sala com lotes ativos encontrada'], 404);
        
    } catch (Exception $e) {
        return $response->json(['erro' => 'Erro ao buscar salas: ' . $e->getMessage()], 500);
    }
}


    
}
