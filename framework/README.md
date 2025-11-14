# SmartMushroom API ![Version](https://img.shields.io/badge/version-1.0.0-blue.svg) ![Status](https://img.shields.io/badge/status-em%20desenvolvimento-green.svg) ![PHP](https://img.shields.io/badge/php-8.2-777BB4.svg)

## 1. Visão Geral da API
SmartMushroom API é o backend REST responsável por operar um sistema acadêmico de telemetria e automação de estufas de cogumelos. A plataforma concentra leituras ambientais (temperatura, umidade, CO₂, luminosidade), controla atuadores de climatização/iluminação e mantém o histórico completo dos lotes de cultivo.

O projeto faz parte de um estudo de engenharia de software em que o professor fornece um mini framework próprio (baseado em MVC + Router simples). A disciplina avalia arquitetura, documentação e integração entre front-end e dispositivos IoT. A API resolve:

- Cadastro e gerenciamento de salas, lotes, fases e parâmetros de cultivo.
- Registro de leituras telemétricas e geração de gráficos agregados (24h, diário, semanal, mensal).
- Orquestração dos atuadores instalados em cada sala e rastreamento das ações executadas.
- Consulta centralizada para dashboards Web e serviços externos da fazenda inteligente.

## 2. Arquitetura da API

### 2.1 Funcionamento do framework acadêmico
- `framework/index.php` é o front controller. Ele carrega `core/Core.php` e dispara o roteamento.
- `core/Routes.php` registra rotas com `Http::get|post|put|delete`, mapeando-as para `Controller@method`.
- `core/Request.php` encapsula corpo, query e parâmetros dinâmicos.
- `core/Response.php` padroniza retornos JSON e códigos HTTP.
- `core/Conexao.php` provê um singleton PDO configurado via constantes definidas no projeto.
- `core/Core.php` faz o dispatch: quebra a string controller@method, instancia o controller e injeta `Request`, `Response` e `array $url`.
- `core/Jwt.php` fornece utilitários para geração/validação de tokens, pronto para futura proteção de rotas.

### 2.2 Estrutura de diretórios
```
framework/
├── controller/        # Controllers REST (SalaController, LoteController etc.)
├── core/             # Mini-framework fornecido (Router, Request, Response, JWT, DB)
├── model/            # Data access layer (PDO + SQL raw)
├── data_debug.php    # Script auxiliar para depuração
├── index.php         # Front controller
└── README.md         # Este documento
configuracao.php      # Ajustes de bootstrap fora do framework
```

### 2.3 Ciclo de vida das requisições
1. **Client** envia requisição HTTP para `framework/index.php`.
2. **Router** (`core/Routes.php` + `Http` helper) encontra a rota e monta `$url` com parâmetros.
3. **Controller** recebe `Request/Response` e valida entrada.
4. **Model** executa a consulta/alteração usando `Conexao::getInstance()`.
5. **Response** retorna JSON com status apropriado para o cliente.

Diagrama:
```
Client → Router → Controller → Model → Database
```

### 2.4 Middlewares
O framework não possui middlewares encadeados. A validação é feita nas controllers. Caso JWT seja habilitado futuramente, o professor aconselha inserir um middleware manual em `index.php` antes do dispatch para validar `Authorization`.

### 2.5 Controllers
Cada controller contém métodos com assinatura `function action(Request $req, Response $res, array $url)`. Responsabilidades:
- Sanitizar/validar entradas.
- Invocar models e interpretar exceções.
- Definir mensagens de retorno e status HTTP.

### 2.6 Models
Classes em `framework/model` estendem a lógica de negócio de cada entidade. Cada model prepara SQL manualmente, usando `PDO::prepare`, `execute` e `fetch`. Não há ORM. Os models centralizam:
- Joins entre tabelas (ex.: `LoteModel::selectAll`).
- Agregações (ex.: `LeituraModel::getAggregatedData`).
- Regras específicas (ex.: `LoteModel::salaOcupada`).

### 2.7 Helpers / Utils
- `core/Jwt.php`: criação e validação básica de tokens HS256.
- `core/ExceptionPdo.php`: tratamento especializado de exceções de banco.
- `data_debug.php`: utilitário para inspecionar conexão/dados durante aulas.

