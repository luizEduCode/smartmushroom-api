# API legada — SmartMushroom

## 1. Objetivo e escopo

Este documento registra o funcionamento da API SmartMushroom existente antes da refatoração.

O objetivo é preservar uma referência histórica do sistema que será alterado, registrando:

- arquitetura e fundação HTTP;
- autenticação e autorização;
- rotas existentes;
- fluxos de negócio implementados;
- comportamentos particulares do código;
- limitações e problemas conhecidos.

O documento descreve o comportamento observado no código executável de `framework/` e sua relação com o banco legado. Ele **não define o comportamento desejado da nova API**.

Arquivos PHP antigos existentes fora de `framework/`, como o `configuracao.php` da raiz do repositório, não fazem parte deste inventário porque o framework atual não depende deles.

**Estado documentado:** agosto de 2026, branch `main` do repositório `smartmushroom-api`.

---

## 2. Visão geral

A API foi desenvolvida em PHP puro e utiliza uma arquitetura simples baseada em:

```text
Requisição HTTP
    ↓
index.php
    ↓
Core / Routes
    ↓
Controller
    ↓
Model
    ↓
PDO
    ↓
MySQL
```

Principais tecnologias e características:

- PHP puro;
- MySQL;
- PDO para acesso ao banco;
- XAMPP/Apache como ambiente local;
- roteamento próprio;
- controllers e models próprios;
- autenticação JWT implementada manualmente;
- respostas em JSON;
- aplicação organizada dentro do diretório `framework/`.

Estrutura principal da API:

```text
framework/
├── controller/
├── core/
├── model/
├── .htaccess
├── data_debug.php
└── index.php
```

O fluxo por `index.php` é o caminho normal das requisições roteadas. No Apache, o `.htaccess` não reescreve URLs que correspondem a arquivos físicos existentes. Por isso, arquivos PHP existentes diretamente em `framework/` podem ser acessados sem passar pelo roteador.

`data_debug.php` é um exemplo desse comportamento: o arquivo pode ser servido diretamente pelo Apache e ativa `display_errors` e `error_reporting(E_ALL)`. Ele não é uma rota da API, mas constitui um ponto de entrada paralelo ao `index.php`.

---

## 3. Fundação HTTP

### 3.1 Roteamento

As rotas ficam cadastradas em `core/Routes.php`. O `Core::dispatch()` compara a URL recebida com as rotas registradas, verifica o verbo HTTP, carrega o controller e chama a action correspondente.

Parâmetros de caminho são reconhecidos por padrões como `{idLote}` e enviados ao método do controller.

O padrão de roteamento aceita letras, números, `_`, `-`, `.` e `@` nos parâmetros de caminho. Quando uma rota espera um identificador numérico, essa validação ocorre posteriormente no controller.

Quando a rota não existe, o fluxo normal retorna `404`. Quando o caminho existe, mas o verbo HTTP ou a action correspondente não é válido para aquele despacho, retorna `405`.

### 3.2 Entrada de dados

`Request::body()` trata os verbos de maneiras diferentes:

| Verbo | Fonte utilizada |
|---|---|
| `GET` | `$_GET` |
| `DELETE` | `$_GET` |
| `POST` | `$_POST` |
| `PUT` | `php://input` processado por `parse_str()` |

Existe também `Request::bodyJson()`, capaz de decodificar JSON, mas os controllers ativos utilizam `body()` para a leitura dos dados de entrada.

Consequentemente, a API legada não possui um contrato de entrada padronizado em JSON.

### 3.3 Respostas

`Response::json()`:

- define o status HTTP;
- envia `Content-Type: application/json`;
- serializa o conteúdo com `json_encode()`.

O formato do corpo varia entre endpoints. São utilizados campos como `message`, `erro`, `status`, nomes específicos de recursos e arrays retornados diretamente.

Não existe um envelope único de sucesso ou erro para toda a API.

### 3.4 Banco de dados

A conexão é centralizada em `core/Conexao.php` e utiliza PDO.

Na versão registrada:

