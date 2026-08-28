<?php

return [
    'enabled' => env('NMS_DEV_PANEL_ENABLED', true),
    'environments' => ['local'],
    'route_prefix' => '_nms-dev-panel',
    'jira_url' => 'https://newmindstart.atlassian.net/browse',
    'email_domain' => 'local.test',
    'user_model' => 'App\\Models\\User',
    'domain_model' => 'App\\Models\\Domain',
    'domain_configuration_model' => 'App\\Models\\Configuration\\DomainConfiguration',
    'payment_environment' => 'local',
    'database_dump' => [
        'binary' => env('NMS_DEV_PANEL_MYSQL_BINARY', 'mysql'),
        'max_kilobytes' => 3 * 1024 * 1024,
        'timeout' => 900,
    ],
    'payment_settings' => [
        ['config_path' => 'stripe.account_id', 'config_key' => 'STRIPE_ACCOUNT_ID', 'source' => 'NMS_DEV_STRIPE_ACCOUNT_ID', 'value' => env('NMS_DEV_STRIPE_ACCOUNT_ID')],
        ['config_path' => 'stripe.public_key', 'config_key' => 'STRIPE_KEY', 'source' => 'NMS_DEV_STRIPE_KEY', 'value' => env('NMS_DEV_STRIPE_KEY')],
        ['config_path' => 'stripe.secret_key.live', 'config_key' => 'STRIPE_SECRET', 'source' => 'NMS_DEV_STRIPE_SECRET', 'value' => env('NMS_DEV_STRIPE_SECRET')],
        ['config_path' => 'stripe.webhook_secret', 'config_key' => 'STRIPE_WEBHOOK_SECRET', 'source' => 'NMS_DEV_STRIPE_WEBHOOK_SECRET', 'value' => env('NMS_DEV_STRIPE_WEBHOOK_SECRET')],
        ['config_path' => 'ecommpay.project_id', 'config_key' => 'ECOMMPAY_PROJECT', 'source' => 'NMS_DEV_ECOMMPAY_PROJECT', 'value' => env('NMS_DEV_ECOMMPAY_PROJECT')],
        ['config_path' => 'ecommpay.secret_key', 'config_key' => 'ECOMMPAY_SECRET', 'source' => 'NMS_DEV_ECOMMPAY_SECRET', 'value' => env('NMS_DEV_ECOMMPAY_SECRET')],
        ['config_path' => 'ecommpay.custom_webhook', 'config_key' => 'ECOMMPAY_CUSTOM_WEBHOOK', 'source' => 'NMS_DEV_NGROK_URL', 'value' => env('NMS_DEV_NGROK_URL'), 'suffix' => '/api/payment/webhook/ecommpay'],
    ],
    'cookie_names' => ['XSRF-TOKEN'],
    'cookie_paths' => ['/'],
    'cookie_domains' => [null],
];
