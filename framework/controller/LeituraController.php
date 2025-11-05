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

    public function gerarGrafico(Request $request, Response $response, array $url)
    {
        // 1. Validar idLote da URL
        if (!isset($url[0]) || !is_numeric($url[0]) || (int)$url[0] <= 0) {
            return $response->json(['message' => 'Uso correto: GET /leitura/grafico/{idLote}'], 400);
        }
        $idLote = (int)$url[0];

        // 2. Validar o lote
        $lote = $this->loteModel->selectId($idLote);
        if ($lote === null) {
            return $response->json(['message' => 'Lote não encontrado'], 404);
        }

        // 3. Obter e validar parâmetros de query
        $queryParams = $request->body();

        $aggregation = strtolower($queryParams['aggregation'] ?? 'daily');
        if (!in_array($aggregation, ['daily', 'weekly'])) {
            return $response->json(['message' => 'Parâmetro aggregation inválido. Use "daily" ou "weekly".'], 400);
        }

        $metric = strtolower($queryParams['metric'] ?? 'temperatura');
        $allowedMetrics = ['umidade', 'temperatura', 'co2']; // 'luz' não é adequado para média em gráfico de linha
        if (!in_array($metric, $allowedMetrics)) {
            return $response->json(['message' => 'Parâmetro metric inválido. Use "umidade", "temperatura" ou "co2".'], 400);
        }

        $startDateStr = $queryParams['start_date'] ?? null;
        $endDateStr = $queryParams['end_date'] ?? null;

        // 4. Definir datas padrão se não forem fornecidas
        $now = new DateTime();
        $startDate = null;
        $endDate = null;

        if ($startDateStr && $endDateStr) {
            try {
                $startDate = new DateTime($startDateStr);
                $endDate = new DateTime($endDateStr);
            } catch (Exception $e) {
                return $response->json(['message' => 'Formato de data inválido. Use YYYY-MM-DD.'], 400);
            }
        } else {
            // Datas padrão
            if ($aggregation === 'daily') {
                $endDate = clone $now;
                $startDate = (clone $now)->modify('-7 days');
            } elseif ($aggregation === 'weekly') {
                $endDate = clone $now;
                // Ajustar end_date para o final da semana atual ou para o último domingo/sábado
                // Para consistência, vamos ajustar start/end para o início da semana.
                // Se hoje é quarta, a semana termina domingo. Se quisermos 8 semanas completas,
                // precisamos ir mais para trás.
                // Para simplificar, vamos pegar 8 semanas para trás do dia atual.
                $startDate = (clone $now)->modify('-8 weeks');
            }
        }

        // Formato para o Model
        $modelStartDate = $startDate->format('Y-m-d');
        $modelEndDate = $endDate->format('Y-m-d');

        // 5. Chamar o Model para obter os dados agregados
        $rawData = $this->model->getAggregatedData($idLote, $metric, $aggregation, $modelStartDate, $modelEndDate);

        // 6. Formatar os dados para a resposta JSON
        $formattedData = [];
        $title = '';
        $xAxisLabel = '';
        $yAxisLabel = '';

        if ($aggregation === 'daily') {
            $xAxisLabel = 'Dia';
            $yAxisLabel = ucfirst($metric) . ' (°C)'; // Assumindo °C para temperatura, ajustar para outros
            if ($metric === 'umidade') $yAxisLabel = 'Umidade (%)';
            if ($metric === 'co2') $yAxisLabel = 'CO2 (ppm)';

            $title = ucfirst($metric) . ' Diária - ';
            if ($startDateStr && $endDateStr) {
                $title .= $startDate->format('d/m/Y') . ' a ' . $endDate->format('d/m/Y');
            } else {
                $title .= 'Últimos 7 Dias';
            }

            foreach ($rawData as $row) {
                $date = new DateTime($row['aggregation_key']);
                $formattedData[] = [
                    'x'     => $date->format('Y-m-d'),
                    'y'     => round((float)$row['average_value'], 1),
                    'label' => $date->format('M d')
                ];
            }
        } elseif ($aggregation === 'weekly') {
            $xAxisLabel = 'Semana';
            $yAxisLabel = ucfirst($metric) . ' (°C)';
            if ($metric === 'umidade') $yAxisLabel = 'Umidade (%)';
            if ($metric === 'co2') $yAxisLabel = 'CO2 (ppm)';

            $title = ucfirst($metric) . ' Semanal - ';
            if ($startDateStr && $endDateStr) {
                $title .= $startDate->format('d/m/Y') . ' a ' . $endDate->format('d/m/Y');
            } else {
                $title .= 'Últimas 8 Semanas';
            }

            foreach ($rawData as $row) {
                $startDateOfWeek = new DateTime($row['aggregation_key']);
                $weekNumber = $startDateOfWeek->format('W'); // Número da semana ISO 8601
                $formattedData[] = [
                    'x'     => $startDateOfWeek->format('Y-m-d'),
                    'y'     => round((float)$row['average_value'], 1),
                    'label' => 'Sem ' . $weekNumber
                ];
            }
        }

        // 7. Construir a resposta final
        $responsePayload = [
            "chart_type" => "line",
            "data"       => $formattedData,
            "metadata"   => [
                "title"        => $title,
                "x_axis_label" => $xAxisLabel,
                "y_axis_label" => $yAxisLabel,
                "color"        => "#245B88" // Cor padrão
            ]
        ];

        return $response->json($responsePayload, 200);
    }
}
