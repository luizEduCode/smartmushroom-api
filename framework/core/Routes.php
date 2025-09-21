<?php

require_once 'Http.php';

//sala
Http::get('/sala/listarTodos',                                              'SalaController@listarTodos');
Http::get('/sala/listarIdSala/{idSala}',                                    'SalaController@listarIdSala');
Http::get('/sala/listarSalasComLotesAtivos',                                'SalaController@listarSalasComLotesAtivos');
Http::post('/sala/adicionar',                                               'SalaController@adicionar');
Http::put('/sala/alterar',                                                  'SalaController@alterar');
Http::delete('/sala/deletar/{idLote}',                                      'SalaController@deletar');


//coguemlo
Http::get('/cogumelo/listarTodos',                                          'CogumeloController@listarTodos');
Http::get('/cogumelo/listarIdCogumelo/{idCogumelo}',                        'CogumeloController@listarIdCogumelo');
Http::post('/cogumelo/adicionar',                                           'CogumeloController@adicionar');
Http::put('/cogumelo/alterar',                                              'CogumeloController@alterar');
Http::delete('/cogumelo/deletar/{idLote}',                                  'CogumeloController@deletar');


//fase_cultivo
Http::get('/faseCultivo/listarTodos',                                       'FaseCultivoController@listarTodos');
Http::get('/faseCultivo/listarIdFaseCultivo/{idCultivo}',                   'FaseCultivoController@listarIdFaseCultivo');
Http::get('/faseCultivo/listarPorCogumelo/{idCogumelo}',                    'FaseCultivoController@listarPorCogumelo');
Http::post('/faseCultivo/adicionar',                                        'FaseCultivoController@adicionar');
Http::put('/faseCultivo/alterar',                                           'FaseCultivoController@alterar');
Http::delete('/faseCultivo/deletar/{idLote}',                               'FaseCultivoController@deletar');


//lote
Http::get('/lote/listarTodos',                                              'LoteController@listarTodos');
Http::get('/lote/listarAtivos',                                             'LoteController@listarAtivos');
Http::get('/lote/listarIdLote/{idLote}',                                    'LoteController@listarIdLote');
Http::get('/lote/listarSalasDisponiveis',                                   'LoteController@listarSalasDisponiveis');
Http::post('/lote/adicionar',                                               'LoteController@adicionar');
Http::put('/lote/alterar',                                                  'LoteController@alterar');
Http::delete('/lote/deletar/{idLote}',                                      'LoteController@deletar');
Http::delete('/lote/deletar_fisico/{idLote}',                               'LoteController@deletar_fisico');


//historico_fase
Http::get('/historico_fase/listarTodos',                                    'HistoricoFaseController@listarTodos');
Http::get('/historico_fase/listarIdHistorico/{idHistorico}',                'HistoricoFaseController@listarIdHistorico');
Http::get('/historico_fase/listarIdLote/{idLote}',                          'HistoricoFaseController@listarIdLote');
Http::get('/historico_fase/listarIdFase/{idFase}',                          'HistoricoFaseController@listarIdFase');
Http::post('/historico_fase/adicionar',                                     'HistoricoFaseController@adicionar');
Http::put('/historico_fase/alterar',                                        'HistoricoFaseController@alterar');
Http::delete('/historico_fase/deletar/{idHistorico}',                       'HistoricoFaseController@deletar');

//leitura
Http::get('/leitura/listarTodos',                                           'leituraController@listarTodos');
Http::get('/leitura/listarIdLeitura/{idLeitura}',                           'leituraController@listarIdLeitura');
Http::get('/leitura/listarIdLote/{idLote}',                                 'leituraController@listarIdLote');
Http::post('/leitura/adicionar',                                            'leituraController@adicionar');
//leitura nao precisa de update
Http::delete('/leitura/deletar/{idLeitura}',                                'leituraController@deletar');

//usuario


//configuracao ou parametros
Http::get('/parametros/listarTodos',                                        'ParametroController@listarTodos');
Http::get('/parametros/listarIdParametro/{idParametro}',                    'ParametroController@listarIdParametro');
Http::get('/parametros/listarIdLote/{idLote}',                              'ParametroController@listarIdLote');
Http::post('/parametros/adicionar',                                         'ParametroController@adicionar');
Http::put('/parametros/alterar',                                            'ParametroController@alterar');
//parametro nao precisa de update
Http::delete('/parametros/deletar/{idParametro}',                           'ParametroController@deletar');

//alerta


//atuador
Http::get('/atuador/listarTodos',                                           'AtuadorController@listarTodos');
Http::get('/atuador/listarIdAtuador/{idAtuador}',                           'AtuadorController@listarIdAtuador');
Http::get('/atuador/listarIdSala/{idSala}',                                 'AtuadorController@listarIdSala');
Http::post('/atuador/adicionar',                                            'AtuadorController@adicionar');
Http::post('/atuador/alterar',                                               'AtuadorController@alterar');
Http::delete('/atuador/deletar/{idAtuador}',                                'AtuadorController@deletar');


//controle_atuador
Http::get('/controleAtuador/listarTodos',                                   'ControleAtuadorController@listarTodos');
Http::get('/controleAtuador/listarIdControle/{idControle}',                 'ControleAtuadorController@listarIdControle');
Http::get('/controleAtuador/listarIdAtuador/{idAtuador}',                   'ControleAtuadorController@listarIdAtuador');
Http::get('/controleAtuador/listarIdLote/{idLote}',                         'ControleAtuadorController@listarIdLote');
Http::post('/controleAtuador/adicionar',                                    'ControleAtuadorController@adicionar');
Http::post('/controleAtuador/alterar',                                    'ControleAtuadorController@alterar');
//controle_atuador nao precisa de update
Http::delete('/controleAtuador/deletar/{idControle}',                       'ControleAtuadorController@deletarLogico');
Http::delete('/controleAtuador/deletarFisico/{idControle}',                 'ControleAtuadorController@deletarFisico');

//log_sistema