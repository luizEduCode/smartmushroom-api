<?php
require_once './core/Controller.php';
require_once './model/UsuarioModel.php';

class UsuarioController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function login(Request $request, Response $response, array $url)
    {
        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body nao recebido'], 400);
        }

        $email = strtolower(trim($data['email'] ?? ''));
        $senha = $data['senha'] ?? '';

        if ($email === '' || $senha === '') {
            return $response->json(['message' => 'Email e senha sao obrigatorios'], 400);
        }
        if (!self::validarEmail($email)) {
            return $response->json(['message' => 'Email invalido'], 400);
        }

        $usuario = $this->model->selectAuthByEmail($email);
        if ($usuario === null || !password_verify($senha, $usuario['senha'])) {
            return $response->json(['message' => 'Credenciais invalidas'], 401);
        }

        $token = Jwt::gerarJWT(
            (int)$usuario['idUsuario'],
            $usuario['nomeUsuario'],
            $usuario['tipo']
        );

        unset($usuario['senha']);

        return $response->json([
            'message' => 'Login realizado com sucesso',
            'token' => $token,
            'usuario' => $usuario
        ], 200);
    }

    public function listarTodos(Request $request, Response $response, array $url)
    {
        if ($this->checkToken($request, $response) === null) {
            return;
        }

        $usuarios = $this->model->selectAll();
        return $response->json($usuarios, 200);
    }

    public function listarIdUsuario(Request $request, Response $response, array $url)
    {
        if ($this->checkToken($request, $response) === null) {
            return;
        }

        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: GET /usuario/listarIdUsuario/{idUsuario}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID do usuario invalido'], 400);
        }

        $usuario = $this->model->selectById($id);
        if ($usuario === null) {
            return $response->json(['message' => 'Usuario nao encontrado'], 404);
        }

        return $response->json($usuario, 200);
    }

    public function adicionar(Request $request, Response $response, array $url)
    {
        if ($this->requireAdmin($request, $response) === null) {
            return;
        }

        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body nao recebido'], 400);
        }

        $nome = trim($data['nomeUsuario'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $senha = $data['senha'] ?? '';
        $tipo = strtolower(trim($data['tipo'] ?? 'usuario'));

        if ($nome === '' || $email === '' || $senha === '') {
            return $response->json(['message' => 'Campos nomeUsuario, email e senha sao obrigatorios'], 400);
        }

        if (!self::validarEmail($email)) {
            return $response->json(['message' => 'Email invalido'], 400);
        }

        if (!self::validarSenha($senha)) {
            return $response->json(['message' => 'Senha deve conter ao menos 6 caracteres'], 400);
        }

        if (!self::validarTipo($tipo)) {
            return $response->json(['message' => "Tipo invalido. Use 'admin' ou 'usuario'"], 400);
        }

        if ($this->model->selectByEmail($email) !== null) {
            return $response->json(['message' => 'Email ja cadastrado'], 409);
        }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
        $novoId = $this->model->create($nome, $email, $senhaHash, $tipo);

        if ($novoId <= 0) {
            return $response->json(['message' => 'Erro ao adicionar usuario'], 500);
        }

        $criado = $this->model->selectById($novoId);

        return $response->json([
            'message' => 'Usuario criado com sucesso',
            'usuario' => $criado
        ], 201);
    }

    public function alterar(Request $request, Response $response, array $url)
    {
        if ($this->checkToken($request, $response) === null) {
            return;
        }

        $data = $request->body();
        if (empty($data)) {
            return $response->json(['message' => 'Body nao recebido'], 400);
        }

        $idUsuario = (int)($data['idUsuario'] ?? 0);
        $nome = trim($data['nomeUsuario'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $tipo = strtolower(trim($data['tipo'] ?? 'usuario'));
        $senha = $data['senha'] ?? null;

        if ($idUsuario <= 0 || $nome === '' || $email === '') {
            return $response->json(['message' => 'Campos obrigatorios ausentes ou invalidos'], 400);
        }

        if (!self::validarEmail($email)) {
            return $response->json(['message' => 'Email invalido'], 400);
        }

        if (!self::validarTipo($tipo)) {
            return $response->json(['message' => "Tipo invalido. Use 'admin' ou 'usuario'"], 400);
        }

        $usuarioAtual = $this->model->selectById($idUsuario);
        if ($usuarioAtual === null) {
            return $response->json(['message' => 'Usuario nao encontrado'], 404);
        }

        $emailExistente = $this->model->selectByEmail($email);
        if ($emailExistente !== null && (int)$emailExistente['idUsuario'] !== $idUsuario) {
            return $response->json(['message' => 'Email ja utilizado por outro usuario'], 409);
        }

        $senhaHash = null;
        if ($senha !== null && $senha !== '') {
            if (!self::validarSenha($senha)) {
                return $response->json(['message' => 'Senha deve conter ao menos 6 caracteres'], 400);
            }
            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
        }

        $sucesso = $this->model->update($idUsuario, $nome, $email, $tipo, $senhaHash);

        if (!$sucesso) {
            return $response->json(['message' => 'Nada foi alterado ou ocorreu um erro'], 400);
        }

        $atualizado = $this->model->selectById($idUsuario);

        return $response->json([
            'message' => 'Usuario atualizado com sucesso',
            'usuario' => $atualizado
        ], 200);
    }

    public function deletar(Request $request, Response $response, array $url)
    {
        if ($this->requireAdmin($request, $response) === null) {
            return;
        }

        if (!isset($url[0]) || !is_numeric($url[0])) {
            return $response->json(['message' => 'Uso correto: DELETE /usuario/deletar/{idUsuario}'], 400);
        }

        $id = (int)$url[0];
        if ($id <= 0) {
            return $response->json(['message' => 'ID do usuario invalido'], 400);
        }

        if ($this->model->selectById($id) === null) {
            return $response->json(['message' => 'Usuario nao encontrado'], 404);
        }

        $sucesso = $this->model->delete($id);

        if (!$sucesso) {
            return $response->json(['message' => 'Erro ao remover usuario'], 500);
        }

        return $response->json(['message' => 'Usuario removido com sucesso'], 200);
    }

    private static function validarEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private static function validarTipo(string $tipo): bool
    {
        return in_array($tipo, ['admin', 'usuario'], true);
    }

    private static function validarSenha(string $senha): bool
    {
        return strlen($senha) >= 6;
    }
}
