<?php
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY_BE'),
        ],
    ],
];
