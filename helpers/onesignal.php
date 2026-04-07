<?php

function mg_onesignal_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $localConfig = [];
    $localPath = __DIR__ . '/../includes/onesignal.local.php';
    if (file_exists($localPath)) {
        $loaded = require $localPath;
        if (is_array($loaded)) {
            $localConfig = $loaded;
        }
    }

    $enabledValue = $localConfig['enabled'] ?? getenv('ONESIGNAL_ENABLED') ?? false;
    $enabled = filter_var($enabledValue, FILTER_VALIDATE_BOOLEAN);
    $envApiUrl = getenv('ONESIGNAL_API_URL');
    $apiUrl = trim((string)($localConfig['api_url'] ?? ''));
    if ($apiUrl === '' && $envApiUrl !== false) {
        $apiUrl = trim((string)$envApiUrl);
    }
    $apiUrl = preg_replace('/[\x00-\x1F\x7F\s]+/u', '', (string)$apiUrl) ?? '';
    if ($apiUrl === '' || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        $apiUrl = 'https://api.onesignal.com/notifications?c=push';
    }

    $config = [
        'enabled' => $enabled,
        'app_id' => trim((string)($localConfig['app_id'] ?? getenv('ONESIGNAL_APP_ID') ?? '')),
        'rest_api_key' => trim((string)($localConfig['rest_api_key'] ?? getenv('ONESIGNAL_REST_API_KEY') ?? '')),
        'safari_web_id' => trim((string)($localConfig['safari_web_id'] ?? getenv('ONESIGNAL_SAFARI_WEB_ID') ?? '')),
        'api_url' => $apiUrl,
    ];

    $config['web_ready'] = $config['enabled'] && $config['app_id'] !== '';
    $config['api_ready'] = $config['web_ready'] && $config['rest_api_key'] !== '';

    return $config;
}

function mg_onesignal_public_config(): array
{
    $config = mg_onesignal_config();

    return [
        'enabled' => $config['web_ready'],
        'app_id' => $config['app_id'],
        'safari_web_id' => $config['safari_web_id'],
        'service_worker_path' => 'OneSignalSDKWorker.js',
        'service_worker_updater_path' => 'OneSignalSDKUpdaterWorker.js',
        'api_url_debug' => $config['api_url'],
    ];
}

function mg_onesignal_base_url(): string
{
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = trim(dirname(dirname($scriptName)), '/');

    return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
}

function mg_onesignal_normalize_url(?string $url): ?string
{
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $baseUrl = mg_onesignal_base_url();
    if ($baseUrl === '') {
        return null;
    }

    if ($url[0] === '/') {
        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $url;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

function mg_onesignal_send_to_users(array $externalIds, string $title, string $message, array $extra = []): array
{
    $config = mg_onesignal_config();
    $targets = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $externalIds))));

    if (!$config['api_ready']) {
        return ['success' => false, 'error' => 'OneSignal nao configurado para envio.'];
    }

    if (!$targets) {
        return ['success' => false, 'error' => 'Nenhum usuario de destino informado para o push.'];
    }

    $payload = [
        'app_id' => $config['app_id'],
        'target_channel' => 'push',
        'include_aliases' => [
            'external_id' => $targets,
        ],
        'headings' => [
            'pt-BR' => $title,
            'pt' => $title,
            'en' => $title,
        ],
        'contents' => [
            'pt-BR' => $message,
            'pt' => $message,
            'en' => $message,
        ],
    ];

    if (!empty($extra['url'])) {
        $normalizedUrl = mg_onesignal_normalize_url((string)$extra['url']);
        if ($normalizedUrl !== null) {
            $payload['url'] = $normalizedUrl;
        }
    }
    if (!empty($extra['data']) && is_array($extra['data'])) {
        $payload['data'] = $extra['data'];
    }

    $headers = [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Key ' . $config['rest_api_key'],
    ];

    $requestBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $curlFailed = false;
    $curlError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($config['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = trim((string)curl_error($ch));
        curl_close($ch);

        if ($response === false) {
            $curlFailed = true;
        }
    }

    if (!function_exists('curl_init') || $curlFailed) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $requestBody,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($config['api_url'], false, $context);
        $httpCode = 0;
        foreach (($http_response_header ?? []) as $headerLine) {
            if (preg_match('/\s(\d{3})\s/', $headerLine, $match)) {
                $httpCode = (int)$match[1];
                break;
            }
        }

        if ($response === false) {
            $errorMessage = 'Falha ao enviar push via OneSignal.';
            if ($curlError !== '') {
                $errorMessage .= ' curl=' . $curlError;
            }
            $errorMessage .= ' api_url=' . $config['api_url'];
            return ['success' => false, 'error' => $errorMessage];
        }
    }

    $decoded = json_decode((string)$response, true);
    if ($httpCode >= 400) {
        $error = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$response;
        return ['success' => false, 'error' => $error !== '' ? $error : 'Erro no OneSignal.'];
    }

    return [
        'success' => true,
        'data' => $decoded,
    ];
}

function mg_onesignal_notify_user(?string $externalId, string $title, string $message, array $extra = []): array
{
    $externalId = trim((string)$externalId);
    if ($externalId === '') {
        return ['success' => false, 'error' => 'Usuario de push vazio.'];
    }

    return mg_onesignal_send_to_users([$externalId], $title, $message, $extra);
}
