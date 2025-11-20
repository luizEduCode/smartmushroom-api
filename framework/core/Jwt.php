<?php

define('CHAVE_PRIVADA', 'chave_privada');

class Jwt
{
    public static function gerarJWT(int $idUsuario = 0, string $nomeUsuario = 'USUARIO', string $tipo = 'usuario')
    {
        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]);

        $payload = json_encode([
            'idUsuario' => $idUsuario,
            'nomeUsuario' => $nomeUsuario,
            'tipo' => $tipo,
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        $base64Header = Jwt::replaceCaracteresParaGerarJwt(base64_encode($header));
        $base64Payload = Jwt::replaceCaracteresParaGerarJwt(base64_encode($payload));

        $assinatura = hash_hmac('SHA256', "$base64Header.$base64Payload", CHAVE_PRIVADA, true);
        $base64Assinatura = Jwt::replaceCaracteresParaGerarJwt(base64_encode($assinatura));

        return "$base64Header.$base64Payload.$base64Assinatura";
    }

    private static function replaceCaracteresParaGerarJwt($dados)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], $dados);
    }

    private static function replaceCaracteresParaValidarJwt($dados)
    {
        return str_replace(['-', '_'], ['+', '/'], $dados);
    }

    public static function tokenValido($token)
    {
        $token = str_replace('Bearer ', '', $token ?? '');
        $blocos = explode('.', $token);
        if (count($blocos) != 3) {
            return 'Token nao possui os requisitos (header, payload e assinatura)';
        }

        list($header, $payload, $assinatura) = $blocos;

        $decodeHeader = Jwt::trataComprimentoBase64(Jwt::replaceCaracteresParaValidarJwt($header));
        $decodePayload = Jwt::trataComprimentoBase64(Jwt::replaceCaracteresParaValidarJwt($payload));

        if ($decodeHeader === false || $decodePayload === false) {
            return 'Token malformado';
        }

        if (Jwt::tokenEstaExpirado($decodePayload)) {
            return 'Token esta expirado';
        }

        $assinaturaCalculada = hash_hmac('SHA256', "$header.$payload", CHAVE_PRIVADA, true);
        $base64Assinatura = Jwt::replaceCaracteresParaGerarJwt(base64_encode($assinaturaCalculada));

        if ($base64Assinatura != $assinatura) {
            return 'Assinatura do token invalida';
        }

        return '';
    }

    public static function extrairPayload($token)
    {
        $token = str_replace('Bearer ', '', $token ?? '');
        $blocos = explode('.', $token);
        if (count($blocos) != 3) {
            return null;
        }

        list(, $payload) = $blocos;

        $decodePayload = Jwt::trataComprimentoBase64(Jwt::replaceCaracteresParaValidarJwt($payload));
        if ($decodePayload === false) {
            return null;
        }

        $dados = json_decode($decodePayload, true);
        return $dados ?: null;
    }

    private static function trataComprimentoBase64($dados)
    {
        $padding = 4 - (strlen($dados) % 4);
        if ($padding < 4) {
            $dados .= str_repeat('=', $padding);
        }
        return base64_decode($dados);
    }

    private static function tokenEstaExpirado($payload)
    {
        $payload = json_decode($payload, true);
        $exp = $payload['exp'] ?? 0;
        return $exp < time();
    }
}