### 2.8 Configurações
As credenciais do banco e do servidor são definidas em `core/Conexao.php` via constantes (`SGBD`, `HOST`, etc.). Em produção recomenda-se sobrescrever via variáveis de ambiente (vide seção 6).

## 3. Endpoints da API
A seguir, cada rota cadastrada em `core/Routes.php`. Todos os exemplos usam `http://localhost:8000` como host; ajuste conforme seu servidor.

### 3.1 Sala

#### GET /sala/listarTodos
- **Descrição**: retorna todas as salas cadastradas.
- **Parâmetros**: nenhum.
- **Exemplo request**:
  ```http
  GET /sala/listarTodos HTTP/1.1
  Host: localhost:8000
  Accept: application/json
  ```
- **Resposta 200**:
  ```json
  [
    {"idSala":1,"nomeSala":"Sala 01","descricaoSala":"Estufa principal","dataCriacao":"2024-01-12"}
  ]
  ```
- **Resposta erro**: `404` se não houver dados.
- **Validações**: n/a.
- **Dependências**: `SalaModel`, tabela `sala`.

#### GET /sala/listarIdSala/{idSala}
- **Descrição**: consulta sala específica.
- **Parâmetros path**: `idSala` (inteiro > 0).
- **Erros**: `400` ID inválido, `404` não encontrada.
- **Exemplo sucesso**:
  ```json
  {"idSala":2,"nomeSala":"Sala 02","descricaoSala":"Backup"}
  ```

#### GET /sala/listarSalasComLotesAtivos
- **Descrição**: devolve salas com lotes ativos e últimas leituras.
- **Resposta 200**:
  ```json
  {
    "salas":[
      {
        "idSala":1,
        "nomeSala":"Sala 01",
        "lotes":[
          {
            "idLote":5,
            "status":"ativo",
            "nomeCogumelo":"Shiitake",
            "temperatura":21.4,
            "umidade":88.0
          }
        ]
      }
    ]
  }
  ```

#### POST /sala/adicionar
- **Body**:
  ```json
  {"nomeSala":"Sala 03","descricaoSala":"Piloto IoT"}
  ```
- **Validações**: campos obrigatórios, strings não vazias.
- **Resposta**: `201` com mensagem; `400` quando faltar campos.

#### PUT /sala/alterar
- **Body** inclui `idSala`.
- **Erros**: `404` se sala inexistente; `400` se dados inválidos.

#### DELETE /sala/deletar/{idSala}
- **Regra**: só remove se existir; `200` sucesso, `400/404` caso contrário.

### 3.2 Cogumelo
Mesma estrutura CRUD.

| Método | Rota | Descrição | Regras |
|--------|------|-----------|--------|
| GET | `/cogumelo/listarTodos` | Lista especies | Sem parâmetros |
| GET | `/cogumelo/listarIdCogumelo/{id}` | Busca por ID | ID > 0 |
| POST | `/cogumelo/adicionar` | Cria espécie (nome, descrição, tempo médio) | Campos obrigatórios |
| PUT | `/cogumelo/alterar` | Atualiza espécie | Requer ID existente |
| DELETE | `/cogumelo/deletar/{id}` | Remove | Bloqueia se relacionada a lotes ativos (validação no Model) |

Exemplo request:
```http
POST /cogumelo/adicionar
Content-Type: application/json

{
  "nomeCogumelo": "Portobello",
  "tempoCultivoDias": 45
}
```

### 3.3 Fase de Cultivo
- **GET /faseCultivo/listarTodos**: catálogo completo de fases (ex.: colonização, frutificação).
- **GET /faseCultivo/listarPorCogumelo/{idCogumelo}**: filtra por espécie.
- **POST /faseCultivo/adicionar**: cria fases com metas de ambiente.
- **PUT /faseCultivo/alterar**: atualiza metas.
- **DELETE /faseCultivo/deletar/{idFaseCultivo}**.
- **Validações**: `nomeFaseCultivo`, metas numéricas (temperatura, umidade, co2) e `idCogumelo` obrigatórios.