- SGBD: MySQL;
- host: `localhost`;
- banco: `smartmushroom_db`;
- charset: `utf8`;
- PDO configurado com `ERRMODE_EXCEPTION`.

As configurações de conexão estão definidas diretamente no código.

---

## 4. Autenticação e usuários

### 4.1 Login

O login ocorre por:

```text
POST /usuario/login
```

O controller:

1. recebe e-mail e senha;
2. normaliza o e-mail;
3. busca o usuário;
4. verifica a senha com `password_verify()`;
5. gera um JWT;
6. retorna o token e os dados do usuário.

As senhas criadas pela API são armazenadas com hash produzido por `password_hash(..., PASSWORD_BCRYPT)`.

O JWT utiliza `HS256` e contém:

- `idUsuario`;
- `nomeUsuario`;
- `tipo`;
- `iat`;
- `exp`.

O token expira em uma hora.

Existe também:

```text
GET /auth/validate
```

Esse endpoint exige um token válido e retorna seu payload.

### 4.2 Autorização

O controller-base oferece:

- `checkToken()` para exigir autenticação;
- `requireAdmin()` para exigir autenticação e perfil `admin`.

`AuthController`, `UsuarioController` e `LoteController` herdam o controller-base. Entretanto, a proteção não é aplicada de maneira uniforme.

No fluxo ativo de usuários:

| Operação | Proteção atual |
|---|---|
| `POST /usuario/login` | pública |
| `GET /usuario/listarTodos` | usuário autenticado |
| `GET /usuario/listarIdUsuario/{idUsuario}` | usuário autenticado |
| `POST /usuario/adicionar` | administrador |
| `PUT /usuario/alterar` | usuário autenticado |
| `DELETE /usuario/deletar/{idUsuario}` | administrador |
| `GET /auth/validate` | token válido |

Em `LoteController`, as chamadas a `requireAdmin()` presentes em alguns métodos estão comentadas. Os endpoints ativos de lote não exigem autenticação.

Os controllers de sala, cogumelo, fase de cultivo, histórico de fase, leitura, parâmetros, atuadores e controle de atuadores também não aplicam autenticação aos endpoints ativos.

### 4.3 Comportamentos de segurança conhecidos

- a chave usada para assinar JWT está fixa no código;
- autenticação não é aplicada consistentemente aos recursos;
- vários endpoints administrativos permanecem públicos;
- um usuário autenticado pode chamar `UsuarioController::alterar()` informando outro `idUsuario`;
- o mesmo fluxo de alteração aceita o campo `tipo`, permitindo alterar o perfil do usuário informado;
- alteração de senha não exige confirmação da senha atual;
- não existe autenticação própria do ESP32.

---

## 5. Catálogo de rotas

Esta seção registra as rotas cadastradas em `core/Routes.php`.

### 5.1 Sala

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/sala/listarTodos` | Listar salas |
| GET | `/sala/listarIdSala/{idSala}` | Consultar uma sala |
| GET | `/sala/listarSalasComLotesAtivos` | Montar visão de salas com lotes ativos |
| POST | `/sala/adicionar` | Criar sala |
| PUT | `/sala/alterar` | Alterar sala |
| DELETE | `/sala/deletar/{idSala}` | Excluir sala |

### 5.2 Cogumelo

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/cogumelo/listarTodos` | Listar cogumelos |
| GET | `/cogumelo/listarIdCogumelo/{idCogumelo}` | Consultar cogumelo |
| POST | `/cogumelo/adicionar` | Criar cogumelo |
| PUT | `/cogumelo/alterar` | Alterar cogumelo |
| DELETE | `/cogumelo/deletar/{idCogumelo}` | Excluir cogumelo |

