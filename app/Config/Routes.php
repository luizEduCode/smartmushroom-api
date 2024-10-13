<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Rotas para leituras de sensores
$routes->post('api/leituras', 'Api::receberLeitura');
$routes->get('api/leituras', 'Api::listarLeituras');

// Rotas para comandos de atuadores
$routes->post('api/comandos', 'Api::enviarComando');
$routes->get('api/comandos', 'Api::listarComandos');


//teste de conexão
$routes->get('api/teste-conexao-banco', 'Api::testeConexaoBanco');