### 3.4 Lote
| Método | Rota | Descrição | Validações principais |
|---|---|---|---|
| GET | `/lote/listarTodos` | Lista lotes com sala + cogumelo | n/a |
| GET | `/lote/listarAtivos` | Apenas ativos | n/a |
| GET | `/lote/listarIdLote/{id}` | Detalhe | ID > 0 |
| GET | `/lote/listarSalasDisponiveis` | Salas sem lotes ativos | n/a |
| GET | `/lote/listarIdSala/{idSala}` | Lote ativo da sala | Sala existente |
| POST | `/lote/adicionar` | Cria lote (idSala, idCogumelo, dataInicio, status) | Verifica sala livre, sala/cogumelo existentes |
| PUT | `/lote/alterar` | Atualiza lote | Garante integridade dos relacionamentos |
| DELETE | `/lote/deletar/{id}` | Finalização lógica (status) | Lote existente |
| DELETE | `/lote/deletar_fisico/{id}` | Remoção física | Apenas quando não há dependências |

Exemplo de criação:
```http
POST /lote/adicionar
Content-Type: application/json

{
  "idSala": 1,
  "idCogumelo": 2,
  "dataInicio": "2024-09-01",
  "status": "ativo"
}
```
Resposta 201:
```json
{
  "message": "Lote criado",
  "idLote": 12
}
```

### 3.5 Histórico de Fase
- **GET /historico_fase/listarTodos**: histórico completo.
- **GET /historico_fase/listarIdLote/{idLote}**: transições por lote.
- **GET /historico_fase/listarIdFase/{idFase}**: quem está/esteve na fase.
- **POST /historico_fase/adicionar**: registra mudança (campos: `idLote`, `idFaseCultivo`, `dataMudanca`, `observacoes`).
- **PUT /historico_fase/alterar**: edita registro.
- **DELETE /historico_fase/deletar/{idHistorico}`.
- **Dependências**: tabelas `lote`, `fase_cultivo`.

### 3.6 Leitura
- **GET /leitura/listarTodos**: últimas leituras de todos os lotes (ordem desc).
- **GET /leitura/listarIdLeitura/{id}`**.
- **GET /leitura/listarIdLote/{idLote}`**: histórico completo do lote.
- **GET /leitura/listarUltimaLeitura/{idLote}`**.
- **POST /leitura/adicionar**:
  ```json
  {
    "idLote": 5,
    "umidade": 85.5,
    "temperatura": 21.7,
    "co2": 1200,
    "luz": "ligado"
  }
  ```
  - Regras: lote ativo obrigatório; impede leituras em lote finalizado.
- **DELETE /leitura/deletar/{id}`**: remove leitura incorreta.

#### GET /leitura/grafico/{idLote}
- **Descrição**: gera séries temporais agregadas para dashboards.
- **Query params**:
  - `aggregation`: `daily`, `weekly`, `monthly`, `24h` (alias `hourly`).
  - `metric`: `temperatura`, `umidade`, `co2`.
  - `start_date`, `end_date` (opcionais, `YYYY-MM-DD` ou `YYYY-MM-DD HH:MM:SS`).
- **Resposta 200**:
  ```json
  {
    "chart_type": "line",
    "data": [
      {"x":"2024-10-01","y":21.5,"label":"Oct 01"},
      {"x":"2024-10-02","y":22.1,"label":"Oct 02"}
    ],
    "metadata":{
      "title":"Temperatura Diária - Últimos 7 dias",
      "x_axis_label":"Dia",
      "y_axis_label":"Temperatura (°C)",
      "color":"#245B88"
    }
  }
  ```
- **Erros**:
  - `400` agregação ou métrica inválida.
  - `404` lote inexistente.
  - `409` se lote finalizado (herdado de validações da controller).

### 3.7 Parâmetros
CRUD semelhante a Fase, porém ligado a `lote`:
- **GET** `/parametros/listarTodos`, `/listarIdParametro/{id}`, `/listarIdLote/{idLote}`.
- **POST /parametros/adicionar**: corpo contém metas (`temperaturaMin`, `temperaturaMax`, etc.).
- **PUT /parametros/alterar`.
- **DELETE /parametros/deletar/{id}`.

### 3.8 Atuador
- **GET /atuador/listarTodos**: lista dispositivos (tipo, sala, status).
- **GET /atuador/listarIdAtuador/{id}`.
- **GET /atuador/listarIdSala/{idSala}`: atuadores instalados na sala.
- **POST /atuador/adicionar**: cadastra hardware.
- **PUT /atuador/alterar**.
- **DELETE /atuador/deletar/{id}`.