### 5.3 Fase de cultivo

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/faseCultivo/listarTodos` | Listar fases |
| GET | `/faseCultivo/listarIdFaseCultivo/{idFaseCultivo}` | Consultar fase |
| GET | `/faseCultivo/listarPorCogumelo/{idCogumelo}` | Listar fases de um cogumelo |
| POST | `/faseCultivo/adicionar` | Criar fase |
| PUT | `/faseCultivo/alterar` | Alterar fase |
| DELETE | `/faseCultivo/deletar/{idFaseCultivo}` | Excluir fase |

### 5.4 Lote

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/lote/listarTodos` | Listar lotes |
| GET | `/lote/listarAtivos` | Listar lotes ativos |
| GET | `/lote/listarIdLote/{idLote}` | Consultar lote |
| GET | `/lote/listarSalasDisponiveis` | Listar salas sem lote ativo |
| GET | `/lote/listarIdSala/{idSala}` | Consultar lote ativo por sala |
| POST | `/lote/adicionar` | Criar lote |
| PUT | `/lote/alterar` | Alterar lote |
| DELETE | `/lote/deletar/{idLote}` | Finalizar lote, apesar do nome da rota |
| DELETE | `/lote/deletar_fisico/{idLote}` | Excluir fisicamente um lote |

### 5.5 Histórico de fase

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/historico_fase/listarTodos` | Listar histórico |
| GET | `/historico_fase/listarIdHistorico/{idHistorico}` | Consultar registro |
| GET | `/historico_fase/listarIdLote/{idLote}` | Consultar histórico de um lote |
| GET | `/historico_fase/listarIdFase/{idFase}` | Consultar registros ativos de uma fase |
| POST | `/historico_fase/adicionar` | Criar registro |
| PUT | `/historico_fase/alterar` | Alterar registro histórico |
| DELETE | `/historico_fase/deletar/{idHistorico}` | Excluir registro histórico |

### 5.6 Parâmetros/configuração

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/parametros/listarTodos` | Listar configurações |
| GET | `/parametros/listarIdParametro/{idParametro}` | A rota indica busca por parâmetro, mas a implementação consulta por `idLote` |
| GET | `/parametros/listarIdLote/{idLote}` | Consultar configurações de um lote |
| POST | `/parametros/adicionar` | Criar configuração |
| PUT | `/parametros/alterar` | Rota registrada, porém sem action funcional; o método está comentado e o despacho retorna `405` |
| DELETE | `/parametros/deletar/{idParametro}` | Excluir configuração por `idConfig`, com inconsistência na validação anterior à exclusão |

### 5.7 Leitura

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/leitura/listarTodos` | Listar leituras |
| GET | `/leitura/listarIdLeitura/{idLeitura}` | Consultar leitura |
| GET | `/leitura/listarIdLote/{idLote}` | Listar leituras do lote |
| GET | `/leitura/listarUltimaLeitura/{idLote}` | Obter a última leitura do lote |
| POST | `/leitura/adicionar` | Registrar leitura |
| DELETE | `/leitura/deletar/{idLeitura}` | Excluir leitura |
| GET | `/leitura/grafico/{idLote}` | Retornar dados agregados para gráfico |

### 5.8 Atuador

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/atuador/listarTodos` | Listar atuadores |
| GET | `/atuador/listarIdAtuador/{idAtuador}` | Consultar atuador |
| GET | `/atuador/listarIdSala/{idSala}` | Listar atuadores da sala |
| POST | `/atuador/adicionar` | Criar atuador |
| PUT | `/atuador/alterar` | Alterar atuador |
| DELETE | `/atuador/deletar/{idAtuador}` | Excluir atuador |

Tipos aceitos atualmente: `umidade`, `temperatura`, `co2` e `luz`.

### 5.9 Controle de atuador

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/controleAtuador/listarTodos` | Listar controles |
| GET | `/controleAtuador/listarIdControle/{idControle}` | Consultar controle |
| GET | `/controleAtuador/listarIdAtuador/{idAtuador}` | Consultar histórico/controles de um atuador |
| GET | `/controleAtuador/listarIdLote/{idLote}` | Obter o último estado dos atuadores do lote; retorna `409` se o lote estiver finalizado |
| POST | `/controleAtuador/adicionar` | Registrar novo estado/comando |
| DELETE | `/controleAtuador/deletar/{idControle}` | Inativar controle |
| DELETE | `/controleAtuador/deletarFisico/{idControle}` | Excluir controle fisicamente |

O método de alteração de controle aparece comentado no código e não é uma rota ativa.

### 5.10 Usuário e autenticação

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/usuario/listarTodos` | Listar usuários; exige autenticação |
| GET | `/usuario/listarIdUsuario/{idUsuario}` | Consultar usuário; exige autenticação |
| POST | `/usuario/adicionar` | Criar usuário; exige administrador |
| PUT | `/usuario/alterar` | Alterar usuário; exige autenticação |
| DELETE | `/usuario/deletar/{idUsuario}` | Excluir usuário; exige administrador |
| POST | `/usuario/login` | Autenticar e gerar JWT |
| GET | `/auth/validate` | Validar JWT |

