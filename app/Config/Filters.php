<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;

class Filters extends BaseFilters
{
    /**
     * Configura aliases para as classes de filtro
     * para facilitar a leitura e a simplicidade.
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => \CodeIgniter\Filters\CSRF::class,
        'toolbar'       => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'      => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => \App\Filters\Cors::class, // Adicionado
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
    ];

    /**
     * Lista de filtros especiais requeridos.
     *
     * Os filtros listados aqui são especiais. Eles são aplicados antes e depois
     * de outros tipos de filtros, e sempre aplicados mesmo se uma rota não existir.
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            // 'forcehttps', // Força requisições seguras globais
            // 'pagecache',  // Cache de página web
        ],
        'after' => [
            // 'pagecache',   // Cache de página web
            // 'performance', // Métricas de performance
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * Lista de aliases de filtros que são sempre
     * aplicados antes e depois de cada requisição.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            // 'csrf',
            // 'invalidchars',
            'cors', // Aplica o filtro CORS globalmente antes de cada requisição
        ],
        'after' => [
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * Lista de aliases de filtros que funcionam em um
     * método HTTP específico (GET, POST, etc.).
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * Lista de aliases de filtros que devem ser executados em quaisquer
     * padrões de URI antes ou depois.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'cors' => [
            'before' => [
                'api/*', // Aplica o filtro CORS a todas as rotas que começam com 'api/'
            ],
        ],
    ];
}
