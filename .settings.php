<?php

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Dk\\Vasin\\Controllers',
        ],
        'restIntegration' => [
            'enabled' => true,
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            'dk.vasin.recipientService' => [
                'className' => '\\Dk\\Vasin\\Services\RecipientService',
            ],
            'dk.vasin.funnelService' => [
                'className' => '\\Dk\\Vasin\\Services\FunnelService',
            ],
        ],
        'readonly' => true,
    ],
];