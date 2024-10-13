<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Api extends ResourceController
{
    protected $format = 'json';


    //recebe dados ESP32
    public function receberLeitura()
    {
        $input = $this->request->getJSON();

        $model = new \App\Models\LeiturasModel();

        $data = [
            'temperatura' => $input->temperatura,
            'umidade'     => $input->umidade,
        ];

        $model->insert($data);

        return $this->respondCreated($data);
    }

    //Leituras para o Flutter
    public function listarLeituras()
    {
        $model = new \App\Models\LeiturasModel();
        $data = $model->orderBy('created_at', 'DESC')->findAll();

        return $this->respond($data);
    }

    public function enviarComando()
    {
        // Lógica para receber comandos do Flutter
    }

    public function listarComandos()
    {
        // Lógica para enviar comandos para a ESP32
    }

    public function testeConexaoBanco()
    {
        try {
            $db = \Config\Database::connect();
            if ($db->connID) {
                return $this->respond(['message' => 'Conexão com o banco de dados estabelecida com sucesso.']);
            } else {
                return $this->fail('Falha na conexão com o banco de dados.');
            }
        } catch (\Exception $e) {
            return $this->fail('Erro: ' . $e->getMessage());
        }
    }
}
