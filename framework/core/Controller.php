<?php

require_once './core/Jwt.php';

abstract class Controller
{
    /**
     * Valida o token enviado no header Authorization.
     * Retorna payload decodificado em caso de sucesso.
     */
    protected function checkToken(Request $request, Response $response): ?array
    {
        $token = $request->header('Authorization');
        $erro = Jwt::tokenValido($token);

        if (!empty($erro)) {
            $response->json(['message' => $erro], 401);
            return null;
        }

        $payload = Jwt::extrairPayload($token);
        if ($payload === null) {
            $response->json(['message' => 'Token invalido'], 401);
            return null;
        }

        return $payload;
    }

    /**
     * Exige autenticacao e perfil admin.
     */
    protected function requireAdmin(Request $request, Response $response): ?array
    {
        $payload = $this->checkToken($request, $response);
        if ($payload === null) {
            return null;
        }

        if (($payload['tipo'] ?? '') !== 'admin') {
            $response->json(['message' => 'Acesso restrito a administradores'], 403);
            return null;
        }

        return $payload;
    }
}
