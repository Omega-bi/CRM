<?php

use Modules\Workspace\Providers\WorkspaceServiceProvider;

return [
    'modules' => [
        'Workspace' => [
            'path' => base_path('Modules/Workspace'),
            'namespace' => 'Modules\Workspace',
            'providers' => [
                WorkspaceServiceProvider::class,
            ],
        ],
    ],
];
