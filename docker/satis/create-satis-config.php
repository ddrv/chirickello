<?php

$config = [
    'name' => 'uberpopug/packages',
    'homepage' => 'http://satis',
    'repositories' => [],
    'require-all' => true,
    'archive' => [
        'directory' => 'dist',
        'format' => 'tar',
        'prefix-url' => 'http://satis',
        'skip-dev' => true,
    ],
];

$files = glob('/opt/satis/packages/*/*/composer.json');
foreach ($files as $file) {
    $url = substr($file, 0, -14);
    $package = substr($url, 20);
    $config['repositories'][] = [
        'type' => 'path',
        'url' => $url,
        'options' => [
            'symlink' => false,
            'reference' => 'config',
        ],
    ];
}

echo json_encode($config, JSON_PRETTY_PRINT) . PHP_EOL;
