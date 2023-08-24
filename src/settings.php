<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        'rpt_renderer' => [
            'template_path' => __DIR__ . '/../public/core/dwnld/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Configuración de mi DNS data base
        
        'connectionString' => [
            //'dns'  => 'mysql:host=localhost;dbname=alertave_connect;charset=utf8',
            //'user' => 'alertave_connect',
            //'pass' => 'JsmS0G~2V9yL'
            'dns'  => 'mysql:host=localhost;dbname=base_inventario;charset=utf8',
            'user' => 'root',
            'pass' => ''        
        ]
    ],
];