---

## 6. Fluxos e regras de negócio legados

### 6.1 Lote

#### Criação

`LoteController::adicionar()`:

1. recebe os dados por `body()`;
2. usa a data atual como `dataInicio` quando ela não é informada;
3. valida sala, cogumelo, datas e status;
4. impede a criação quando a sala já possui lote ativo;
5. exige uma fase de cultivo;
6. valida se a fase pertence ao cogumelo escolhido;
7. cria o lote;
8. copia os parâmetros da fase para `configuracao`;
9. cria o registro inicial em `historico_fase`.

As gravações de lote, configuração e histórico de fase não são executadas dentro de uma única transação. Uma falha intermediária pode deixar apenas parte do fluxo persistida.

A verificação de sala ocupada é feita na aplicação por `LoteModel::salaOcupada()`.

#### Alteração

`LoteController::alterar()` realiza uma atualização completa e exige, além de `idLote`, os campos `idSala`, `idCogumelo`, `dataInicio` e `status`.

Quando o status informado é `finalizado`, `dataFim` recebe a data atual se não tiver sido enviada. Quando o status informado é `ativo`, `dataFim` é removida.

Nesse segundo caso existe um comportamento incorreto conhecido: `salaOcupada($idSala)` procura qualquer lote ativo na sala sem excluir o próprio `idLote` em alteração. Assim, editar um lote que já está ativo na mesma sala pode fazer o próprio registro ser considerado conflito e resultar em `409`.

#### Finalização e exclusão

`DELETE /lote/deletar/{idLote}` não exclui fisicamente o registro. Ele altera o lote para `status = 'finalizado'` e define `dataFim = CURDATE()`.

`DELETE /lote/deletar_fisico/{idLote}` executa exclusão física.

No caminho de exclusão física, `LoteModel::finalizar_fisico()` captura `PDOException`, registra a mensagem e relança a exceção. `LoteController::deletar_fisico()` não possui `try/catch` para esse erro. Portanto, uma falha do banco pode escapar do fluxo normal de resposta JSON; a exposição de detalhes ao cliente depende da configuração de erros do ambiente PHP.

### 6.2 Fase de cultivo e histórico

`fase_cultivo` cadastra fases ligadas a uma espécie de cogumelo e contém parâmetros como temperatura, umidade e CO₂.

O controller valida:

- temperatura mínima menor que máxima;
- umidade mínima menor que máxima;
- umidade dentro de `0–100%`;
- `co2Max > 0`.

`historico_fase` registra a relação entre lote e fase. O recurso é exposto como CRUD e permite criar, alterar e excluir registros históricos diretamente.

Ao criar ou alterar registros, existem validações relacionadas a:

- existência do lote;
- existência da fase;
- bloqueio de novo histórico quando o lote está finalizado;
- compatibilidade entre o cogumelo do lote e o cogumelo da fase.

### 6.3 Configuração/parâmetros do lote

Ao criar um lote, a API copia os parâmetros da fase selecionada para a tabela `configuracao`.

Parâmetros também são expostos como recurso independente por rotas próprias.

O comportamento atual apresenta uma inconsistência central:

`ParametroModel::selectByIdParametro(int $id)` executa uma consulta por:

```sql
WHERE idLote = ?
```

apesar de o nome do método e algumas rotas indicarem uma busca por identificador do parâmetro, armazenado como `idConfig`.

Esse método é utilizado em três fluxos:

