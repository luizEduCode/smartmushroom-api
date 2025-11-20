<?php
require_once './core/Controller.php';

class AuthController extends Controller
{
    public function validate(Request $request, Response $response, array $url)
    {
        $payload = $this->checkToken($request, $response);
        if ($payload === null) {
            return;
        }

        return $response->json([
            'message' => 'Token valido',
            'payload' => $payload
        ], 200);
    }
}