### 3.9 Controle de Atuador
- **GET /controleAtuador/listarTodos**: log de acionamentos.
- **GET /controleAtuador/listarIdControle/{id}`.
- **GET /controleAtuador/listarIdAtuador/{idAtuador}`: último status por atuador.
- **GET /controleAtuador/listarIdLote/{idLote}`: estado mais recente por lote (usa janela `ROW_NUMBER()`).
- **POST /controleAtuador/adicionar**:
  ```json
  {"idAtuador":3,"idLote":5,"statusAtuador":"ligado"}
  ```
- **DELETE lógico** `/controleAtuador/deletar/{id}` (marca inativo) e **DELETE físico** `/controleAtuador/deletarFisico/{id}`.

## 4. Banco de Dados

### 4.1 Diagrama ER (texto)
```
[sala] 1---N [lote] 1---N [leitura]
[cogumelo] 1---N [lote]
[fase_cultivo] 1---N [historico_fase] N---1 [lote]
[lote] 1---N [parametro]
[sala] 1---N [atuador] 1---N [controle_atuador] N---1 [lote]
```

### 4.2 Tabelas e relacionamentos
- **sala** (`idSala` PK) — atributos `nomeSala`, `descricaoSala`.
- **cogumelo** (`idCogumelo` PK) — catálogo de espécies.
- **fase_cultivo** (`idFaseCultivo` PK) — obrigatório `idCogumelo` (FK).
- **lote** (`idLote` PK) — FKs `idSala`, `idCogumelo`.
- **historico_fase** (`idHistorico`) — FK `idLote`, `idFaseCultivo`.
- **leitura** (`idLeitura`) — FK `idLote`.
- **parametro** (`idParametro`) — FK `idLote`.
- **atuador** (`idAtuador`) — FK `idSala`.
- **controle_atuador** (`idControle`) — FKs `idAtuador`, `idLote`.

### 4.3 Restrições
- Integridade referencial é obrigatória para todos os FKs.
- `lote.status` limitado a `ativo`, `finalizado` ou `inativo`.
- Leituras não são permitidas em lotes finalizados (controlado no controller).
- `controle_atuador.statusAtuador` aceita `ligado`, `desligado`, `inativo`.

## 5. Requisitos do Ambiente

| Item | Versão/Observação |
|------|-------------------|
| PHP | 8.1+ (recomendado 8.2) |
| Extensões | `pdo`, `pdo_mysql`, `mbstring`, `json` |
| Composer | 2.x (caso utilize pacotes adicionais) |
| Banco de Dados | MySQL/MariaDB 10+ |
| Servidor | Apache + mod_php ou PHP built-in server |

### Variáveis de ambiente / constantes
Configure antes de subir:

| Variável | Descrição |
|----------|-----------|
| `SGBD` | `mysql`, `mssql`, `postgre` |
| `HOST` | Host do banco |
| `DBNAME` | Nome do schema (ex.: `smartmushroom_db`) |
| `USER` / `PASSWORD` | Credenciais |
| `PORTA_DB` | Porta (3306 default) |
| `CHAVE_PRIVADA` | Chave do JWT em `core/Jwt.php` |

## 6. Como executar o projeto
1. **Instalar dependências** (se houver `composer.json`):
   ```bash
   composer install
   ```
2. **Configurar ambiente**:
   ```bash
   cp .env.example .env   # caso mantenha um arquivo de exemplo
   ```
   Ajuste `.env` ou `core/Conexao.php` com credenciais reais.
3. **Executar migrações** (substitua por seu script real):
   ```bash
   php migrate.php
   ```
4. **Iniciar servidor local**:
   ```bash
   php -S localhost:8000 -t framework
   ```
5. **Testar**:
   ```bash
   curl http://localhost:8000/sala/listarTodos
   ```

## 7. Estrutura de Pastas
```
framework/
├── controller/              # Lógica REST (SalaController, LoteController, etc.)
├── core/
│   ├── Conexao.php          # Singleton PDO
│   ├── Core.php             # Dispatcher principal
│   ├── Http.php             # Registrador de rotas
│   ├── Request.php          # Parser de entrada
│   ├── Response.php         # Wrapper JSON
│   ├── Routes.php           # Definição das rotas
│   └── Jwt.php              # Utilitário JWT
├── model/                   # Consultas SQL
├── index.php                # Bootstrap
├── data_debug.php           # Ferramentas de debug
└── README.md                # Documentação
configuracao.php             # Config extra compartilhada
```

## 8. Integração com Autenticação
Ainda não há rotas protegidas, porém `core/Jwt.php` oferece geração/validação HS256.

1. Gere um token:
   ```php
   $token = Jwt::gerarJWT();
   ```
2. Inclua nos headers:
   ```
   Authorization: Bearer <token>
   ```
3. Para validar, chame `Jwt::tokenValido($token)` antes de despachar rotas. O método retorna string vazia quando válido ou mensagem de erro (`Token está expirado`, `Assinatura inválida`, etc.).

## 9. Erros e Tratamento de Exceções
- Estrutura padrão:
  ```json
  {
    "message": "Descrição do erro",
    "errors": [],
    "timestamp": "2025-02-11T22:15:00Z"
  }
  ```
  (alguns controllers retornam apenas `message`; padronize conforme necessário).
- **Códigos comuns**:
  - `200` sucesso.
  - `201` recurso criado.
  - `400` validação/uso incorreto.
  - `404` recurso inexistente.
  - `409` conflito de negócio (ex.: inserir leitura em lote finalizado).
  - `500` erro inesperado (capturado via `try/catch`).
- Exceptions de banco (`PDOException`) são propagadas até o controller, que responde com `500` e mensagem genérica.

## 10. Testes da API
- **PHPUnit**: ainda não configurado; recomenda-se incluir em `composer.json`.
- **Testes manuais**:
  - Insomnia/Postman: importe as rotas listadas e configure o host base.
  - Curl rápido:
    ```bash
    curl -X POST http://localhost:8000/leitura/adicionar \
         -H "Content-Type: application/json" \
         -d '{"idLote":5,"umidade":82,"temperatura":21.5,"co2":1150,"luz":"ligado"}'
    ```
- **Coleções sugeridas**: forneça `docs/SmartMushroom.postman_collection.json` (crie conforme necessidade) com variáveis `{{base_url}}`.

## 11. Segurança da API
- **Rate limit**: não implementado. Sugere-se usar gateway (ex.: Nginx ou API Management) e limitar por IP/token.
- **Sanitização**: controllers validam tipos, mas não escapam strings — rely on prepared statements (SQL injection mitigado). Adicionalmente, sanitize entradas textuais para evitar XSS quando os dados forem exibidos no front.
- **SQL Injection**: todos os models usam `PDO::prepare`. Nunca concatene inputs diretamente.
- **CORS**: configure no servidor (Apache/Nginx) ou adicione headers em `Response`.
- **Permissões**: ainda não há RBAC; quando JWT for adotado, inclua claims (`role`, `sala_ids`) e valide em cada controller.

## 12. Roadmap
1. Implementar middleware de autenticação JWT e refresh tokens.
2. Normalizar respostas de erro (`message`, `code`, `details`).
3. Adicionar testes automatizados (PHPUnit + integração).
4. Criar migrations/versionamento de banco.
5. Internacionalizar mensagens (pt-BR/en-US).
6. Adicionar cache para relatórios e gráficos.
7. Disponibilizar collection Postman e especificação OpenAPI 3.0.

## 13. Contribuição
1. Faça um fork e crie um branch (`git checkout -b feature/minha-feature`).
2. Garanta que o código siga o padrão PSR-12 (use `phpcs` se disponível).
3. Abra um Pull Request descrevendo:
   - Contexto e motivação.
   - Passos para reproduzir/testar.
   - Checklist (testes, documentação atualizada).
4. Para dúvidas ou melhorias sem código, abra uma Issue descrevendo cenários e logs.

## 14. Licença
Distribuído sob a licença **MIT**. Consulte o arquivo `LICENSE` (ou inclua-o na raiz) para mais detalhes.

## 15. Contato / Suporte
- **Coordenação acadêmica**: professor responsável pelo framework.
- **Time SmartMushroom**: smartmushroom@universidade.edu.br
- **Dúvidas técnicas**: abra uma issue ou envie e-mail com logs + versão da API.

---
> _Este repositório faz parte de um projeto educacional. O framework foi entregue pelo professor e pode divergir de padrões de mercado. Use esta documentação como ponto de partida para evoluções futuras._