1. **`GET /parametros/listarIdParametro/{idParametro}`** — o valor recebido como `idParametro` é tratado pela model como `idLote`. O endpoint retorna a configuração mais recente desse lote, em vez de pesquisar diretamente por `idConfig`.
2. **`POST /parametros/adicionar`** — após inserir a configuração, o controller recebe o novo `idConfig` por `lastInsertId()` e o passa para `selectByIdParametro()`. Como a consulta usa esse valor como `idLote`, o campo `parametro` da resposta pode conter outro registro ou `null`.
3. **`DELETE /parametros/deletar/{idParametro}`** — antes de excluir, o controller usa `selectByIdParametro($id)` para encontrar o registro e verificar se o lote está finalizado. Essa validação pode consultar uma configuração pertencente a outro lote. A exclusão posterior usa corretamente `DELETE ... WHERE idConfig = ?`, de modo que o registro validado e o registro excluído podem não ser o mesmo. Isso pode produzir `404` ou `409` indevidos e também pode permitir uma exclusão que deveria ser bloqueada.

Além disso, a rota `PUT /parametros/alterar` está registrada em `Routes.php`, mas `ParametroController::alterar()` está comentado. Não existe action ativa para executar a alteração.

Quando existem várias configurações para um lote, `selectByIdLote()` retorna todas ordenadas da mais recente para a mais antiga.

### 6.4 Leituras ambientais

Cada registro legado de leitura concentra os valores ambientais em uma única linha vinculada ao lote:

```text
idLote
temperatura
umidade
co2
luz
```

`POST /leitura/adicionar`:

- exige `idLote`, `umidade`, `temperatura` e `co2` numéricos;
- aceita `luz` como `ligado` ou `desligado`, usando `ligado` como padrão;
- verifica se o lote existe;
- retorna `409` e impede a gravação se o lote estiver finalizado.

`DELETE /leitura/deletar/{idLeitura}` também impede a exclusão quando a leitura pertence a um lote finalizado.

Características e limitações do registro:

- existe apenas uma temperatura por leitura;
- não há identificação do sensor responsável por cada valor;
- não há agrupamento identificável de várias medições realizadas no mesmo instante;
- não existe identificador de idempotência para reconhecer um reenvio do ESP32;
- não existe autenticação própria do ESP32;
- as entradas não utilizam JSON;
- as validações verificam tipo/formato, mas não estabelecem limites de plausibilidade física para os valores;
- leituras de lotes não finalizados podem ser excluídas;
- `luz` é armazenado junto às medições ambientais;
- as listagens não possuem paginação.

### 6.5 Gráficos

`GET /leitura/grafico/{idLote}` agrega leituras e prepara uma resposta para consumo do aplicativo.

Os parâmetros são recebidos pela query string porque, para `GET`, `Request::body()` retorna `$_GET`.

Parâmetros aceitos:

| Parâmetro | Valores/comportamento |
|---|---|
| `aggregation` | `daily`, `weekly`, `monthly` ou `24h`; `hourly` é convertido para `24h` |
| `metric` | `temperatura`, `umidade` ou `co2` |
| `start_date` | início opcional do intervalo |
| `end_date` | fim opcional do intervalo |

Quando `start_date` e `end_date` são informados juntos, o controller tenta convertê-los com `DateTime`, normaliza os horários conforme a agregação e verifica se a data inicial não é posterior à final.

Quando o intervalo não é informado, são utilizados períodos padrão:

- `daily`: últimos 7 dias;
- `weekly`: últimas 8 semanas;
- `monthly`: últimos 6 meses;
- `24h`: últimas 24 horas.

Não existe um limite máximo para a amplitude de um intervalo personalizado.

A resposta contém:

- `chart_type = line`;
- série em `data`;
- título;
- rótulo do eixo X;
- rótulo do eixo Y;
- cor predefinida.

Os valores são agregados por média. Informações de apresentação do gráfico fazem parte do payload retornado pela API.

### 6.6 Sala e painel

Além do CRUD de salas, `GET /sala/listarSalasComLotesAtivos` fornece uma visão consolidada para o aplicativo.

Comportamentos e limitações observados:

- endpoints de sala não exigem autenticação;
- criação sem body pode retornar `404` em vez de `400`;
- alguns campos são acessados diretamente sem valor padrão;
- exclusão física de sala é permitida;
- atualização sem alteração efetiva pode ser tratada como erro;
- a consulta consolidada obtém temperatura, umidade e CO₂ separadamente;
- não existe identificação individual de sensores nessa visão.

### 6.7 Atuadores e controle

`atuador` cadastra equipamentos associados às salas. Os tipos aceitos são:

- `umidade`;
- `temperatura`;
- `co2`;
- `luz`.

`controle_atuador` registra estados/acionamentos associados a lote e atuador. Registros sucessivos são preservados e o sistema consulta o estado mais recente para determinar a situação atual.

Para lotes finalizados:

- criação de novo controle é bloqueada;
- remoção de controle é bloqueada;
- `GET /controleAtuador/listarIdLote/{idLote}` também retorna `409` antes de consultar o último estado.

Características e limitações:

- os endpoints não possuem autenticação/autorização uniforme;
- histórico de controle e comando operacional compartilham o mesmo recurso;
- existe exclusão física;
- não existe protocolo explícito de confirmação do ESP32;
- não existem estados estruturados de execução, como pendente, executado ou falhou;
- não há registro estruturado do motivo de uma falha;
- não existe duração ou expiração explícita para comandos temporários.

---

## 7. Limitações transversais registradas

### 7.1 Segurança

- autenticação aplicada apenas a parte dos endpoints de usuário e autenticação;
- endpoints administrativos de outros recursos permanecem públicos;
- ESP32 sem identidade/autenticação própria;
- chave JWT fixa no código;
- autorização insuficiente em `PUT /usuario/alterar`;
- `data_debug.php` pode ser acessado diretamente no Apache, fora do roteador;
- exceções do banco podem escapar do formato JSON em alguns fluxos, como na exclusão física de lote.

### 7.2 Contrato HTTP

- rotas utilizam verbos de ação, como `/listarTodos` e `/adicionar`;
- entrada não padronizada em JSON;
- formatos de resposta diferentes entre controllers;
- uso inconsistente de códigos HTTP;
- ausência de estrutura padronizada para erros;
- ausência de versionamento explícito na URL;
- validação do tipo dos parâmetros de rota ocorre nos controllers, e não no roteador.

### 7.3 Integridade e rastreabilidade

- operações compostas não utilizam transação de forma consistente;
- recursos históricos podem ser excluídos fisicamente;
- histórico de fase é tratado como CRUD editável;
- controles de atuador podem ser apagados;
- leituras podem ser apagadas enquanto o lote não está finalizado;
- não existe idempotência para reenvio de leituras;
- a inconsistência de `selectByIdParametro()` pode validar um registro diferente daquele posteriormente excluído.

### 7.4 Modelo de medições

O modelo legado registra temperatura, umidade, CO₂ e luz em colunas fixas de uma única leitura.

Esse modelo:

- registra apenas uma temperatura por linha;
- não identifica sensores individualmente;
- não associa a leitura a um identificador de sensor;
- não possui um identificador reutilizável para reconhecer reenvios do mesmo conjunto de medições.

### 7.5 Manutenção

- permanecem comentários de TODO e trechos de implementação antiga;
- há código comentado de autenticação e de endpoints;
- nomes de rotas e métodos nem sempre representam o comportamento executado;
- `DELETE /lote/deletar/{idLote}` finaliza em vez de excluir;
- `PUT /parametros/alterar` está registrada sem implementação ativa;
- `selectByIdParametro()` não consulta pelo identificador que seu nome indica.

---

## 8. Encerramento do inventário

Este arquivo registra a API legada como ela existe antes da refatoração. Os comportamentos problemáticos descritos fazem parte do histórico do sistema e são mantidos neste documento justamente para que não se percam durante a evolução do projeto.

Qualquer comportamento futuro deve ser definido separadamente das informações registradas aqui.
